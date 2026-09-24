<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileProjectPublishController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.zyd_mobile.publish_token');

        if ($expectedToken === '') {
            return response()->json([
                'message' => 'Mobil yayın servisi henüz yapılandırılmadı. Sunucuda ZYD_MOBILE_PUBLISH_TOKEN veya ZYD_PUBLISH_TOKEN tanımlanmalı.',
            ], 503);
        }

        $providedToken = (string) $request->bearerToken();
        if ($providedToken === '') {
            $providedToken = (string) $request->header('X-ZYD-Publish-Token');
        }

        if (! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'ZYD Emlak yayın anahtarı geçersiz veya eksik.',
            ], 401);
        }

        $validated = $request->validate([
            'local_project_id' => ['required', 'integer'],
            'project_uuid' => ['nullable', 'uuid'],
            'remote_publication_id' => ['nullable', 'integer', 'min:1'],
            'publication_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'in:publish,update,unpublish'],
            'is_published' => ['nullable', 'boolean'],
            'contractor_name' => ['nullable', 'string', 'max:255'],
            'contractor_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'street' => ['nullable', 'string', 'max:255'],
            'island_parcel' => ['nullable', 'string', 'max:255'],
            'detailed_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'floor_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'square_meters' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'room_count' => ['nullable', 'string', 'max:100'],
            'bathroom_count' => ['nullable', 'integer', 'min:0', 'max:200'],
            'materials' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'string', 'max:100'],
            'end_date' => ['nullable', 'string', 'max:100'],
            'contractor_notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', 'max:100'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['file', 'image', 'max:10240'],
            'selected_photo_count' => ['nullable', 'integer', 'min:0', 'max:12'],
            'replace_photos' => ['nullable', 'boolean'],
        ]);

        $isUnpublishRequest = ($validated['action'] ?? null) === 'unpublish'
            || (array_key_exists('is_published', $validated) && ! (bool) $validated['is_published']);
        $storedPhotos = $isUnpublishRequest
            ? []
            : collect($request->file('photos', []))
                ->map(fn ($photo) => $photo->store('projects/mobile', 'public'))
                ->values()
                ->all();
        $lockIdentity = $validated['project_uuid'] ?? 'legacy-'.$validated['local_project_id'];

        try {
            [$project, $created, $oldPhotoPaths] = Cache::lock('zyd-mobile-project:'.$lockIdentity, 30)
                ->block(10, fn (): array => DB::transaction(function () use ($request, $validated, $storedPhotos): array {
                    $project = $this->resolveProject($validated);
                    $created = ! $project;

                    if (! $project) {
                        $project = new Project;
                    }

                    $this->assertIdentifiersBelongToProject($project, $validated);

                    $project->mobile_project_uuid = $validated['project_uuid'] ?? $project->mobile_project_uuid;
                    if (empty($validated['project_uuid']) || $project->mobile_local_project_id !== null) {
                        $project->mobile_local_project_id = (int) $validated['local_project_id'];
                    }

                    $isUnpublish = ($validated['action'] ?? null) === 'unpublish'
                        || (array_key_exists('is_published', $validated) && ! (bool) $validated['is_published']);

                    if ($isUnpublish) {
                        if (! $project->exists) {
                            throw ValidationException::withMessages([
                                'remote_publication_id' => 'Yayından kaldırılacak proje web sitesinde bulunamadı.',
                            ]);
                        }

                        $project->forceFill(['is_published' => false])->save();

                        return [$project, false, []];
                    }

                    $title = $this->makeTitle($validated);
                    $project->fill([
                        'title' => $title,
                        'developer_name' => $validated['contractor_name'] ?? 'Mobil uygulama',
                        'status' => $this->mapStatus($validated['status'] ?? null),
                        'city' => $this->extractAddressPart($validated['address'] ?? null, 0),
                        'district' => $this->extractAddressPart($validated['address'] ?? null, 1),
                        'neighborhood' => $this->extractAddressPart($validated['address'] ?? null, 2),
                        'latitude' => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                        'summary' => $this->makeSummary($validated),
                        'content' => $this->makeContent($validated),
                        'is_published' => true,
                        'published_at' => $project->published_at ?: now(),
                    ]);

                    if (! $project->exists) {
                        $project->slug = $this->makeUniqueSlug($title, (int) $validated['local_project_id']);
                    }

                    $replacePhotos = (bool) ($validated['replace_photos'] ?? false)
                        || $request->has('selected_photo_count')
                        || $storedPhotos !== [];
                    $oldPhotoPaths = [];

                    if ($replacePhotos) {
                        $oldPhotoPaths = $project->exists
                            ? $project->images()->pluck('image_path')->all()
                            : [];

                        if ($project->cover_image
                            && str_starts_with($project->cover_image, 'projects/mobile/')
                            && ! in_array($project->cover_image, $oldPhotoPaths, true)) {
                            $oldPhotoPaths[] = $project->cover_image;
                        }

                        $project->cover_image = $storedPhotos[0] ?? null;
                    }

                    $project->save();

                    if ($replacePhotos) {
                        $project->images()->delete();

                        foreach ($storedPhotos as $index => $path) {
                            $project->images()->create([
                                'image_path' => $path,
                                'sort_order' => $index,
                            ]);
                        }
                    }

                    return [$project, $created, array_values(array_diff($oldPhotoPaths, $storedPhotos))];
                }));
        } catch (LockTimeoutException) {
            Storage::disk('public')->delete($storedPhotos);

            return response()->json([
                'message' => 'Bu proje için başka bir yayınlama işlemi sürüyor. Lütfen kısa süre sonra aynı proje kimliğiyle tekrar deneyin.',
            ], 409);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPhotos);

            throw $exception;
        }

        if ($oldPhotoPaths !== []) {
            Storage::disk('public')->delete($oldPhotoPaths);
        }

        $project->load('images');

        $isPublished = (bool) $project->is_published;
        $message = ! $isPublished
            ? 'Proje zydemlak.com üzerinde yayından kaldırıldı.'
            : ($created
                ? 'Proje zydemlak.com üzerinde yayınlandı ve panelden yönetilebilir.'
                : 'Proje zydemlak.com üzerinde güncellendi.');

        return response()->json([
            'message' => $message,
            'publication_id' => (string) $project->id,
            'id' => $project->id,
            'project_uuid' => $project->mobile_project_uuid,
            'is_published' => $isPublished,
            'url' => route('projects.show', $project),
            'admin_url' => url('/admin/projects/'.$project->id.'/edit'),
        ], $created ? 201 : 200);
    }

    private function resolveProject(array $data): ?Project
    {
        if (! empty($data['project_uuid'])) {
            $project = Project::query()
                ->where('mobile_project_uuid', $data['project_uuid'])
                ->lockForUpdate()
                ->first();

            if ($project) {
                return $project;
            }
        }

        $remoteId = $data['remote_publication_id'] ?? $data['publication_id'] ?? null;
        if ($remoteId) {
            $project = Project::query()->lockForUpdate()->find($remoteId);

            if ($project) {
                return $project;
            }
        }

        if (empty($data['project_uuid'])) {
            return Project::query()
                ->where('mobile_local_project_id', (int) $data['local_project_id'])
                ->lockForUpdate()
                ->first();
        }

        return null;
    }

    private function assertIdentifiersBelongToProject(Project $project, array $data): void
    {
        if (! $project->exists) {
            return;
        }

        $remoteId = (int) ($data['remote_publication_id'] ?? $data['publication_id'] ?? 0);
        if ($remoteId > 0 && $remoteId !== (int) $project->getKey()) {
            throw ValidationException::withMessages([
                'remote_publication_id' => 'Web yayın kimliği kalıcı proje kimliğiyle eşleşmiyor.',
            ]);
        }

        if ($project->mobile_project_uuid
            && ! empty($data['project_uuid'])
            && $project->mobile_project_uuid !== $data['project_uuid']) {
            throw ValidationException::withMessages([
                'project_uuid' => 'Proje kimliği mevcut yayın kaydıyla eşleşmiyor.',
            ]);
        }

        if ($project->mobile_local_project_id !== null
            && (int) $project->mobile_local_project_id !== (int) $data['local_project_id']) {
            throw ValidationException::withMessages([
                'local_project_id' => 'Yerel proje kimliği mevcut yayın kaydıyla eşleşmiyor.',
            ]);
        }

        if (! empty($data['project_uuid'])
            && Project::query()
                ->where('mobile_project_uuid', $data['project_uuid'])
                ->whereKeyNot($project->getKey())
                ->exists()) {
            throw ValidationException::withMessages([
                'project_uuid' => 'Kalıcı proje kimliği başka bir web kaydına bağlı.',
            ]);
        }

        if (empty($data['project_uuid']) && Project::query()
            ->where('mobile_local_project_id', (int) $data['local_project_id'])
            ->whereKeyNot($project->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'local_project_id' => 'Yerel proje kimliği başka bir web kaydına bağlı.',
            ]);
        }
    }

    private function makeTitle(array $data): string
    {
        $contractor = trim((string) ($data['contractor_name'] ?? ''));
        $location = collect([
            $this->extractAddressPart($data['address'] ?? null, 1),
            $this->extractAddressPart($data['address'] ?? null, 2),
        ])->filter()->join(' ');

        return trim(($contractor !== '' ? $contractor : 'Mobil Proje').' '.$location) ?: 'Mobil Proje';
    }

    private function makeUniqueSlug(string $title, int $localProjectId): string
    {
        $base = Str::slug($title) ?: 'mobil-proje';
        $slug = $base.'-'.$localProjectId;
        $counter = 2;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$localProjectId.'-'.$counter++;
        }

        return $slug;
    }

    private function mapStatus(?string $status): string
    {
        return match (Str::lower(trim((string) $status))) {
            'tamamlandı', 'tamamlandi', 'completed', 'bitti', 'biten' => 'completed',
            'planlanıyor', 'planlaniyor', 'planning' => 'planning',
            default => 'construction',
        };
    }

    private function extractAddressPart(?string $address, int $index): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = collect(preg_split('/\s*\/\s*|\s*-\s*|\s*,\s*/', $address))
            ->map(fn (string $part) => trim($part))
            ->filter()
            ->values();

        return $parts->get($index);
    }

    private function makeSummary(array $data): ?string
    {
        return collect([
            $data['address'] ?? null,
            isset($data['square_meters']) ? $data['square_meters'].' m2' : null,
            isset($data['floor_count']) ? $data['floor_count'].' kat' : null,
            $data['room_count'] ?? null,
        ])->filter()->join(' | ') ?: null;
    }

    private function makeContent(array $data): string
    {
        $rows = [
            'Müteahhit / Firma' => $data['contractor_name'] ?? null,
            'Telefon' => $data['contractor_phone'] ?? null,
            'Adres' => $data['address'] ?? null,
            'Sokak' => $data['street'] ?? null,
            'Ada / Parsel' => $data['island_parcel'] ?? null,
            'Açık Adres' => $data['detailed_address'] ?? null,
            'Konum' => isset($data['latitude'], $data['longitude']) ? $data['latitude'].', '.$data['longitude'] : null,
            'Kat Sayısı' => $data['floor_count'] ?? null,
            'Alan' => isset($data['square_meters']) ? $data['square_meters'].' m2' : null,
            'Oda Sayısı' => $data['room_count'] ?? null,
            'Banyo Sayısı' => $data['bathroom_count'] ?? null,
            'Yapı Özellikleri' => $data['materials'] ?? null,
            'Başlangıç Tarihi' => $data['start_date'] ?? null,
            'Bitiş Tarihi' => $data['end_date'] ?? null,
            'Notlar' => $data['contractor_notes'] ?? null,
        ];

        $html = '<dl>';
        foreach ($rows as $label => $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $html .= '<dt><strong>'.e($label).'</strong></dt><dd>'.nl2br(e((string) $value)).'</dd>';
        }
        $html .= '</dl>';

        return $html;
    }
}

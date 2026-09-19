<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        ]);

        $coverImage = null;
        foreach ($request->file('photos', []) as $index => $photo) {
            $path = $photo->store('projects/mobile', 'public');
            if ($index === 0) {
                $coverImage = $path;
            }
        }

        $title = $this->makeTitle($validated);
        $slug = $this->makeUniqueSlug($title, (int) $validated['local_project_id']);

        $project = Project::query()->create([
            'title' => $title,
            'slug' => $slug,
            'developer_name' => $validated['contractor_name'] ?? 'Mobil uygulama',
            'status' => $this->mapStatus($validated['status'] ?? null),
            'city' => $this->extractAddressPart($validated['address'] ?? null, 0),
            'district' => $this->extractAddressPart($validated['address'] ?? null, 1),
            'neighborhood' => $this->extractAddressPart($validated['address'] ?? null, 2),
            'cover_image' => $coverImage,
            'summary' => $this->makeSummary($validated),
            'content' => $this->makeContent($validated),
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Proje zydemlak.com üzerinde yayınlandı ve panelden yönetilebilir.',
            'publication_id' => (string) $project->id,
            'id' => $project->id,
            'url' => route('projects.show', $project),
            'admin_url' => url('/admin/projects/'.$project->id.'/edit'),
        ], 201);
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

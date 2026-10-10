<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\MobileDraftImport;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileDraftController extends Controller
{
    public function store(Request $request)
    {
        $data = json_decode((string) $request->input('record'), true);
        $request->merge(['record' => $data]);
        $input = $request->validate([
            'record' => ['required', 'array'],
            'record.id' => ['required', 'string', 'max:100'],
            'record.kind' => ['required', 'in:ilan,proje'],
            'record.adSoyad' => ['nullable', 'string', 'max:255'],
            'record.telefon' => ['nullable', 'string', 'max:50'],
            'record.detay' => ['nullable', 'string', 'max:10000'],
            'record.notlar' => ['nullable', 'string', 'max:10000'],
            'record.rayic' => ['nullable', 'array'],
            'record.rayic.birimTl' => ['nullable', 'numeric', 'min:0'],
            'record.rayic.toplamTl' => ['nullable', 'numeric', 'min:0'],
            'record.tasinmazNo' => ['nullable', 'string', 'max:100'],
            'record.ilanTuru' => ['nullable', 'in:satilik,kiralik,devren_satilik,devren_kiralik'],
            'record.gayrimenkul' => ['nullable', 'in:arsa,daire,tarla,mustakil,otel,bina'],
            'record.metrekare' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'record.oda' => ['nullable', 'integer', 'between:0,200'],
            'record.salon' => ['nullable', 'integer', 'between:0,200'],
            'record.kat' => ['nullable', 'integer', 'between:-10,500'],
            'record.banyo' => ['nullable', 'integer', 'between:0,200'],
            'record.binaYasi' => ['nullable', 'integer', 'between:0,500'],
            'record.isitma' => ['nullable', 'string', 'max:100'],
            'record.cepheler' => ['nullable', 'array', 'max:8'],
            'record.cepheler.*' => ['string', 'max:50'],
            'record.durum' => ['nullable', 'in:devam,bitti'],
            'record.malzeme' => ['nullable', 'string', 'max:5000'],
            'record.baslangic' => ['nullable', 'string', 'max:100'],
            'record.bitis' => ['nullable', 'string', 'max:100'],
            'record.ekBilgiler' => ['nullable', 'array', 'max:50'],
            'record.ekBilgiler.*.ad' => ['required', 'string', 'max:100'],
            'record.ekBilgiler.*.deger' => ['required', 'string', 'max:2000'],
            'record.place' => ['present', 'array'],
            'record.place.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'record.place.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'record.place.il' => ['nullable', 'string', 'max:255'],
            'record.place.ilce' => ['nullable', 'string', 'max:255'],
            'record.place.mahalle' => ['nullable', 'string', 'max:255'],
            'record.place.tamAdres' => ['nullable', 'string', 'max:1000'],
            'record.place.sokak' => ['nullable', 'string', 'max:255'],
            'record.place.kapiNo' => ['nullable', 'string', 'max:100'],
            'record.place.ada' => ['nullable', 'string', 'max:100'],
            'record.place.parsel' => ['nullable', 'string', 'max:100'],
            'record.place.pafta' => ['nullable', 'string', 'max:100'],
            'record.place.parselAlan' => ['nullable', 'string', 'max:100'],
            'record.place.nitelik' => ['nullable', 'string', 'max:255'],
            'record.place.mevkii' => ['nullable', 'string', 'max:255'],
            'expected_revision' => ['nullable', 'string', 'max:100'],
            'photos' => ['nullable', 'array', 'max:'.config('mobile_drafts.max_photos')],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('mobile_drafts.photo_kb')],
            'videos' => ['nullable', 'array', 'max:'.config('mobile_drafts.max_videos')],
            'videos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/3gpp', 'max:'.config('mobile_drafts.video_kb')],
        ]);
        // Only validated attributes are retained; no EİDS/publication flags are accepted.
        $record = \Illuminate\Support\Arr::only($input['record'], [
            'id', 'kind', 'adSoyad', 'telefon', 'detay', 'tasinmazNo', 'ilanTuru', 'gayrimenkul',
            'metrekare', 'oda', 'salon', 'kat', 'banyo', 'binaYasi', 'isitma', 'cepheler', 'durum',
            'malzeme', 'baslangic', 'bitis', 'ekBilgiler', 'place', 'notlar', 'rayic',
        ]);
        $record['place'] = \Illuminate\Support\Arr::only($record['place'] ?? [], [
            'lat', 'lng', 'il', 'ilce', 'mahalle', 'tamAdres', 'sokak', 'kapiNo', 'ada', 'parsel',
            'pafta', 'parselAlan', 'nitelik', 'mevkii',
        ]);
        if (isset($record['rayic'])) $record['rayic'] = \Illuminate\Support\Arr::only($record['rayic'], ['birimTl', 'toplamTl']);
        $user = $request->user();
        abort_if($record['kind'] === 'proje' && $user->role !== UserRole::SUPERVISOR, 403);
        $stored = [];
        $mediaHashes = [];
        foreach (['photos', 'videos'] as $field) foreach ($request->file($field, []) as $file) {
            $mediaHashes[] = [$field, hash_file('sha256', $file->getRealPath())];
        }
        $requestHash = hash('sha256', json_encode([$record, $mediaHashes]));
        try {
            $result = Cache::lock('mobile-draft:'.hash('sha256', $user->id.'|'.$record['kind'].'|'.$record['id']), 120)
                ->block(10, function () use ($request, $input, $record, $user, $requestHash, &$stored) {
                    return DB::transaction(function () use ($request, $input, $record, $user, $requestHash, &$stored) {
                        $import = MobileDraftImport::where('user_id', $user->id)->where('kind', $record['kind'])
                            ->where('local_id', $record['id'])->lockForUpdate()->first();
                        $target = $record['kind'] === 'ilan' ? $import?->listing : $import?->project;
                        if (! $import && $record['kind'] === 'proje') {
                            $target = Project::where('mobile_project_uuid', $record['id'])->first();
                            if (! $target && ctype_digit($record['id'])) $target = Project::where('mobile_local_project_id', (int) $record['id'])->first();
                            abort_if($target && MobileDraftImport::where('project_id', $target->id)->exists(), 409, 'Bu web projesi başka bir mobil kayıtla bağlı.');
                        }
                        if ($target) $target = $target->newQuery()->whereKey($target->id)->lockForUpdate()->firstOrFail();
                        if ($import && $import->request_hash === $requestHash
                            && ($record['kind'] === 'ilan' || ($target instanceof Project && $target->is_published))
                            && $import->target_updated_at === $this->targetVersion($target)) {
                            return ['id' => $import->id, 'revision' => $this->revision($import, $target),
                                'status' => $target instanceof Project ? 'published' : 'draft', 'message' => $target instanceof Project
                                    ? 'Proje zaten aynı kayıtta yayınlandı. Yeni proje oluşturulmadı.'
                                    : 'Bu seçimler daha önce aynı kayda aktarıldı. Yeni kayıt oluşturulmadı.'];
                        }
                        // Preserve already published/verified records and edits made on the website.
                        $stageUpdate = $target instanceof Listing && ($target->eids_status !== 'pending'
                            || $target->publication_status !== 'draft' || $target->published_at);
                        if ($import && ($input['expected_revision'] ?? '') !== $this->revision($import, $target)) {
                            abort(409, 'Sitede değişiklik var. Önce site değişikliklerini alın.');
                        }
                        if ($import && ! $target) {
                            abort(409, 'Sitedeki taslak silinmiş. Yeni bir yerel kayıt oluşturun.');
                        }
                        $photos = [];
                        $videos = [];
                        foreach (['photos' => &$photos, 'videos' => &$videos] as $field => &$paths) {
                            foreach ($request->file($field, []) as $file) {
                                // Videos remain private until the website's normal media validation/publishing flow.
                                $disk = $field === 'photos' ? 'public' : 'local';
                                $path = $file->store('mobile-drafts/'.$user->id.'/'.$field, $disk);
                                $paths[] = $path;
                                $stored[] = ['disk' => $disk, 'path' => $path];
                            }
                        }
                        unset($paths);
                        if ($stageUpdate) {
                            // Hold proposed changes on the SAME import/target; web review applies them later.
                        } elseif ($record['kind'] === 'ilan') {
                            $target = $this->listing($target, $record, $user);
                            $target->images()->delete();
                            foreach ($photos as $index => $path) {
                                $target->images()->create(['image_path' => $path, 'is_main' => $index === 0, 'sort_order' => $index]);
                            }
                        } else {
                            $target = $this->project($target, $record);
                            $target->cover_image = $photos[0] ?? null;
                            $target->save();
                            $target->images()->delete();
                            foreach ($photos as $index => $path) {
                                $target->images()->create(['image_path' => $path, 'sort_order' => $index]);
                            }
                        }
                        if (! $stageUpdate && $target instanceof Listing) {
                            $target->mobile_video_paths = [];
                            $target->video_path = null;
                            $target->video_url = null;
                            $target->save();
                        }
                        $oldMedia = $import?->media ?? [];
                        $target->refresh();
                        $import ??= new MobileDraftImport;
                        $import->fill(['user_id' => $user->id, 'kind' => $record['kind'], 'local_id' => $record['id'],
                            'listing_id' => $target instanceof Listing ? $target->id : null,
                            'project_id' => $target instanceof Project ? $target->id : null,
                            'payload' => $record, 'media' => $stored, 'pending_update' => (bool) $stageUpdate,
                            'request_hash' => $requestHash, 'target_updated_at' => $this->targetVersion($target),
                            'remote_revision' => (string) Str::uuid()]);
                        if ($target instanceof Project) {
                            $this->attachReviewedVideos($target, $import, $stored);
                            $target->forceFill(['is_published' => true, 'published_at' => now()])->save();
                            $target->refresh();
                            $import->target_updated_at = $this->targetVersion($target);
                        }
                        $import->save();
                        DB::afterCommit(function () use ($oldMedia): void {
                            foreach ($oldMedia as $file) {
                                $this->deleteUnused($file);
                            }
                        });
                        return ['id' => $import->id, 'revision' => $this->revision($import, $target),
                            'status' => $target instanceof Project ? 'published' : 'draft', 'message' => $target instanceof Project
                                ? 'Proje seçtiğiniz bilgilerle sitede yayınlandı. Güncellemeler aynı projeye uygulanır.' : ($stageUpdate
                                ? 'Mevcut kayıt için güncelleme taslağı aktarıldı. Yeni ilan/proje oluşturulmadı. Web panelinden uygulayın.'
                                : 'Siteye taslak aktarıldı. Yayınlanmadı. EİDS ve yayın işlemleri web panelinden yapılır.')];
                    });
                });
        } catch (\Throwable $error) {
            foreach ($stored as $file) Storage::disk($file['disk'])->delete($file['path']);
            throw $error;
        }
        return response()->json($result);
    }

    private function listing(?Listing $listing, array $r, $user): Listing
    {
        $p = $r['place'];
        $listing ??= new Listing;
        $locked = [];
        if ($listing->exists && $listing->eids_status === 'verified') {
            $locked = \Illuminate\Support\Arr::only($listing->getAttributes(), ['eids_document_no', 'block_number', 'parcel_number', 'city_id', 'district_id', 'neighborhood_id']);
        }
        if ($listing->exists && $listing->published_at) {
            $locked = array_merge($locked, \Illuminate\Support\Arr::only($listing->getAttributes(), ['type', 'category', 'subcategory']));
        }
        if (! $listing->exists) {
            $source = match ($user->role) {
                UserRole::AGENT => 'real_estate_office', UserRole::INDIVIDUAL => 'owner', default => 'zydemlak',
            };
            $listing->fill(['user_id' => $user->id, 'created_by_id' => $user->id, 'source_type' => $source,
                'business_profile_id' => $user->role === UserRole::AGENT ? $user->businessProfile?->id : null,
                'from_who' => match ($source) { 'owner' => 'Sahibinden', 'real_estate_office' => 'Emlak Ofisinden', default => 'ZYDEMLAK' },
                'slug' => 'mobil-'.Str::uuid(), 'price' => 0, 'eids_status' => 'pending',
                'publication_status' => 'draft', 'is_active' => false]);
        }
        $listing->fill(['title' => ($r['adSoyad'] ?? 'Mobil ilan').' — '.($p['mahalle'] ?? ''), 'description' => $r['detay'] ?? '',
            'latitude' => $p['lat'] ?? null, 'longitude' => $p['lng'] ?? null, 'mapped_address' => $p['tamAdres'] ?? '', 'extra_fields' => $this->extraFields($r),
            'type' => match ($r['ilanTuru'] ?? null) {
                'kiralik' => 'Kiralık', 'devren_satilik' => 'Devren Satılık', 'devren_kiralik' => 'Devren Kiralık', default => 'Satılık',
            },
            'category' => match ($r['gayrimenkul'] ?? null) { 'arsa' => 'Arsa', 'tarla' => 'Tarla', 'villa', 'mustakil' => 'Müstakil Ev', 'isyeri', 'dukkan', 'otel' => 'İşyeri', default => 'Daire' },
            'subcategory' => match ($r['gayrimenkul'] ?? null) { 'daire' => 'Daire', 'mustakil' => 'Müstakil Ev', 'bina' => 'Bina', 'tarla' => 'Tarla', default => null },
            'square_meters' => $r['metrekare'] ?? null, 'room_count' => isset($r['oda']) ? $r['oda'].'+'.($r['salon'] ?? 0) : null,
            'floor_number' => $r['kat'] ?? null, 'building_age' => $r['binaYasi'] ?? null,
            'heating_type' => $r['isitma'] ?? null, 'facades' => $r['cepheler'] ?? [],
            'eids_document_no' => preg_replace('/\s+/', '', $r['tasinmazNo'] ?? ''),
            'city_id' => $p['il'] ?? '', 'district_id' => $p['ilce'] ?? '', 'neighborhood_id' => $p['mahalle'] ?? '',
            'block_number' => $p['ada'] ?? '', 'parcel_number' => $p['parsel'] ?? '', 'sheet_number' => $p['pafta'] ?? '']);
        // Official fields remain governed by the existing EİDS flow, even when omitted from a mobile update.
        $listing->fill($locked);
        $listing->save();
        return $listing;
    }

    private function project(?Project $project, array $r): Project
    {
        $p = $r['place'];
        $project ??= new Project;
        if (! $project->exists) {
            $project->slug = 'mobil-'.Str::uuid();
            $project->is_published = false;
            if (Str::isUuid($r['id'])) $project->mobile_project_uuid = $r['id'];
        }
        $project->fill(['title' => ($r['adSoyad'] ?? 'Mobil proje').' — '.($p['mahalle'] ?? ''), 'developer_name' => $r['adSoyad'] ?? '',
            'status' => ($r['durum'] ?? '') === 'bitti' ? 'completed' : 'construction',
            'city' => $p['il'] ?? '', 'district' => $p['ilce'] ?? '', 'neighborhood' => $p['mahalle'] ?? '',
            'latitude' => $p['lat'] ?? null, 'longitude' => $p['lng'] ?? null, 'summary' => $p['tamAdres'] ?? '',
            'content' => nl2br(e($r['detay'] ?? '')), 'extra_fields' => $this->extraFields($r)]);
        $project->save();
        return $project;
    }

    public function index(Request $request)
    {
        return response()->json(['records' => MobileDraftImport::where('user_id', $request->user()->id)
            ->with(['listing', 'project'])->get()->map(function ($import) {
                $target = $import->kind === 'ilan' ? $import->listing : $import->project;
                if (! $target) return null;
                $r = $import->payload;
                if ($import->pending_update) return ['local_id' => $import->local_id, 'kind' => $import->kind,
                    'record' => $r, 'revision' => $this->revision($import, $target)];
                if ($target instanceof Project) {
                    if (array_key_exists('adSoyad', $r)) $r['adSoyad'] = $target->developer_name;
                    if (array_key_exists('detay', $r)) $r['detay'] = html_entity_decode(strip_tags(str_replace(['<br>', '<br />'], "\n", $target->content ?? '')));
                    $r['ekBilgiler'] = array_values(array_filter($target->extra_fields ?? [], fn ($field) => ! isset($field['_mobileKey'])));
                    $r['durum'] = $target->status === 'completed' ? 'bitti' : 'devam';
                    $r['place'] = $r['place'] ?? [];
                    if ($target->latitude !== null && $target->longitude !== null) $r['place'] = array_replace($r['place'],
                        ['lat' => (float) $target->latitude, 'lng' => (float) $target->longitude]);
                    foreach (['il' => 'city', 'ilce' => 'district', 'mahalle' => 'neighborhood', 'tamAdres' => 'summary'] as $field => $attribute) {
                        if (array_key_exists($field, $r['place'])) $r['place'][$field] = $target->$attribute ?? '';
                    }
                } else {
                    if (array_key_exists('detay', $r)) $r['detay'] = strip_tags($target->description ?? '');
                    if (array_key_exists('tasinmazNo', $r)) $r['tasinmazNo'] = $target->eids_document_no ?? '';
                    $r['ekBilgiler'] = array_values(array_filter($target->extra_fields ?? [], fn ($field) => ! isset($field['_mobileKey'])));
                    if ($target->latitude !== null && $target->longitude !== null) {
                        $r['place'] = array_replace($r['place'], ['lat' => (float) $target->latitude,
                            'lng' => (float) $target->longitude, 'tamAdres' => $target->mapped_address ?? $r['place']['tamAdres']]);
                    }
                }
                return ['local_id' => $import->local_id, 'kind' => $import->kind, 'record' => $r,
                    'revision' => $this->revision($import, $target)];
            })->filter()->values()]);
    }

    private function revision(MobileDraftImport $import, $target): string
    {
        return hash('sha256', $import->remote_revision.'|'.$this->targetVersion($target));
    }

    private function targetVersion($target): string
    {
        $attributes = $target?->getAttributes() ?? [];
        ksort($attributes);
        return hash('sha256', json_encode($attributes));
    }

    private function extraFields(array $r): array
    {
        $fields = $r['ekBilgiler'] ?? [];
        foreach (['telefon' => 'Telefon', 'notlar' => 'Notlar', 'malzeme' => 'Malzeme', 'baslangic' => 'Başlangıç', 'bitis' => 'Bitiş', 'banyo' => 'Banyo', 'oda' => 'Oda', 'salon' => 'Salon', 'kat' => 'Kat', 'metrekare' => 'Metrekare'] as $key => $label) {
            if (isset($r[$key]) && $r[$key] !== '') $fields[] = ['ad' => $label, 'deger' => (string) $r[$key], '_mobileKey' => $key];
        }
        foreach (['ada' => 'Ada', 'parsel' => 'Parsel', 'parselAlan' => 'Parsel alanı', 'sokak' => 'Sokak', 'kapiNo' => 'Kapı no'] as $key => $label) {
            if (! empty($r['place'][$key])) $fields[] = ['ad' => $label, 'deger' => $r['place'][$key], '_mobileKey' => 'place.'.$key];
        }
        foreach (['gayrimenkul' => 'Gayrimenkul türü', 'binaYasi' => 'Bina yaşı', 'isitma' => 'Isıtma', 'cepheler' => 'Cepheler'] as $key => $label) {
            if (isset($r[$key])) $fields[] = ['ad' => $label, 'deger' => is_array($r[$key]) ? implode(', ', $r[$key]) : (string) $r[$key], '_mobileKey' => $key];
        }
        foreach (['il' => 'İl', 'ilce' => 'İlçe', 'mahalle' => 'Mahalle / köy', 'tamAdres' => 'Açık adres', 'nitelik' => 'Nitelik', 'pafta' => 'Pafta', 'mevkii' => 'Mevkii'] as $key => $label) {
            if (! empty($r['place'][$key])) $fields[] = ['ad' => $label, 'deger' => $r['place'][$key], '_mobileKey' => 'place.'.$key];
        }
        if (isset($r['rayic']['birimTl'])) $fields[] = ['ad' => 'Rayiç TL/m²', 'deger' => (string) $r['rayic']['birimTl'], '_mobileKey' => 'rayic.birimTl'];
        if (isset($r['rayic']['toplamTl'])) $fields[] = ['ad' => 'Toplam rayiç TL', 'deger' => (string) $r['rayic']['toplamTl'], '_mobileKey' => 'rayic.toplamTl'];
        return $fields;
    }

    private function deleteUnused(array $file): void
    {
        if ($file['disk'] === 'public' && (\App\Models\ListingImage::where('image_path', $file['path'])->exists()
            || \App\Models\ProjectImage::where('image_path', $file['path'])->exists()
            || Project::where('cover_image', $file['path'])->exists())) return;
        Storage::disk($file['disk'])->delete($file['path']);
    }

    public function applyUpdate(MobileDraftImport $import): void
    {
        abort_unless(auth()->user()?->role === UserRole::SUPERVISOR, 403);
        DB::transaction(function () use ($import): void {
            $import = MobileDraftImport::whereKey($import->id)->lockForUpdate()->firstOrFail();
            $applyFields = $import->pending_update;
            if (! $applyFields && ! collect($import->media)->contains('disk', 'local')) return;
            $r = $import->payload;
            $target = $import->kind === 'ilan' ? $import->listing : $import->project;
            abort_unless($target, 409);
            $target = $target->newQuery()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            if ($applyFields && $import->target_updated_at !== $this->targetVersion($target)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['record' => 'Web kaydı aktarım sonrasında değişmiş. Önce telefonda site değişikliklerini alın ve seçimleri yeniden aktarın.']);
            }
            $oldPhotos = $target->images()->pluck('image_path')->all();
            if ($applyFields) $target = $import->kind === 'ilan' ? $this->listing($target, $r, $import->user) : $this->project($target, $r);
            $photos = collect($import->media)->where('disk', 'public')->pluck('path')->values();
            if ($applyFields) $target->images()->delete();
            foreach ($applyFields ? $photos : [] as $i => $path) {
                $attributes = ['image_path' => $path, 'sort_order' => $i];
                if ($target instanceof Listing) $attributes['is_main'] = $i === 0;
                $target->images()->create($attributes);
            }
            if ($target instanceof Project && $applyFields) { $target->cover_image = $photos->first(); $target->save(); }
            $this->attachReviewedVideos($target, $import);
            $target->refresh();
            $import->pending_update = false; $import->remote_revision = (string) Str::uuid();
            $import->target_updated_at = $this->targetVersion($target); $import->save();
            DB::afterCommit(function () use ($oldPhotos): void { foreach ($oldPhotos as $path) $this->deleteUnused(['disk' => 'public', 'path' => $path]); });
        });
    }

    private function attachReviewedVideos($target, MobileDraftImport $import, ?array &$createdFiles = null): void
    {
        $files = collect($import->media)->where('disk', 'local')->map(function ($file) {
            return new \Illuminate\Http\UploadedFile(Storage::disk('local')->path($file['path']), basename($file['path']), null, null, true);
        });
        foreach ($files as $file) {
            $rules = $target instanceof Listing ? ['file', new \App\Rules\ListingVideo]
                : ['file', 'mimetypes:'.implode(',', config('projects.video.mime_types')), 'max:'.config('projects.video.max_size_kb')];
            \Illuminate\Support\Facades\Validator::make(['video' => $file], ['video' => $rules])->validate();
        }
        $paths = [];
        try {
            foreach ($files as $file) {
                $path = $file->store($target instanceof Listing ? 'listings/videos/mobile' : 'projects/videos/mobile', 'public');
                $paths[] = $path;
                if ($createdFiles !== null) $createdFiles[] = ['disk' => 'public', 'path' => $path];
            }
            $old = array_unique(array_filter(array_merge($target->mobile_video_paths ?? [], [$target->video_path])));
            $target->mobile_video_paths = $paths;
            $target->video_path = $paths[0] ?? null;
            $target->video_url = null;
            $target->save();
            DB::afterCommit(function () use ($old): void {
                foreach ($old as $path) {
                    if (! Listing::where('video_path', $path)->exists() && ! Project::where('video_path', $path)->exists()) Storage::disk('public')->delete($path);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($paths);
            throw $e;
        }
    }
}

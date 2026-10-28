<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingFormDraft;
use App\Rules\ListingVideo;
use App\Support\ListingFormData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;

class ListingFormDraftService
{
    public function save(string $id, int $version, array $data, ?Listing $listing = null): ListingFormDraft
    {
        return DB::transaction(function () use ($id, $version, $data, $listing) {
            $draft = ListingFormDraft::query()->whereKey($id)->lockForUpdate()->first();
            if ($draft) {
                abort_unless($draft->user_id === auth()->id() && $draft->listing_id === $listing?->id, 403);
                if ($draft->version !== $version) {
                    throw ValidationException::withMessages(['draft' => 'Taslak başka bir sekmede güncellendi. Bu sayfadaki girdiler korunuyor; diğer sekmeyi kontrol edin.']);
                }
            } else {
                if ($version !== 0) {
                    throw ValidationException::withMessages(['draft' => 'Taslak bulunamadı. Sayfayı yenilemeden girdilerinizi kontrol edin.']);
                }
                $draft = new ListingFormDraft(['id' => $id, 'user_id' => auth()->id(), 'listing_id' => $listing?->id, 'version' => 0]);
            }
            $data = Arr::only($data, ListingFormData::FIELDS);
            Validator::make($data, [
                'title' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:200000'],
                'private_note' => ['nullable', 'string', 'max:10000'], 'form_details' => ['nullable', 'array'],
                'form_details.features' => ['nullable', 'array'], 'form_details.features.*' => ['array'],
                'form_details.features.*.values' => ['nullable', 'array', 'max:100'],
                'form_details.features.*.values.*' => ['string', 'max:150'],
                'form_details.features.*.declined' => ['nullable', 'boolean'],
            ])->validate();
            unset($data['eids_status'], $data['from_who']);
            foreach (['uploaded_images', 'video_path'] as $field) {
                $files = $data[$field] ?? [];
                $files = is_array($files) ? $files : array_filter([$files]);
                if (count($files) > ($field === 'uploaded_images' ? config('listings.photos.max_files') : 1)) {
                    throw ValidationException::withMessages(['data.'.$field => 'Yükleme adedi sınırı aşıldı.']);
                }
                $saved = [];
                foreach ($files as $key => $file) {
                    if ($file instanceof UploadedFile) {
                        $saved[$key] = $field === 'uploaded_images' ? $this->storeImage($file) : $this->storeVideo($file);
                    } elseif (is_string($file) && $this->ownsPath($file, $field, $draft, $listing)) {
                        $saved[$key] = $file;
                    } else {
                        throw ValidationException::withMessages(['data.'.$field => 'Yükleme bu taslağa veya ilana ait değil.']);
                    }
                }
                $data[$field] = $saved;
            }
            // Only ordinary form inputs are stored. OTPs, tokens and service responses have no payload key.
            $draft->payload = $data;
            $draft->version++;
            $draft->save();

            return $draft;
        });
    }

    public function ownsPath(string $path, string $field, ?ListingFormDraft $draft, ?Listing $listing): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }
        $existing = $field === 'uploaded_images' ? ($listing?->images()->pluck('image_path')->all() ?? []) : array_filter([$listing?->video_path]);
        if (in_array($path, $existing, true)) {
            return true;
        }
        $paths = $draft?->user_id === auth()->id() ? ($draft->payload[$field] ?? []) : [];

        return in_array($path, is_array($paths) ? $paths : [$paths], true) && Storage::disk('public')->exists($path);
    }

    public function storeImage(UploadedFile $file): string
    {
        Validator::make(['photo' => $file], ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('listings.photos.max_size_kb'), 'dimensions:max_width=12000,max_height=12000']])->validate();
        $image = (new ImageManager(new Driver))->read($file->getRealPath());
        // Re-encoding strips EXIF/GPS before a file ever becomes publicly accessible.
        $path = 'listings/images/'.auth()->id().'/'.hash('sha256', $file->getFilename().auth()->id()).'.jpg';
        Storage::disk('public')->put($path, (string) $image->toJpeg(95));

        return $path;
    }

    public function storeVideo(UploadedFile $file): string
    {
        Validator::make(['video' => $file], ['video' => ['required', 'file', 'max:'.config('listings.video.max_size_kb'), new ListingVideo]])->validate();
        if (in_array($file->getMimeType(), ['video/quicktime', 'video/3gpp'], true)) {
            $output = tempnam(sys_get_temp_dir(), 'zyd-video-');
            try {
                $process = new Process([
                    config('listings.video.ffmpeg_binary'), '-v', 'error', '-nostdin', '-y',
                    '-protocol_whitelist', 'file,pipe', '-i', $file->getRealPath(),
                    '-map_metadata', '-1', '-map', '0:v:0', '-map', '0:a?',
                    '-c:v', 'libx264', '-c:a', 'aac', '-movflags', '+faststart', '-f', 'mp4', $output,
                ]);
                $process->setTimeout(120)->mustRun();
                $path = 'listings/videos/'.auth()->id().'/'.Str::uuid().'.mp4';
                Storage::disk('public')->put($path, file_get_contents($output));
                $file->store('listing-video-originals/'.auth()->id(), 'local');

                return $path;
            } catch (\Throwable) {
                throw ValidationException::withMessages(['data.video_path' => 'MOV/3GP dönüştürülemedi. Sunucuda FFmpeg kurulumu gerekir; MP4 (H.264) veya WebM yükleyebilirsiniz.']);
            } finally {
                if (is_file($output)) {
                    unlink($output);
                }
            }
        }

        return $file->store('listings/videos/'.auth()->id(), 'public');
    }
}

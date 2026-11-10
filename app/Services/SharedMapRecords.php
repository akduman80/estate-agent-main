<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class SharedMapRecords
{
    public static function records(bool $admin = false): array
    {
        $projects = Project::with('images');
        $listings = Listing::with('images');
        if (! $admin) {
            $projects->published();
            $listings->published()->where(fn ($q) => $q->whereNull('form_details->hide_location')->orWhere('form_details->hide_location', false));
        }

        return $projects->whereNotNull('latitude')->whereNotNull('longitude')->get()->map(fn ($p) => [
            'id' => 'proje-'.$p->id, 'kind' => 'proje', 'title' => $p->title, 'location' => $p->location,
            'latitude' => (float) $p->latitude, 'longitude' => (float) $p->longitude,
            'url' => $admin ? '/admin/projects/'.$p->id.'/edit' : route('projects.show', $p),
            'photos' => collect([$p->cover_image])->merge($p->images->pluck('image_path'))->filter()->unique()->map(fn ($path) => Storage::disk('public')->url($path))->values()->all(),
            'videos' => collect([$p->video_path])->merge($p->mobile_video_paths ?? [])->filter()->unique()->map(fn ($path) => Storage::disk('public')->url($path))->values()->all(),
            'youtube_id' => $p->youtube_video_id,
        ])->merge($listings->whereNotNull('latitude')->whereNotNull('longitude')->get()->map(fn ($l) => [
            'id' => 'ilan-'.$l->id, 'kind' => 'ilan', 'title' => $l->title, 'location' => strip_tags($l->mapped_address ?? ''),
            'latitude' => (float) $l->latitude, 'longitude' => (float) $l->longitude,
            'url' => $admin ? '/portal/listings/'.$l->id.'/edit' : route('listings.show', $l),
            'photos' => $l->images->pluck('image_path')->filter()->map(fn ($path) => Storage::disk('public')->url($path))->values()->all(),
            'videos' => collect([$l->video_path])->merge($l->mobile_video_paths ?? [])->filter()->unique()->map(fn ($path) => Storage::disk('public')->url($path))->values()->all(),
        ]))->values()->all();
    }
}

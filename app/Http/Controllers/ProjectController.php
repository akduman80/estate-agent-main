<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
        ]);

        $publishedProjects = Project::query()->published();
        $filterOptions = [
            'cities' => (clone $publishedProjects)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'districts' => (clone $publishedProjects)->whereNotNull('district')->distinct()->orderBy('district')->pluck('district'),
            'neighborhoods' => (clone $publishedProjects)->whereNotNull('neighborhood')->distinct()->orderBy('neighborhood')->pluck('neighborhood'),
        ];

        $filteredProjects = Project::query()
            ->published()
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filters['district'] ?? null, fn (Builder $query, string $district) => $query->where('district', $district))
            ->when($filters['neighborhood'] ?? null, fn (Builder $query, string $neighborhood) => $query->where('neighborhood', $neighborhood));

        $projects = (clone $filteredProjects)
            ->with('images')
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $mapProjects = (clone $filteredProjects)
            ->with('images')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Project $project): array => [
                'title' => $project->title,
                'location' => $project->location,
                'summary' => $project->summary,
                'latitude' => (float) $project->latitude,
                'longitude' => (float) $project->longitude,
                'image_urls' => collect([$project->cover_image])
                    ->merge($project->images->pluck('image_path'))
                    ->filter()
                    ->unique()
                    ->map(fn (string $path): string => Storage::disk('public')->url($path))
                    ->values(),
                'video_url' => $project->video_path ? Storage::disk('public')->url($project->video_path) : null,
                'youtube_id' => $project->youtube_video_id,
                'url' => route('projects.show', $project),
            ])
            ->values();

        return view('projects.index', [
            'projects' => $projects,
            'mapProjects' => $mapProjects,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless(Project::query()->published()->whereKey($project->getKey())->exists(), 404);

        $project->load('images');

        return view('projects.show', compact('project'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->with('images')
            ->published()
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->paginate(12);

        $mapProjects = Project::query()
            ->with('images')
            ->published()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (Project $project): array => [
                'title' => $project->title,
                'location' => $project->location,
                'summary' => $project->summary,
                'latitude' => (float) $project->latitude,
                'longitude' => (float) $project->longitude,
                'image_url' => $project->cover_image ? Storage::disk('public')->url($project->cover_image) : null,
                'url' => route('projects.show', $project),
            ])
            ->values();

        return view('projects.index', [
            'projects' => $projects,
            'mapProjects' => $mapProjects,
            'googleMapsKey' => config('services.google_maps.browser_key'),
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless(Project::query()->published()->whereKey($project->getKey())->exists(), 404);

        $project->load('images');

        return view('projects.show', compact('project'));
    }
}

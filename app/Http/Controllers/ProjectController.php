<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()->published()->orderByDesc('is_featured')->latest('published_at')->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        abort_unless(Project::query()->published()->whereKey($project->getKey())->exists(), 404);

        return view('projects.show', compact('project'));
    }
}

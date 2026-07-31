<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Project::class);

        $projects = $this->authenticatedUser($request)
            ->projects()
            ->select(['id', 'user_id', 'name', 'description', 'created_at', 'updated_at'])
            ->withCount(['nodes', 'dependencies'])
            ->latest()
            ->get();

        return Inertia::render('projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Project::class);

        return Inertia::render('projects/Create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->authenticatedUser($request)
            ->projects()
            ->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Project created.',
        ]);

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $project->load([
            'nodes' => fn ($query) => $query->latest(),
            'dependencies' => fn ($query) => $query->latest(),
            'dependencies.sourceNode',
            'dependencies.targetNode',
        ]);

        return Inertia::render('projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Project updated.',
        ]);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Project deleted.',
        ]);

        return to_route('projects.index');
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }
}

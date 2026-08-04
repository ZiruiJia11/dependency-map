<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDependencyRequest;
use App\Http\Requests\UpdateDependencyRequest;
use App\Models\Dependency;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DependencyController extends Controller
{
    /** Create a validated directed connection inside the current project. */
    public function store(
        StoreDependencyRequest $request,
        Project $project,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $project->dependencies()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dependency created.',
        ]);

        return to_route('projects.show', $project);
    }

    /** Update an existing connection without allowing its project to change. */
    public function update(
        UpdateDependencyRequest $request,
        Project $project,
        Dependency $dependency,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $dependency->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dependency updated.',
        ]);

        return to_route('projects.show', $project);
    }

    /** Delete only within the current project's dependency relationship. */
    public function destroy(
        Project $project,
        Dependency $dependency,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $project->dependencies()
            ->whereKey($dependency->getKey())
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Dependency deleted.',
        ]);

        return to_route('projects.show', $project);
    }
}

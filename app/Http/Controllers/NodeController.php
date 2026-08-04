<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNodeRequest;
use App\Http\Requests\UpdateNodeRequest;
use App\Models\Node;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NodeController extends Controller
{
    /** Create a node through the project relationship so project_id is trusted. */
    public function store(
        StoreNodeRequest $request,
        Project $project,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $project->nodes()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Node created.',
        ]);

        return to_route('projects.show', $project);
    }

    /** Update a node after nested binding and request validation have succeeded. */
    public function update(
        UpdateNodeRequest $request,
        Project $project,
        Node $node,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $node->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Node updated.',
        ]);

        return to_route('projects.show', $project);
    }

    /** Delete only within the current project's node relationship. */
    public function destroy(
        Project $project,
        Node $node,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $project->nodes()
            ->whereKey($node->getKey())
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Node deleted.',
        ]);

        return to_route('projects.show', $project);
    }
}

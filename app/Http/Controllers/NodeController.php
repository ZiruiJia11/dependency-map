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

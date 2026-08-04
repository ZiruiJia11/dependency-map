<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /** Any authenticated user may view their ownership-filtered project list. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Only the owner may view a specific project. */
    public function view(User $user, Project $project): bool
    {
        return $user->getKey() === $project->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /** Project ownership is the boundary for project, node, and dependency edits. */
    public function update(User $user, Project $project): bool
    {
        return $user->getKey() === $project->user_id;
    }

    /** Only the owner may delete the project and its cascading data. */
    public function delete(User $user, Project $project): bool
    {
        return $user->getKey() === $project->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}

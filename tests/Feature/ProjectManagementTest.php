<?php

use App\Models\Project;
use App\Models\User;

// Domain-level tests document the project ownership boundary used by the policy.
test('a project belongs to a user', function () {
    $project = Project::factory()->create();

    expect($project->user)->toBeInstanceOf(User::class)
        ->and($project->user->is($project->user))->toBeTrue();
});

test('users may only manage their own projects', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect($owner->can('view', $project))->toBeTrue()
        ->and($owner->can('update', $project))->toBeTrue()
        ->and($owner->can('delete', $project))->toBeTrue()
        ->and($otherUser->can('view', $project))->toBeFalse()
        ->and($otherUser->can('update', $project))->toBeFalse()
        ->and($otherUser->can('delete', $project))->toBeFalse();
});

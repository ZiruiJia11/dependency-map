<?php

use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Project feature tests cover authentication, ownership, validation, and CRUD responses.
test('guests cannot access projects', function () {
    $this->get(route('projects.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can create projects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Dependency Map',
        'description' => 'Map important project dependencies.',
    ]);

    $project = Project::query()->sole();

    expect($project->user_id)->toBe($user->id)
        ->and($project->name)->toBe('Dependency Map');

    $response->assertRedirect(route('projects.show', $project));
});

test('project data is validated', function (array $data, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), $data)
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['description' => 'No name'], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'description too long' => [
        ['name' => 'Valid name', 'description' => str_repeat('a', 5001)],
        'description',
    ],
]);

test('users only see their own projects', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $ownProject = Project::factory()->for($user)->create();
    Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/Index', false)
            ->has('projects', 1)
            ->where('projects.0.id', $ownProject->id));
});

test('users cannot view another users project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

test('owners can update their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)->put(route('projects.update', $project), [
        'name' => 'Updated project',
        'description' => null,
    ]);

    expect($project->fresh()->name)->toBe('Updated project');
    $response->assertRedirect(route('projects.show', $project));
});

test('owners can delete their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->delete(route('projects.destroy', $project));

    $this->assertModelMissing($project);
    $response->assertRedirect(route('projects.index'));
});

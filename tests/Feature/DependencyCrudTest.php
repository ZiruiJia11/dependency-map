<?php

use App\Models\Dependency;
use App\Models\Node;
use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;

// Dependency tests exercise the complete directed-connection security boundary.
test('project owners can create dependencies', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($project)
        ->createOne();

    $response = actingAs($user)
        ->post(route('projects.dependencies.store', $project), [
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'description' => 'The API stores data in the database.',
        ]);

    $dependency = Dependency::query()->sole();

    expect($dependency->project_id)->toBe($project->id)
        ->and($dependency->source_node_id)->toBe($sourceNode->id)
        ->and($dependency->target_node_id)->toBe($targetNode->id)
        ->and($dependency->description)
        ->toBe('The API stores data in the database.');

    $response->assertRedirect(route('projects.show', $project));
});

test('users cannot create dependencies in another users project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($project)
        ->createOne();

    actingAs($user)
        ->post(route('projects.dependencies.store', $project), [
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'description' => null,
        ])
        ->assertForbidden();

    expect(Dependency::query()->exists())->toBeFalse();
});

test('a node cannot depend on itself', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $node */
    $node = Node::factory()
        ->for($project)
        ->createOne();

    actingAs($user)
        ->post(route('projects.dependencies.store', $project), [
            'source_node_id' => $node->id,
            'target_node_id' => $node->id,
            'description' => null,
        ])
        ->assertSessionHasErrors('target_node_id');

    expect(Dependency::query()->exists())->toBeFalse();
});

test('dependencies cannot connect nodes from another project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Project $otherProject */
    $otherProject = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $externalNode */
    $externalNode = Node::factory()
        ->for($otherProject)
        ->createOne();

    actingAs($user)
        ->post(route('projects.dependencies.store', $project), [
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $externalNode->id,
            'description' => null,
        ])
        ->assertSessionHasErrors('target_node_id');

    expect(Dependency::query()->exists())->toBeFalse();
});

test('duplicate dependencies cannot be created', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($project)
        ->createOne();

    Dependency::factory()
        ->for($project)
        ->createOne([
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

    actingAs($user)
        ->post(route('projects.dependencies.store', $project), [
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'description' => null,
        ])
        ->assertSessionHasErrors([
            'target_node_id' => 'This dependency already exists.',
        ]);

    expect(Dependency::query()->get())->toHaveCount(1);
});

test('project owners can update dependencies', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $originalTarget */
    $originalTarget = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $newTarget */
    $newTarget = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Dependency $dependency */
    $dependency = Dependency::factory()
        ->for($project)
        ->createOne([
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $originalTarget->id,
            'description' => 'Original description.',
        ]);

    $response = actingAs($user)
        ->put(
            route('projects.dependencies.update', [
                $project,
                $dependency,
            ]),
            [
                'source_node_id' => $sourceNode->id,
                'target_node_id' => $newTarget->id,
                'description' => 'Updated description.',
            ],
        );

    $dependency->refresh();

    expect($dependency->source_node_id)->toBe($sourceNode->id)
        ->and($dependency->target_node_id)->toBe($newTarget->id)
        ->and($dependency->description)->toBe('Updated description.');

    $response->assertRedirect(route('projects.show', $project));
});

test('a dependency can be updated without changing its nodes', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Dependency $dependency */
    $dependency = Dependency::factory()
        ->for($project)
        ->createOne([
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'description' => 'Original description.',
        ]);

    actingAs($user)
        ->put(
            route('projects.dependencies.update', [
                $project,
                $dependency,
            ]),
            [
                'source_node_id' => $sourceNode->id,
                'target_node_id' => $targetNode->id,
                'description' => 'Updated description.',
            ],
        )
        ->assertRedirect(route('projects.show', $project));

    $dependency->refresh();

    expect($dependency->description)->toBe('Updated description.');
});

test('dependencies cannot be accessed through another project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Project $otherProject */
    $otherProject = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($otherProject)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($otherProject)
        ->createOne();

    /** @var Dependency $dependency */
    $dependency = Dependency::factory()
        ->for($otherProject)
        ->createOne([
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

    actingAs($user)
        ->put(
            route('projects.dependencies.update', [
                $project,
                $dependency,
            ]),
            [
                'source_node_id' => $sourceNode->id,
                'target_node_id' => $targetNode->id,
                'description' => null,
            ],
        )
        ->assertNotFound();
});

test('project owners can delete dependencies', function () {
    /** @var User $user */
    $user = User::factory()->createOne();

    /** @var Project $project */
    $project = Project::factory()
        ->for($user)
        ->createOne();

    /** @var Node $sourceNode */
    $sourceNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Node $targetNode */
    $targetNode = Node::factory()
        ->for($project)
        ->createOne();

    /** @var Dependency $dependency */
    $dependency = Dependency::factory()
        ->for($project)
        ->createOne([
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

    $response = actingAs($user)
        ->delete(
            route('projects.dependencies.destroy', [
                $project,
                $dependency,
            ]),
        );

    expect(
        Dependency::query()
            ->whereKey($dependency->id)
            ->exists(),
    )->toBeFalse();

    $response->assertRedirect(route('projects.show', $project));
});

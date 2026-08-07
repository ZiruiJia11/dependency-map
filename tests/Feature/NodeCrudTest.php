<?php

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

// Node CRUD tests verify nested project access, enum validation, and persistence.
test('project owners can create nodes', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();

    $response = actingAs($user)
        ->post(route('projects.nodes.store', $project), [
            'name' => 'Production database',
            'description' => 'Primary PostgreSQL database.',
            'type' => NodeType::Database->value,
            'status' => NodeStatus::Normal->value,
        ]);

    $node = Node::query()->sole();

    expect($node->project_id)->toBe($project->id)
        ->and($node->name)->toBe('Production database')
        ->and($node->type)->toBe(NodeType::Database)
        ->and($node->status)->toBe(NodeStatus::Normal);

    $response->assertRedirect(route('projects.show', $project));
});

test('users cannot create nodes in another users project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->create();

    actingAs($user)
        ->post(route('projects.nodes.store', $project), [
            'name' => 'Unauthorized node',
            'type' => NodeType::Task->value,
            'status' => NodeStatus::Normal->value,
        ])
        ->assertForbidden();

    expect(Node::query()->exists())->toBeFalse();
});

test('node data is validated', function (array $data, string $field) {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();

    actingAs($user)
        ->post(route('projects.nodes.store', $project), $data)
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [
        [
            'type' => NodeType::Task->value,
            'status' => NodeStatus::Normal->value,
        ],
        'name',
    ],
    'invalid type' => [
        [
            'name' => 'Invalid node',
            'type' => 'invalid',
            'status' => NodeStatus::Normal->value,
        ],
        'type',
    ],
    'invalid status' => [
        [
            'name' => 'Invalid node',
            'type' => NodeType::Task->value,
            'status' => 'invalid',
        ],
        'status',
    ],
]);

test('project owners can update nodes', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $node = Node::factory()->for($project)->create();

    $response = actingAs($user)
        ->put(route('projects.nodes.update', [$project, $node]), [
            'name' => 'Updated service',
            'description' => null,
            'type' => NodeType::Service->value,
            'status' => NodeStatus::AtRisk->value,
        ]);

    $node->refresh();

    expect($node->name)->toBe('Updated service')
        ->and($node->type)->toBe(NodeType::Service)
        ->and($node->status)->toBe(NodeStatus::AtRisk);

    $response->assertRedirect(route('projects.show', $project));
});

test('nodes cannot be accessed through another project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();
    $node = Node::factory()->for($otherProject)->create();

    actingAs($user)
        ->put(route('projects.nodes.update', [$project, $node]), [
            'name' => 'Moved node',
            'type' => NodeType::Task->value,
            'status' => NodeStatus::Normal->value,
        ])
        ->assertNotFound();
});

test('project owners can delete nodes', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $node = Node::factory()->for($project)->create();

    $response = actingAs($user)
        ->delete(route('projects.nodes.destroy', [$project, $node]));

    assertModelMissing($node);
    $response->assertRedirect(route('projects.show', $project));
});

test('project owners can update node positions', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $node = Node::factory()->for($project)->create();

    $response = actingAs($user)
        ->patch(route('projects.nodes.position', [$project, $node]), [
            'position_x' => 420.5,
            'position_y' => 120.25,
        ]);

    $node->refresh();

    expect($node->position_x)->toBe('420.5000')
        ->and($node->position_y)->toBe('120.2500');

    $response->assertRedirect(route('projects.show', $project));
});

test('node positions are validated', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $node = Node::factory()->for($project)->create();

    actingAs($user)
        ->patch(route('projects.nodes.position', [$project, $node]), [
            'position_x' => 1201,
            'position_y' => -1,
        ])
        ->assertSessionHasErrors([
            'position_x',
            'position_y',
        ]);

    $node->refresh();

    expect($node->position_x)->toBeNull()
        ->and($node->position_y)->toBeNull();

});

test('nodes cannot be repositioned through another project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();
    $node = Node::factory()->for($otherProject)->create();

    actingAs($user)
        ->patch(route('projects.nodes.position', [$project, $node]), [
            'position_x' => 420,
            'position_y' => 120,
        ])
        ->assertNotFound();

    $node->refresh();

    expect($node->position_x)->toBeNull()
        ->and($node->position_y)->toBeNull();
});

test('project owners can reset all node positions', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();

    Node::factory()
        ->count(2)
        ->for($project)
        ->create([
            'position_x' => 420,
            'position_y' => 120,
        ]);

    $otherNode = Node::factory()
        ->for($otherProject)
        ->create([
            'position_x' => 300,
            'position_y' => 100,
        ]);

    $response = actingAs($user)
        ->delete(route('projects.nodes.positions.destroy', $project));

    expect(
        Node::query()
            ->where('project_id', $project->id)
            ->whereNotNull('position_x', 'and')
            ->exists(),
    )->toBeFalse()
        ->and(
            Node::query()
                ->where('project_id', $project->id)
                ->whereNotNull('position_y', 'and')
                ->exists(),
        )->toBeFalse();

    $otherNode->refresh();

    expect($otherNode->position_x)->toBe('300.0000')
        ->and($otherNode->position_y)->toBe('100.0000');

    $response->assertRedirect(route('projects.show', $project));
});

test('users cannot reset node positions in another users project', function () {
    /** @var User $user */
    $user = User::factory()->createOne();
    $project = Project::factory()->create();

    $node = Node::factory()
        ->for($project)
        ->create([
            'position_x' => 420,
            'position_y' => 120,
        ]);

    actingAs($user)
        ->delete(route('projects.nodes.positions.destroy', $project))
        ->assertForbidden();

    $node->refresh();

    expect($node->position_x)->toBe('420.0000')
        ->and($node->position_y)->toBe('120.0000');
});
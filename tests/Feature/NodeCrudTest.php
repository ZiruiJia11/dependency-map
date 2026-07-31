<?php

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Project;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

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

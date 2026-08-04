<?php

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Dependency;
use App\Models\Node;
use App\Models\Project;

// Model tests cover enum casting and dependency relationships around a node.
test('nodes cast their type and status to enums', function () {
    $node = Node::factory()->create([
        'type' => NodeType::Task,
        'status' => NodeStatus::AtRisk,
    ]);

    expect($node->type)->toBe(NodeType::Task)
        ->and($node->status)->toBe(NodeStatus::AtRisk);
});

test('deleting a project cascades to its nodes and dependencies', function () {
    $project = Project::factory()->create();
    $sourceNode = Node::factory()->for($project)->create();
    $targetNode = Node::factory()->for($project)->create();
    $dependency = Dependency::factory()
        ->for($project)
        ->for($sourceNode, 'sourceNode')
        ->for($targetNode, 'targetNode')
        ->create();

    $project->delete();

    $this->assertModelMissing($sourceNode);
    $this->assertModelMissing($targetNode);
    $this->assertModelMissing($dependency);
});

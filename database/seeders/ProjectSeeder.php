<?php

namespace Database\Seeders;

use App\Models\Dependency;
use App\Models\Node;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(?User $user = null): void
    {
        $project = Project::factory()
            ->for($user ?? User::factory())
            ->create([
                'name' => 'Website launch',
                'description' => 'Example project for exploring dependency mapping.',
            ]);

        $sourceNode = Node::factory()->for($project)->create([
            'name' => 'Approve production release',
            'type' => 'approval',
        ]);

        $targetNode = Node::factory()->for($project)->create([
            'name' => 'Deploy application',
            'type' => 'service',
        ]);

        Dependency::factory()
            ->for($project)
            ->for($sourceNode, 'sourceNode')
            ->for($targetNode, 'targetNode')
            ->create(['description' => 'Deployment waits for release approval.']);
    }
}

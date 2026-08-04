<?php

namespace Database\Factories;

use App\Models\Dependency;
use App\Models\Node;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dependency>
 */
class DependencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Tests that need a shared project override these related factories explicitly.
        return [
            'project_id' => Project::factory(),
            'source_node_id' => Node::factory(),
            'target_node_id' => Node::factory(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}

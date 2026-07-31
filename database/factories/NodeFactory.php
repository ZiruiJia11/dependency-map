<?php

namespace Database\Factories;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Node>
 */
class NodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'type' => fake()->randomElement(NodeType::cases()),
            'status' => fake()->randomElement(NodeStatus::cases()),
            'position_x' => null,
            'position_y' => null,
        ];
    }
}

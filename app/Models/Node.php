<?php

namespace App\Models;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use Database\Factories\NodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id',
    'name',
    'description',
    'type',
    'status',
    'position_x',
    'position_y',
])]
class Node extends Model
{
    /** @use HasFactory<NodeFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => NodeStatus::Normal->value,
    ];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Dependency, $this>
     */
    public function outgoingDependencies(): HasMany
    {
        return $this->hasMany(Dependency::class, 'source_node_id');
    }

    /**
     * @return HasMany<Dependency, $this>
     */
    public function incomingDependencies(): HasMany
    {
        return $this->hasMany(Dependency::class, 'target_node_id');
    }

    protected function casts(): array
    {
        return [
            'type' => NodeType::class,
            'status' => NodeStatus::class,
            'position_x' => 'decimal:4',
            'position_y' => 'decimal:4',
        ];
    }
}

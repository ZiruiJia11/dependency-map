<?php

namespace App\Http\Requests;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Node;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNodeRequest extends FormRequest
{
    /** Verify ownership and the nested project-node relationship. */
    public function authorize(): bool
    {
        $project = $this->route('project');
        $node = $this->route('node');

        return $project instanceof Project
            && $node instanceof Node
            && $node->project_id === $project->id
            && ($this->user()?->can('update', $project) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(NodeType::class)],
            'status' => ['required', Rule::enum(NodeStatus::class)],
        ];
    }
}

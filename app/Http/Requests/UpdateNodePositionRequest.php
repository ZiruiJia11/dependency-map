<?php

namespace App\Http\Requests;

use App\Models\Node;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNodePositionRequest extends FormRequest
{
    /**
     * Ensure the node belongs to the project and the user can update it.
     */
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
            'position_x' => ['required', 'numeric', 'between:0,1200'],
            'position_y' => ['required', 'numeric', 'between:0,260'],
        ];
    }
}

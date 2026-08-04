<?php

namespace App\Http\Requests;

use App\Enums\NodeStatus;
use App\Enums\NodeType;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNodeRequest extends FormRequest
{
    /** Inherit node-management permission from the parent project. */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('update', $project) ?? false);
    }

    /**
     * Restrict type and status to the supported domain enums.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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

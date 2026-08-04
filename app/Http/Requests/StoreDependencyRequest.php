<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDependencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Dependencies inherit their management permission from the project.
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('update', $project) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        // Scoped existence checks prevent connections to nodes in other projects.
        return [
            'source_node_id' => [
                'required',
                'integer',
                Rule::exists('nodes', 'id')
                    ->where(fn (Builder $query) => $query->where('project_id', $project instanceof Project ? $project->id : 0)),
            ],
            'target_node_id' => [
                'required',
                'integer',
                'different:source_node_id',
                Rule::exists('nodes', 'id')->where(
                    fn (Builder $query) => $query->where('project_id', $project instanceof Project ? $project->id : 0)
                ),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Skip the duplicate query when basic validation has already failed.
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $project = $this->route('project');

                if (! $project instanceof Project) {
                    return;
                }

                $alreadyExists = $project->dependencies()
                    ->where(
                        'source_node_id',
                        $this->integer('source_node_id')
                    )
                    ->where(
                        'target_node_id',
                        $this->integer('target_node_id')
                    )
                    ->exists();

                // Direction matters: A -> B remains distinct from B -> A.
                if ($alreadyExists) {
                    $validator->errors()->add(
                        'target_node_id',
                        'This dependency already exists.',
                    );
                }
            },
        ];
    }
}

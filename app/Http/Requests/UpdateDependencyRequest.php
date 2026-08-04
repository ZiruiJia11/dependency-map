<?php

namespace App\Http\Requests;

use App\Models\Dependency;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDependencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');
        $dependency = $this->route('dependency');

        // Confirm the dependency belongs to the nested project before policy checks.
        return $project instanceof Project
            && $dependency instanceof Dependency
            && $dependency->project_id === $project->id
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

        // Both endpoints must remain within the project being edited.
        return [
            'source_node_id' => [
                'required',
                'integer',
                Rule::exists('nodes', 'id')
                    ->where(fn (Builder $query) => $query->where(
                        'project_id',
                        $project instanceof Project ? $project->id : 0)),
            ],
            'target_node_id' => [
                'required',
                'integer',
                'different:source_node_id',
                Rule::exists('nodes', 'id')->where(
                    fn (Builder $query) => $query->where(
                        'project_id',
                        $project instanceof Project ? $project->id : 0)
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
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $project = $this->route('project');
                $dependency = $this->route('dependency');

                if (! $project instanceof Project || ! $dependency instanceof Dependency) {
                    return;
                }

                $alreadyExists = $project->dependencies()
                    // Exclude this row so an unchanged connection can be saved.
                    ->whereKeyNot($dependency->getKey())
                    ->where(
                        'source_node_id',
                        $this->integer('source_node_id')
                    )
                    ->where(
                        'target_node_id',
                        $this->integer('target_node_id')
                    )
                    ->exists();

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

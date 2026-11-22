<?php

namespace App\Http\Requests\Api\Bug;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — в Policy (BugPolicy)
    }

    public function rules(): array
    {
        $projectId = $this->route('project')->id;

        return [
            'assignee_id' => ['nullable', 'integer', Rule::exists('project_user', 'user_id')->where('project_id', $projectId)],
        ];
    }

    public function messages(): array
    {
        return [
            'assignee_id.exists' => 'Исполнитель должен быть участником проекта.',
        ];
    }
}

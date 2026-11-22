<?php

namespace App\Http\Requests\Api\Bug;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — в Policy (BugPolicy)
    }

    public function rules(): array
    {
        $projectId = $this->route('project')->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'steps_to_reproduce' => ['required', 'string'],
            'priority_id' => ['required', 'integer', Rule::exists('priorities', 'id')->where('project_id', $projectId)],
            'severity_id' => ['required', 'integer', Rule::exists('severities', 'id')->where('project_id', $projectId)],
            'status_id' => ['required', 'integer', Rule::exists('statuses', 'id')->where('project_id', $projectId)],
            'assignee_id' => ['nullable', 'integer', Rule::exists('project_user', 'user_id')->where('project_id', $projectId)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Заголовок обязателен.',
            'description.required' => 'Описание обязательно.',
            'steps_to_reproduce.required' => 'Шаги воспроизведения обязательны.',
            'priority_id.required' => 'Выберите приоритет.',
            'severity_id.required' => 'Выберите серьёзность.',
            'status_id.required' => 'Выберите статус.',
            'priority_id.exists' => 'Недопустимый приоритет.',
            'severity_id.exists' => 'Недопустимая серьёзность.',
            'status_id.exists' => 'Недопустимый статус.',
            'assignee_id.exists' => 'Исполнитель должен быть участником проекта.',
        ];
    }
}

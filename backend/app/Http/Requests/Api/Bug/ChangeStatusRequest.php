<?php

namespace App\Http\Requests\Api\Bug;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — в Policy (BugPolicy)
    }

    public function rules(): array
    {
        $projectId = $this->route('project')->id;

        return [
            'status_id' => ['required', 'integer', Rule::exists('statuses', 'id')->where('project_id', $projectId)],
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.required' => 'Выберите статус.',
            'status_id.exists' => 'Недопустимый статус.',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — в Policy (manageMembers)
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'exists:users,name'],
            'role' => ['required', 'string', Rule::in(['admin', 'developer', 'tester'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Введите логин пользователя.',
            'name.exists' => 'Пользователь не найден.',
            'role.required' => 'Выберите роль.',
            'role.in' => 'Недопустимая роль.',
        ];
    }
}

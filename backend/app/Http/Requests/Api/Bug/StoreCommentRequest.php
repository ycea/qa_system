<?php

namespace App\Http\Requests\Api\Bug;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — в Policy (BugPolicy)
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Введите текст комментария.',
            'body.max' => 'Комментарий слишком длинный (макс. 5000).',
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Bug;

use Illuminate\Foundation\Http\FormRequest;

class SetMetricValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — Gate update (BugPolicy)
    }

    public function rules(): array
    {
        return [
            'numeric_value' => ['nullable', 'numeric'],
            'categorical_value' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'numeric_value.numeric' => 'Значение метрики должно быть числом.',
        ];
    }
}

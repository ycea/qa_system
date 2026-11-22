<?php

namespace App\Http\Requests\Api\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка прав — Gate manageMetrics
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['numeric', 'categorical'])],
            'options' => ['nullable', 'array', Rule::requiredIf($this->input('type') === 'categorical')],
            'options.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Введите название метрики.',
            'type.required' => 'Выберите тип метрики.',
            'type.in' => 'Недопустимый тип метрики.',
            'options.required' => 'Для категориальной метрики укажите варианты значений.',
        ];
    }
}

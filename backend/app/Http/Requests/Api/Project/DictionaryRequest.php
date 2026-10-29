<?php

namespace App\Http\Requests\Api\Project;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка в Gate (manageDictionaries)
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название обязательно.',
            'name.max' => 'Название слишком длинное (макс. 255).',
        ];
    }
}

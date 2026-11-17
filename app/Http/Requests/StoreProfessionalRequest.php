<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfessionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\p{L}/u'],
            'phone' => [
                'bail', 'nullable', 'string', 'max:255', 'regex:/^[0-9\s()+-]+$/',
                function ($attribute, $value, $fail) {
                    if ($value !== null && ! in_array(strlen(preg_replace('/\D/', '', $value)), [10, 11], true)) {
                        $fail('Informe um telefone com DDD e 10 ou 11 dígitos.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do profissional.',
            'name.regex' => 'O nome deve conter pelo menos uma letra.',
            'phone.regex' => 'O telefone deve conter apenas números e caracteres de formatação.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}

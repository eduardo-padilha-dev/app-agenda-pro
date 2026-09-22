<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\p{L}/u'],
            'phone' => [
                'bail', 'required', 'string', 'max:255', 'regex:/^[0-9\s()+-]+$/',
                function ($attribute, $value, $fail) {
                    $digits = preg_replace('/\D/', '', $value);
                    if (! in_array(strlen($digits), [10, 11], true)) {
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
            'name.required' => 'Informe o nome da cliente.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'name.regex' => 'O nome deve conter pelo menos uma letra.',
            'phone.required' => 'Informe o telefone.',
            'phone.string' => 'O telefone deve ser um texto.',
            'phone.max' => 'O telefone deve ter no máximo 255 caracteres.',
            'phone.regex' => 'O telefone deve conter apenas números e caracteres de formatação.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'notes.string' => 'As observações devem ser um texto.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\p{L}/u'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'price' => ['required', 'string', 'regex:/^\d{1,7}(?:\.\d{1,2})?$/'],
            'professional_ids' => ['sometimes', 'array'],
            'professional_ids.*' => ['required', 'integer', 'distinct', Rule::exists('professionals', 'id')->where('active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do serviço.',
            'duration_minutes.required' => 'Informe a duração do serviço.',
            'price.required' => 'Informe o preço do serviço.',
            'price.regex' => 'Informe o preço em reais com até duas casas decimais.',
            'professional_ids.*.exists' => 'Selecione apenas profissionais ativos.',
        ];
    }
}

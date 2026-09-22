<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:service_id,professional_id,starts_at'],
            'items.*.service_id' => ['required', 'integer'],
            'items.*.professional_id' => ['required', 'integer'],
            'items.*.starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Selecione uma cliente.',
            'items.required' => 'Adicione pelo menos um serviço.',
            'items.min' => 'Adicione pelo menos um serviço.',
            'items.*.starts_at.date_format' => 'Informe uma data e horário válidos.',
        ];
    }
}

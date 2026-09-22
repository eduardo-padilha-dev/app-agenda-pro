<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProfessionalAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intervals' => ['sometimes', 'array:0,1,2,3,4,5,6'],
            'intervals.*' => ['array'],
            'intervals.*.*' => ['required', 'array:starts_at,ends_at'],
            'intervals.*.*.starts_at' => ['required', 'date_format:H:i'],
            'intervals.*.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->input('intervals', []) as $dayIntervals) {
                usort($dayIntervals, fn (array $a, array $b) => strcmp($a['starts_at'], $b['starts_at']));
                $previousEnd = null;
                foreach ($dayIntervals as $interval) {
                    if ($interval['starts_at'] >= $interval['ends_at']) {
                        $validator->errors()->add('intervals', 'O término de cada intervalo deve ser posterior ao início.');
                    } elseif ($previousEnd !== null && $interval['starts_at'] < $previousEnd) {
                        $validator->errors()->add('intervals', 'Os intervalos do mesmo dia não podem se sobrepor.');
                    }
                    $previousEnd = $interval['ends_at'];
                }
            }
        }];
    }
}

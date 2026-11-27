<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepositStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => [
                'required',
                'uuid',
            ],

            'deposit_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    'SENT_BACK',
                ]),
            ],

            'remarks' => [
                'required_if:status,SENT_BACK',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Constants\Constants;

class AssignedAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'userId' => $this->route('userId'),
            'period' => $this->route('period')
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'userId' => [
                'required',
                'integer',
                'min:1',
            ],

            'period' => [
                'required',
                Rule::in([Constants::PERIOD_DAILY, Constants::PERIOD_MONTHLY]),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
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
            'collection_id' => 'required|integer|min:1',
            'payment_channel' => 'required|string|max:50',
            'depository_account' => 'string|max:50',
            'transaction_reference_number' => 'required|string|max:100',
            'deposit_date' => 'required|date',
            'total_deposit_amount' => 'required|numeric|min:0',
            'deposit_charge' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'deposit_slip' => 'required|string|max:255'
        ];
    }
}

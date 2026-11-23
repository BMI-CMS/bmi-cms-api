<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EarlyEndorsedRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $status = $this->input('status');

        $rules = [
            'id' => 'required|integer|min:1',
            'employee_id' => 'required|string|max:10',
            'contact_recording_id' => 'required|integer|min:1',
            'status' => [
                'required',
                Rule::in(['APPROVED', 'DISAPPROVED']),
            ],
        ];

        if ($status === 'DISAPPROVED') {
            $rules['next_action_plan'] = 'required|string|max:60';
            $rules['next_action_date'] = 'required|date';
            $rules['remarks'] = 'string|max:255';
        }

        return $rules;
    }
}

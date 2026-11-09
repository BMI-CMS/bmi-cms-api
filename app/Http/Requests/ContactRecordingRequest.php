<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRecordingRequest extends FormRequest
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
    public function rules()
    {
        $actionTaken = $this->input('action_taken');
        $nextActionTaken = $this->input('next_action_plan');
        $actionField = $this->actionFieldMap();
        $nextActionField = $this->nextActionFieldMap();

        $rules = [
            'customer_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:15',
            'outstanding_balance' => 'required|numeric|min:0',
            'account_id' => 'required|integer|min:1',
            'current_category' => 'required|string|max:70',
            'follow_up_mode' => 'required|string|max:30',
            'reason_for_default' => 'required|string|max:50',
            'action_taken' => 'required|string|max:30',
            'next_action_plan' => 'required|string|max:60',
            'assigned_support_cc' => 'string|max:10',
            'remarks' => 'string|max:255',
            'geotagging' => 'required|string|max:255',
            'next_action_date' => 'required|date',
        ];

        foreach ($actionField as $action => $config) {
            $fieldName = $config['field'];

            if ($action === $actionTaken) {
                $rules[$fieldName] = 'required|array';
                $rules = array_merge($rules, $config['rules']);
            } else {
                $rules[$fieldName] = 'prohibited';
            }
        }

        foreach ($nextActionField as $action => $config) {
            $fieldName = $config['field'];

            if ($action === $nextActionTaken) {
                $rules[$fieldName] = 'required|array';
                $rules = array_merge($rules, $config['rules']);
            } else {
                $rules[$fieldName] = 'prohibited';
            }
        }

        return $rules;
    }

    protected function actionFieldMap(): array
    {
        return [
            'OFFER RESTRUCTURING' => [
                'field' => 'for_restructuring',
                'rules' => [
                    'for_restructuring.monthly_amortization' => 'required|numeric|min:0',
                    'for_restructuring.terms' => 'required|string|max:50',
                ],
            ],
            'COLLECTED' => [
                'field' => 'collected',
                'rules' => [
                    'collected.payment_type' => 'required|string|max:50',
                    'collected.ar_number' => 'required|string|max:50',
                    'collected.ar_date' => 'required|date',
                    'collected.amount' => 'required|numeric|min:0',
                    'collected.receipt_image_path' => 'required|string|max:255'
                ],
            ]
        ];
    }
    protected function nextActionFieldMap(): array
    {
        return [
            'Promise to Pay' => [
                'field' => 'promise_to_pay',
                'rules' => [
                    'promise_to_pay.ptp_amount' => 'required|numeric|min:0',
                    'promise_to_pay.ptp_date' => 'required|date'
                ],
            ],
            'For Repo' => [
                'field' => 'for_repo',
                'rules' => [
                    'for_repo.customer_capacity' => 'required|string|max:15',
                    'for_repo.unit_condition' => 'required|string|max:30',
                    'for_repo.customer_character' => 'required|string|max:20'
                ],
            ],
            'For Support CC' => [
                'field' => 'for_support_cc',
                'rules' => [
                    'for_support_cc.new_assigned_id' => 'required|integer|min:1'
                ],
            ]
        ];
    }
}

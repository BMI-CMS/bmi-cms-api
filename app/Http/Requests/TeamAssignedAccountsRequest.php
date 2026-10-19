<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Constants\Constants;
use Illuminate\Validation\Rule;
use App\Models\User;

class TeamAssignedAccountsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user instanceof User) {
            return false;
        }

        return in_array($user->cmsUser->level, Constants::ACCOUNT_PRIORITIZATION_ALLOWED_LEVELS, true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|min:1',
            'psgc_code' => 'required|string|max:10',
            'period' => [
                'required',
                Rule::in([Constants::PERIOD_DAILY, Constants::PERIOD_MONTHLY]),
            ],

        ];
    }
}

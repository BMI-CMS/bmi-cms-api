<?php

namespace App\Http\Requests;

use App\Constants\Constants;

class TeamVisitPlanRequest extends TeamAssignedAccountsRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:' . Constants::TEAM_ACCOUNTS_MAX_PER_PAGE],
        ];
    }
}

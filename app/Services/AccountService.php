<?php

namespace App\Services;

use App\Constants\NextActionPlan;
use App\Constants\ActionTaken;
use App\Models\Account;
use App\Models\ContactRecording;
use App\Models\Restructuring;
use App\Models\ReceiptEncoding;
use App\Models\EarlyEndorsed;
use App\Models\PromiseToPay;
use App\Models\RepossessionRequest;
use App\Models\SupportReassignment;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function markAsForcePrioritized(int $account_id, string $account_number): array
    {
        $account = Account::where('id', $account_id)
            ->where('account_number', $account_number)
            ->first();

        if (!$account) {
            return [
                'success' => false,
                'message' => 'Account not found.',
                'code' => 404,
            ];
        }

        $account->update([
            'is_force_prioritized' => true,
        ]);

        return [
            'success' => true,
            'data' => [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'is_force_prioritized' => $account->is_force_prioritized,
            ],
        ];
    }
    public function contactRecording(array $data, int $userId)
    {
        DB::beginTransaction();
        try {
            $contactRecording = ContactRecording::create([
                'account_id' => $data["account_id"],
                'current_category' =>  $data["current_category"],
                'follow_up_mode' =>  $data["follow_up_mode"],
                'action_taken' =>  $data["action_taken"],
                'reason_for_default' =>  $data["reason_for_default"],
                'next_action_plan' =>  $data["next_action_plan"],
                'remarks' =>  $data["remarks"],
                'geotagging' =>  $data["geotagging"],
                'recorded_by' =>  $userId,
                'contact_date' =>   now(),
                'next_action_date' =>  $data["next_action_date"]
            ]);

            if ($data["action_taken"] === ActionTaken::OFFERRESTRUCTURING) {
                Restructuring::create([
                    ...$data["for_restructuring"],
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                ]);
            } else if ($data["action_taken"] === ActionTaken::COLLECTED) {
                ReceiptEncoding::create([
                    ...$data["collected"],
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                ]);
            } else if (in_array($data["action_taken"], ActionTaken::EARLYENDORSED)) {
                EarlyEndorsed::create([
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                    'reason_of_endorsement' => $data["action_taken"],
                    'remarks' => $data["remarks"],
                ]);
            }

            if ($data["next_action_plan"] === NextActionPlan::PROMISETOPAY) {
                PromiseToPay::create([
                    ...$data["promise_to_pay"],
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                ]);
            } else if ($data["next_action_plan"] === NextActionPlan::FORREPO) {
                RepossessionRequest::create([
                    ...$data["for_repo"],
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                ]);
            } else if ($data["next_action_plan"] === NextActionPlan::FORSUPPORTCC) {
                SupportReassignment::create([
                    ...$data["for_support_cc"],
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                    'next_action_date' => $data["next_action_date"]
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'data' => [
                    'id' => $data["account_id"],
                    'data' => $data,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => $e,
                'code' => 404,
            ];
        }
    }
}

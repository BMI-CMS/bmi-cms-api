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
use App\Models\ReassignmentHistory;
use App\Models\CHValidation;
use App\Helpers\GetDateHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

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
    public function contactRecording(array $data, int $userId, string $userName)
    {
        DB::beginTransaction();

        try {

            $datetime = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

            $contactRecording = ContactRecording::create([
                'account_id' => $data["account_id"],
                'current_category' => $data["current_category"] ?? null,
                'follow_up_mode' => $data["follow_up_mode"],
                'action_taken' => $data["action_taken"],
                'reason_for_default' => $data["reason_for_default"],
                'next_action_plan' => $data["next_action_plan"],
                'remarks' =>  $data["remarks"] ?? null,
                'geotagging' => $data["geotagging"],
                'recorded_by' => $userId,
                'contact_date' =>  $datetime,
                'next_action_date' => GetDateHelper::nextActionDate($data["action_taken"], $data["next_action_plan"], $data["next_action_date"])
            ]);


            Account::where('id', $data["account_id"])
                ->where('is_contacted', false)
                ->update([
                    'is_contacted' => true
                ]);

            if ($data["action_taken"] === ActionTaken::OFFERRESTRUCTURING) {
                Restructuring::create([
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id,
                    'outstanding_balance' => $data["outstanding_balance"],
                    ...$data["for_restructuring"],
                ]);

                // $response = Http::post('http://127.0.0.1:8000/api/v1/integrations/for-restructuring-account', [
                //     'customer_name' => $data["customer_name"],
                //     'account_number' => $data["account_number"],
                //     'outstanding_balance' => $data["outstanding_balance"],
                //     'conducted_by' => $userName,
                //     'requested_date' => $contactRecording->contact_date,
                //     ...$data["for_restructuring"],
                // ]);

                // if ($response->failed()) {
                //     DB::rollBack();

                //     return [
                //         'success' => false,
                //         'message' => $response->json('message')
                //             ?? 'Webhook request failed.',
                //         'code' => $response->status(),
                //     ];
                // }
            } else if ($data["action_taken"] === ActionTaken::COLLECTED) {
                ReceiptEncoding::create([
                    ...$data["receipt_encoding"],
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

                // $response = Http::post('http://127.0.0.1:8000/api/v1/integrations/early-endorsed-account', [
                //     'customer_name' => $data["customer_name"],
                //     'account_number' =>  $data["account_number"],
                //     'outstanding_balance' => $data["outstanding_balance"],
                //     'reason_for_early_endorsement' => $data["action_taken"],
                //     'requested_date' => $contactRecording->contact_date,
                //     'conducted_by' => $userName,
                //     'remarks' => $data["remarks"],
                // ]);

                // if ($response->failed()) {
                //     DB::rollBack();

                //     return [
                //         'success' => false,
                //         'message' => $response->json('message')
                //             ?? 'Webhook request failed.',
                //         'code' => $response->status(),
                //     ];
                // }
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
                $account = Account::findOrFail($data["account_id"]);
                $account->update([
                    'assigned_cc_id' => $data["for_support_cc"]["new_assigned_id"]
                ]);

                ReassignmentHistory::create([
                    'account_id' => $data["account_id"],
                    'previous_assigned_id' => $userId,
                    'new_assigned_id' => $data["for_support_cc"]["new_assigned_id"],
                    'assigned_by' => $userId
                ]);
            } else if (in_array($data["next_action_plan"], NextActionPlan::FORNOTICEANDDEMAND)) {
                // $response = Http::post('http://127.0.0.1:8000/api/v1/integrations/notice-and-demand-account', [
                //     'customer_name' => $data["customer_name"],
                //     'account_number' => $data["account_number"],
                //     'outstanding_balance' => $data["outstanding_balance"],
                //     'demand_letter_type' => $data["next_action_plan"],
                //     'requested_date' => $contactRecording->contact_date,
                //     'remarks' => $data["remarks"],
                //     'conducted_by' => $userName
                // ]);

                // if ($response->failed()) {
                //     DB::rollBack();

                //     return [
                //         'success' => false,
                //         'message' => $response->json('message')
                //             ?? 'Webhook request failed.',
                //         'code' => $response->status(),
                //     ];
                // }
            } else if ($data["next_action_plan"] === NextActionPlan::FORCHVALIDATION) {
                CHValidation::create([
                    'account_id' => $data["account_id"],
                    'contact_recording_id' => $contactRecording->id
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
                'message' => $e->getMessage(),
                'code' => 404,
            ];
        }
    }
}

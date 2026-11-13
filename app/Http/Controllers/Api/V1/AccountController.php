<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Http\Requests\ContactRecordingRequest;
use App\Http\Requests\ForceAssignmentRequest;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function markAsForcePrioritized(ForceAssignmentRequest $request)
    {
        $validated = $request->validated();

        $result = $this->accountService->markAsForcePrioritized($validated['account_id'], $validated['account_number']);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Account marked as force prioritized successfully',
            'data' => $result['data'],
        ]);
    }

    public function contactRecording(ContactRecordingRequest $request)
    {

        $validated = $request->validated();
        $result = $this->accountService->contactRecording($validated, Auth::user()->cmsUser->id, Auth::user()->cmsUser->name);
        return $result;


        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Successfully Saved',
            'data' => $result['data'],
        ]);
    }
}

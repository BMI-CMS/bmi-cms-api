<?php

namespace App\Exceptions\Lucas;

use Exception;
use App\Enums\Auth\ErrorCode;
use Illuminate\Http\JsonResponse;

class LucasUnavailableException extends Exception
{
    /**
     * Create a new LUCAS unavailable exception instance.
     */
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    /**
     * Render the exception as a JSON error response.
     */
    public function render(): JsonResponse
    {
        return response()->json(
            [
                'status' => 'error',
                'message' => 'The account service is unavailable. Please try again shortly.',
                'code' => ErrorCode::SERVICE_UNAVAILABLE->value,
            ],
            503,
        );
    }
}

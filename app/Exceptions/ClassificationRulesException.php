<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The account classification rules are missing or invalid.
 *
 * The message is safe to show to API clients; details() holds the underlying issue for the log.
 */
class ClassificationRulesException extends RuntimeException
{
    public const MESSAGE = 'Account classification rules are not configured correctly.';

    public function __construct(private readonly string $details)
    {
        parent::__construct(self::MESSAGE);
    }

    public function details(): string
    {
        return $this->details;
    }

    /**
     * SettingService already logged the details; skip Laravel's default report
     * so the problem is not logged twice.
     */
    public function report(): void
    {
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => self::MESSAGE], 500);
    }
}

<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Restructuring;
use App\Models\EarlyEndorsed;
use App\Models\NoticedAccount;
// use Illuminate\Support\Facades\Http;
// use Illuminate\Support\Str;


class LucasIntegrationService
{
    // private string $clientId;
    // private string $keyId;
    // private string $secret;
    // private string $url;

    // public function __construct()
    // {
    //     $this->clientId = config('hmac.outbound.lucas.client_id');
    //     $this->keyId = config('hmac.outbound.lucas.key');
    //     $this->secret = config('hmac.outbound.lucas.secret');
    //     $this->url = config('hmac.outbound.lucas.url');
    // }

    // public function outbound(string $endpoint, array $data)
    // {
    //     $url = "$this->url/$endpoint";

    //     $requestTime = (string) now()->timestamp;
    //     $nonce = (string) Str::uuid();

    //     $body = json_encode($data, JSON_THROW_ON_ERROR);

    //     $bodyHash = hash('sha256', $body);


    //     $path = parse_url($url, PHP_URL_PATH);

    //     $canonicalQuery = '';

    //     $stringToSign = implode("\n", [
    //         'POST',
    //         $path,
    //         $canonicalQuery,
    //         $this->clientId,
    //         $this->keyId ?? '',
    //         $requestTime,
    //         $nonce,
    //         $bodyHash,
    //     ]);

    //     $signature = hash_hmac('sha256', $stringToSign, $this->secret);

    //     $headers = [
    //         'X-Client-Id' => $this->clientId,
    //         'X-Request-Time' => $requestTime,
    //         'X-Nonce' => $nonce,
    //         'X-Signature' => $signature,
    //         'Accept' => 'application/json',
    //     ];

    //     if ($this->keyId !== null && $this->keyId !== '') {
    //         $headers['X-Key-Id'] = $this->keyId;
    //     }

    //     $response = Http::withHeaders($headers)
    //         ->withBody($body, 'application/json')
    //         ->post($url);

    //     return $response;
    // }

    public function forRestructuring()
    {
        return Restructuring::query()
            ->select([
                'accounts.account_number',
                'accounts.customer_name',
                'restructurings.outstanding_balance',
                'restructurings.monthly_amortization',
                'restructurings.terms',
                'restructurings.new_monthly_amortization',
                'restructurings.restructuring_form',
                'restructurings.remarks',
                'restructurings.status',
                'cms_users.name as recorded_by_name',
                'restructurings.created_at',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'restructurings.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('restructurings.status', 'pending')
            ->get();
    }

    public function earlyEndorsed()
    {
        return EarlyEndorsed::query()
            ->select([
                'accounts.account_number',
                'accounts.customer_name',
                'early_endorseds.reason_of_endorsement',
                'cms_users.name as recorded_by_name',
                'early_endorseds.created_at',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'early_endorseds.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('early_endorseds.status', 'PENDING')
            ->get();
    }

    public function noticeAndDemand()
    {
        return NoticedAccount::query()
            ->select([
                'accounts.account_number',
                'accounts.customer_name',
                'noticed_accounts.letter_type',
                'noticed_accounts.reason_for_notice',
                'cms_users.name as recorded_by_name',
                'noticed_accounts.created_at',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'noticed_accounts.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('noticed_accounts.status', 'Unserved')
            ->get();
    }
}

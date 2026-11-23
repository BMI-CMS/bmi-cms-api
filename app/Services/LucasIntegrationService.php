<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Restructuring;
use App\Models\EarlyEndorsed;
use App\Models\NoticedAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function forRestructuring(array $filters = []): LengthAwarePaginator
    {
        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'principal_outstanding' => 'restructurings.outstanding_balance',
            'old_amortization' => 'restructurings.monthly_amortization',
            'restructuring_term' => 'restructurings.terms',
            'restructuring_date' => 'restructurings.created_at',
            'conducted_by' => 'cms_users.name',
            'restructiring_status' => 'restructurings.status',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'restructuring_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'restructurings.created_at';

        return Restructuring::query()
            ->select([
                'restructurings.id',
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
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('restructurings.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }

    public function earlyEndorsed(array $filters = []): LengthAwarePaginator
    {
        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'outstanding_balance' => 'accounts.outstanding_balance',
            'approval_status' => 'early_endorseds.status',
            'requested_date' => 'early_endorseds.created_at',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'requested_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'early_endorseds.created_at';

        return EarlyEndorsed::query()
            ->select([
                'early_endorseds.id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.outstanding_balance',
                'early_endorseds.reason_of_endorsement',
                'cms_users.name as recorded_by_name',
                'early_endorseds.created_at',
                'early_endorseds.status',
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
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('early_endorseds.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }

    public function noticeAndDemand(array $filters = []): LengthAwarePaginator
    {
        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'outstanding_balance' => 'accounts.outstanding_balance',
            'demand_letter_type' => 'noticed_accounts.letter_type',
            'reason_for_notice' => 'noticed_accounts.reason_for_notice',
            'status' => 'noticed_accounts.status',
            'requested_date' => 'noticed_accounts.created_at',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'requested_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'noticed_accounts.created_at';

        return NoticedAccount::query()
            ->select([
                'noticed_accounts.id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.outstanding_balance',
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
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('noticed_accounts.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }
}

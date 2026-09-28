<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_number',
        'customer_id',
        'customer_name',
        'monthly_amortization',
        'past_due_balance',
        'days_past_due',
        'dpd_bucket',
        'no_of_non_payments',
        'outstanding_balance',
        'last_payment_date',
        'asset',
        'is_force_prioritized',
        'cms_user_id',
        'assigned_date',
        'follow_up_date',
        'created_at'
    ];

    public const DEFAULT_FIELDS = [
        'account_number',
        'customer_id',
        'customer_name',
        'monthly_amortization',
        'past_due_balance',
        'days_past_due',
        'dpd_bucket',
        'no_of_non_payments',
        'outstanding_balance',
        'last_payment_date',
        'asset',
        'psgc_code',
        'assigned_cc_id',
        'assigned_ch_id',
        'assigned_am_id',
        'assigned_dh_id',
        'assigned_date',
        'follow_up_date',
        'collecting_address',
        'email',
        'social_media_account',
        'contact_number',
    ];

    public function scopeWithDefaultFields(Builder $query): Builder
    {
        return $query->select(self::DEFAULT_FIELDS);
    }

    public function CmsUser(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'cms_user_id', 'id');
    }
}

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
        'last_payment_amount',
        'past_due_balance',
        'days_past_due',
        'dpd_bucket',
        'no_of_non_payments',
        'outstanding_balance',
        'last_payment_date',
        'payment_status',
        'asset',
        'psgc_code',
        'is_force_prioritized',
        'assigned_cc_id',
        'assigned_ch_id',
        'assigned_am_ar_id',
        'assigned_rm__dh_id',
        'is_contacted',
        'due_date',
        'send_date'
    ];


    public const DEFAULT_FIELDS = [
        'account_number',
        'customer_id',
        'customer_name',
        'monthly_amortization',
        'last_payment_amount',
        'past_due_balance',
        'days_past_due',
        'dpd_bucket',
        'no_of_non_payments',
        'outstanding_balance',
        'last_payment_date',
        'due_date',
        'payment_status',
        'asset',
        'psgc_code',
        'is_force_prioritized',
        'assigned_cc_id',
        'assigned_ch_id',
        'assigned_am_ar_id',
        'assigned_rm__dh_id',
        'is_contacted',
        'due_date',
        'send_date'
    ];

    public function scopeWithDefaultFields(Builder $query): Builder
    {
        return $query->select(self::DEFAULT_FIELDS);
    }

    public function CmsUser(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'cms_user_id', 'id');
    }

    public function contactRecordings()
    {
        return $this->hasMany(ContactRecording::class, 'account_id');
    }

    public function todayContactRecording()
    {
        return $this->hasOne(ContactRecording::class, 'account_id')
            ->ofMany([
                'next_action_date' => 'max',
            ], function ($query) {
                $query->where('next_action_date', '>=', today()->startOfDay())
                    ->where('next_action_date', '<', today()->addDay()->startOfDay());
            });
    }
}

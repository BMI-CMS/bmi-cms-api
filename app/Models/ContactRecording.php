<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactRecording extends Model
{
    use SoftDeletes;

    protected $table = "contact_recordings";

    protected $fillable = [
        'account_id',
        'current_category',
        'follow_up_mode',
        'action_taken',
        'reason_for_default',
        'next_action_plan',
        'assigned_support_cc',
        'remarks',
        'geotagging',
        'recorded_by',
        'contact_date',
        'next_action_date',
        'is_contacted'
    ];

    protected function casts(): array
    {
        return [
            'is_contacted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The account's first contact recording is its first visit: accounts.is_contacted
        // (initial contact status) becomes true and stays true. Later recordings match no row,
        // so the account is not rewritten. Rows added with insert() bypass model events.
        static::created(function (ContactRecording $recording) {
            Account::query()
                ->whereKey($recording->account_id)
                ->where('is_contacted', false)
                ->update(['is_contacted' => true]);
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}

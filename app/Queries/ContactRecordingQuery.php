<?php

namespace App\Queries;

use App\Models\ContactRecording;

class ContactRecordingQuery
{
    public static function contactRecordedDaily(string  $startDate)
    {
        return ContactRecording::select([
            'id as contact_recording_id',
            'account_id',
            'follow_up_mode',
            'reason_for_default',
            'action_taken',
            'next_action_plan',
            'remarks',
            'geotagging',
            'recorded_by',
            'contact_date',
            'next_action_date'
        ])
            ->where('next_action_date', '<', $startDate);
    }
    public static function contactRecordedMonthly(string  $startDate, string $endDate)
    {
        return ContactRecording::select([
            'id as contact_recording_id',
            'account_id',
            'follow_up_mode',
            'reason_for_default',
            'action_taken',
            'next_action_plan',
            'remarks',
            'geotagging',
            'recorded_by',
            'contact_date',
            'next_action_date'
        ])
            ->where('next_action_date', '>=', $startDate)
            ->where('next_action_date', '<', $endDate);
    }
}

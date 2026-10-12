<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Next Action Carry-over Days
    |--------------------------------------------------------------------------
    |
    | How many days before today a next action may have been scheduled and still
    | appear in the Visit Plan (never before the start of the current month).
    | 1 = an uncompleted next action from yesterday shows for one more day
    | (confirmed 2026-10-10, docs/general-business-rules.md section 7.1).
    |
    */

    'next_action_carry_over_days' => 1,

];

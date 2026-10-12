<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FecCustomerEvaluation extends Model
{
    protected $table = 'fec_customer_evaluations';

    protected $fillable =  [
        'fec_accounts_id',
        'table_a_section_1',
        'table_a_section_2',
        'table_a_section_3',
        'table_a_section_4',
        'table_b_section_5',
        'table_b_section_6',
        'question_description',
        'user_response',
        'fec_form',
        'remarks',
        'visit_date',
        'submit_date',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'table_a_section_1' => 'array',
            'table_a_section_2' => 'array',
            'table_b_section_5' => 'array',
            'table_b_section_6' => 'array',

        ];
    }
}

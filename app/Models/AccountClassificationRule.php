<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One account classification rule, evaluated by SettingService.
 *
 * - parent:         "Prioritization" or "Regular".
 * - classification: the label (e.g. "For Repossession"); null = "use the recording's current_category".
 * - precedence:     evaluation order, lowest first; the first matching rule wins.
 * - conditions:     {"all": [{"field": ..., "operator": ..., "value": ...}, ...]}
 */
class AccountClassificationRule extends Model
{
    use SoftDeletes;

    protected $table = 'account_classification_rules';

    protected $fillable = [
        'parent',
        'classification',
        'precedence',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'precedence' => 'integer',
        ];
    }
}

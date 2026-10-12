<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One classification label, read by SettingService.
 *
 * - parent:        "prioritization" or "regular" (the top-level group in the assigned-accounts response).
 * - label:         the classification name used by account_classification_rules.classification.
 * - response_path: the position of the accounts in the response, e.g. ["regular", "np_1"].
 */
class AccountClassification extends Model
{
    use SoftDeletes;

    protected $table = 'account_classifications';

    protected $fillable = [
        'parent',
        'label',
        'response_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'response_path' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

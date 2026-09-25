<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CMSUser extends Model
{
    use SoftDeletes;

    protected $table = 'cms_users';

    protected $fillable = [
        'name',
        'role_id',
        'assigned_ch_ro_id',
        'assigned_ch_ro',
        'assigned_rm_dh_id',
        'assigned_rm_dh',
        'psgc_code'
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'cms_user_id', 'id');
    }
}

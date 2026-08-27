<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN_ID = 1;

    public const STAFF_ID = 2;

    public const CUSTOMER_ID = 3;

    public const DRIVER_ID = 4;

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

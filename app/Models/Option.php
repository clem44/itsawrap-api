<?php

namespace App\Models;

use App\Models\Concerns\HasPrimaryImageMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Plank\Mediable\Mediable;

class Option extends Model
{
    use HasFactory, HasPrimaryImageMedia, Mediable;

    protected $fillable = [
        'name',
        'title',
        'description',
    ];

    public function optionValues(): HasMany
    {
        return $this->hasMany(OptionValue::class);
    }

    public function itemOptions(): HasMany
    {
        return $this->hasMany(ItemOption::class);
    }
}

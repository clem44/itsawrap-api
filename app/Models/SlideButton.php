<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideButton extends Model
{
    use HasFactory;

    protected $fillable = [
        'sort_order',
        'label',
        'url',
        'style',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function slide(): BelongsTo
    {
        return $this->belongsTo(Slide::class);
    }
}

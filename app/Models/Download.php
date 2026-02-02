<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'filepath',
        'size',
        'ext',
        'meme',
        'filetype',
        'type',
        'bundle_identifier',
        'bundle_version',
        'title',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'published' => 'boolean',
        ];
    }
}

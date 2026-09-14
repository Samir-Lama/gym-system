<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckInChallenge extends Model
{
    protected $fillable = [
        'token_hash',
        'location',
        'expires_at',
        'used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}

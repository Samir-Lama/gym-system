<?php

namespace App\Models;

use App\Enums\AccessCredentialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAccessCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'type',
        'credential_hash',
        'label',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = [
        'credential_hash',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccessCredentialType::class,
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

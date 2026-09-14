<?php

namespace App\Models;

use App\Enums\CheckInMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'member_membership_id',
        'method',
        'check_in_at',
        'check_out_at',
        'device_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'method' => CheckInMethod::class,
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(
            MemberMembership::class,
            'member_membership_id'
        );
    }
}

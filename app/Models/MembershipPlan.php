<?php

namespace App\Models;

use App\Enums\MembershipPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'status' => MembershipPlanStatus::class,
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(MemberMembership::class);
    }
}

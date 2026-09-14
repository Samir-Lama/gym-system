<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum MembershipStatus: string implements SelectableEnum
{
    use HasOptions;

    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case PAUSED = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::PAUSED => 'Paused',
        };
    }
}

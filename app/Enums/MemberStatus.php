<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum MemberStatus: string implements SelectableEnum
{
    use HasOptions;
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
            self::PENDING => 'Pending',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::SUSPENDED => 'yellow',
            self::CANCELLED => 'red',
            self::PENDING => 'blue',
        };
    }
}

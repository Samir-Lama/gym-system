<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum PaymentStatus: string implements SelectableEnum
{
    use HasOptions;

    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }
}

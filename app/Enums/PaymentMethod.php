<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum PaymentMethod: string implements SelectableEnum
{
    use HasOptions;

    case CASH = 'cash';
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case ESEWA = 'esewa';
    case KHALTI = 'khalti';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::ESEWA => 'eSewa',
            self::KHALTI => 'Khalti',
        };
    }
}

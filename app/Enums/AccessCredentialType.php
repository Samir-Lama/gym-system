<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum AccessCredentialType: string implements SelectableEnum
{
    use HasOptions;

    case QR = 'qr';
    case RFID = 'rfid';

    public function label(): string
    {
        return match ($this) {
            self::QR => 'QR Code',
            self::RFID => 'RFID',
        };
    }
}

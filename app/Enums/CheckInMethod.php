<?php

namespace App\Enums;

use App\Enums\Contracts\SelectableEnum;
use App\Traits\HasOptions;

enum CheckInMethod: string implements SelectableEnum
{
    use HasOptions;

    case MANUAL = 'manual';
    case QR = 'qr';
    case RFID = 'rfid';
    case BIOMETRIC = 'biometric';
    case MOBILE = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::QR => 'QR Code',
            self::RFID => 'RFID / NFC',
            self::BIOMETRIC => 'Biometric',
            self::MOBILE => 'Mobile',
        };
    }
}

<?php

namespace App\Enums\Contracts;

interface SelectableEnum
{
    public function label(): string;

    public static function values(): array;

    public static function options(): array;
}
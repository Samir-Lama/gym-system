<?php

namespace App\Contracts\Services;

use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CheckInServiceInterface
{
    public function checkIn(
        int $memberId,
        CheckInMethod $method = CheckInMethod::MANUAL,
        ?string $deviceId = null,
        ?string $notes = null,
    ): CheckIn;

    public function checkOut(CheckIn $checkIn): CheckIn;

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $presence = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator;
}

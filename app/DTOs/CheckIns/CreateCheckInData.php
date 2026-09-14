<?php

namespace App\DTOs\CheckIns;

use App\Enums\CheckInMethod;
use App\Http\Requests\CheckIns\StoreCheckInRequest;

readonly class CreateCheckInData
{
    public function __construct(
        public int $memberId,
        public CheckInMethod $method,
        public ?string $deviceId,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreCheckInRequest $request): self
    {
        return new self(
            memberId: (int) $request->input('member_id'),
            method: CheckInMethod::from(
                $request->input('method') ?: CheckInMethod::MANUAL->value
            ),
            deviceId: $request->input('device_id'),
            notes: $request->input('notes'),
        );
    }
}

<?php

namespace App\DTOs\MembershipPlans;

use App\Enums\MembershipPlanStatus;
use App\Http\Requests\MembershipPlans\StoreMembershipPlanRequest;

readonly class CreateMembershipPlanData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public int $durationDays,
        public MembershipPlanStatus $status,
    ) {}

    public static function fromRequest(
        StoreMembershipPlanRequest $request
    ): self {
        return new self(
            name: $request->string('name')->value(),
            description: $request->input('description'),
            price: (float) $request->input('price'),
            durationDays: (int) $request->input('duration_days'),
            status: MembershipPlanStatus::from(
                $request->string('status')->value()
            ),
        );
    }
}

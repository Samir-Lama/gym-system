<?php

namespace App\DTOs\MembershipPlans;

use App\Enums\MembershipPlanStatus;
use App\Http\Requests\MembershipPlans\UpdateMembershipPlanRequest;

readonly class UpdateMembershipPlanData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public int $durationDays,
        public MembershipPlanStatus $status,
    ) {}

    public static function fromRequest(
        UpdateMembershipPlanRequest $request
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
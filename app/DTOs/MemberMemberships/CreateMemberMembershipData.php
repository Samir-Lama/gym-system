<?php

namespace App\DTOs\MemberMemberships;

use App\Http\Requests\MemberMemberships\StoreMemberMembershipRequest;

readonly class CreateMemberMembershipData
{
    public function __construct(
        public int $memberId,
        public int $membershipPlanId,
        public string $startDate,
        public ?string $notes,
        public float $discountAmount = 0,
        public ?string $discountReason = null,
    ) {}

    public static function fromRequest(
        StoreMemberMembershipRequest $request
    ): self {
        return new self(
            memberId: (int) $request->input('member_id'),
            membershipPlanId: (int) $request->input('membership_plan_id'),
            startDate: $request->string('start_date')->value(),
            notes: $request->input('notes'),
            discountAmount: (float) ($request->input('discount_amount') ?? 0),
            discountReason: $request->input('discount_reason'),
        );
    }
}

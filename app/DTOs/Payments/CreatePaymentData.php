<?php

namespace App\DTOs\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Payments\StorePaymentRequest;

readonly class CreatePaymentData
{
    public function __construct(
        public int $memberId,
        public int $memberMembershipId,
        public PaymentMethod $paymentMethod,
        public PaymentStatus $paymentStatus,
        public ?string $paidAt,
        public ?string $reference,
        public ?string $notes,
    ) {}

    public static function fromRequest(
        StorePaymentRequest $request
    ): self {
        return new self(
            memberId: (int) $request->input('member_id'),

            memberMembershipId: (int) $request->input('member_membership_id'),

            paymentMethod: PaymentMethod::from(
                $request->string('payment_method')->value()
            ),

            paymentStatus: PaymentStatus::from(
                $request->string('payment_status')->value()
            ),

            paidAt: $request->input('paid_at'),
            reference: $request->input('reference'),
            notes: $request->input('notes'),
        );
    }
}

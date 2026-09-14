<?php

namespace Tests\Feature;

use App\DTOs\Payments\CreatePaymentData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function membership(string $finalPrice = '2000.25'): MemberMembership
    {
        return MemberMembership::create([
            'member_id' => Member::factory()->create()->id,
            'membership_plan_id' => MembershipPlan::create([
                'name' => 'Monthly Basic', 'price' => 2500, 'duration_days' => 30, 'status' => 'active',
            ])->id,
            'start_date' => '2026-09-13', 'end_date' => '2026-10-12', 'status' => 'active',
            'price' => 2500, 'discount_amount' => 2500 - (float) $finalPrice, 'final_price' => $finalPrice,
        ]);
    }

    private function payload(MemberMembership $membership): array
    {
        $this->actingAsRole();

        return [
            'member_id' => $membership->member_id,
            'member_membership_id' => $membership->id,
            'amount' => 1,
            'payment_method' => 'cash', 'payment_status' => 'paid',
            'paid_at' => '2026-09-13', 'reference' => 'RECEIPT-123', 'notes' => 'Paid at desk',
        ];
    }

    public function test_tampered_amount_is_replaced_with_membership_snapshot(): void
    {
        $membership = $this->membership();
        $membership->plan->update(['price' => 3000]);
        $this->post(route('payments.store'), $this->payload($membership))->assertSessionHasNoErrors();
        $payment = Payment::sole();
        $this->assertSame('2000.25', $payment->amount);
        $this->assertSame($membership->id, $payment->member_membership_id);
        $this->assertSame($membership->member_id, $payment->member_id);
        $this->assertSame('RECEIPT-123', $payment->reference);
        $this->assertSame('Paid at desk', $payment->notes);
    }

    public function test_fully_discounted_membership_records_zero(): void
    {
        $this->post(route('payments.store'), $this->payload($this->membership('0.00')))
            ->assertSessionHasNoErrors();
        $this->assertSame('0.00', Payment::sole()->amount);
    }

    public function test_request_rejects_missing_nonexistent_or_other_members_membership(): void
    {
        $membership = $this->membership();
        $payload = $this->payload($membership);
        $other = $this->membership();
        foreach ([null, 999999, $other->id] as $id) {
            $this->post(route('payments.store'), array_merge($payload, ['member_membership_id' => $id]))
                ->assertSessionHasErrors('member_membership_id');
        }
        unset($payload['member_membership_id']);
        $this->post(route('payments.store'), $payload)->assertSessionHasErrors('member_membership_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_non_active_membership(): void
    {
        $membership = $this->membership();
        $membership->update(['status' => 'paused']);

        $this->post(route('payments.store'), $this->payload($membership))
            ->assertSessionHasErrors('member_membership_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_service_independently_rejects_invalid_ownership(): void
    {
        $membership = $this->membership();
        $otherMember = Member::factory()->create();
        foreach ([$membership->id, 999999] as $id) {
            try {
                app(PaymentService::class)->create(new CreatePaymentData(
                    memberId: $otherMember->id,
                    memberMembershipId: $id,
                    paymentMethod: PaymentMethod::CASH,
                    paymentStatus: PaymentStatus::PAID,
                    paidAt: null, reference: null, notes: null,
                ));
                $this->fail('Invalid membership should have been rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('member_membership_id', $exception->errors());
            }
        }
        $this->assertDatabaseCount('payments', 0);
    }
}

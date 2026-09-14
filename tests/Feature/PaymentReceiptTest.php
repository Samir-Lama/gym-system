<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Payment
    {
        return Payment::create([
            'member_id' => Member::factory()->create()->id,
            'amount' => 2000, 'payment_method' => 'cash', 'payment_status' => 'paid',
            'paid_at' => '2026-09-13', 'reference' => 'RECEIPT-123', 'notes' => 'Paid at desk',
        ]);
    }

    public function test_receipt_loads_member_and_membership_price_snapshot(): void
    {
        $payment = $this->payment();
        $plan = MembershipPlan::create([
            'name' => 'Monthly Basic', 'price' => 3000, 'duration_days' => 30, 'status' => 'active',
        ]);
        $membership = MemberMembership::create([
            'member_id' => $payment->member_id, 'membership_plan_id' => $plan->id,
            'start_date' => '2026-09-13', 'end_date' => '2026-10-12', 'status' => 'active',
            'price' => 2500, 'discount_amount' => 500, 'final_price' => 2000,
            'discount_reason' => 'Student discount',
        ]);
        $payment->update(['member_membership_id' => $membership->id]);

        $this->actingAsRole();
        $this->get(route('payments.show', $payment))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Billing/Show')
            ->where('payment.id', $payment->id)
            ->where('payment.amount', '2000.00')
            ->where('payment.reference', 'RECEIPT-123')
            ->where('payment.notes', 'Paid at desk')
            ->where('payment.member.id', $payment->member_id)
            ->where('payment.membership.plan.name', 'Monthly Basic')
            ->where('payment.membership.price', '2500.00')
            ->where('payment.membership.discount_amount', '500.00')
            ->where('payment.membership.final_price', '2000.00')
            ->where('payment.membership.discount_reason', 'Student discount'));
    }

    public function test_receipt_supports_payments_without_membership_or_optional_details(): void
    {
        $payment = $this->payment();
        $payment->update(['paid_at' => null, 'reference' => null, 'notes' => null, 'payment_status' => 'pending']);
        $this->actingAsRole();
        $this->get(route('payments.show', $payment))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Billing/Show')
            ->where('payment.membership', null)
            ->where('payment.paid_at', null)
            ->where('payment.reference', null)
            ->where('payment.notes', null)
            ->where('payment.payment_status', 'pending'));
    }

    public function test_receipt_requires_authentication(): void
    {
        $this->get(route('payments.show', $this->payment()))->assertRedirect(route('login'));
    }

    public function test_unknown_payment_returns_not_found(): void
    {
        $this->actingAsRole();
        $this->get(route('payments.show', 999999))->assertNotFound();
    }
}

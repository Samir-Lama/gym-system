<?php

namespace Tests\Feature;

use App\DTOs\MemberMemberships\CreateMemberMembershipData;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Services\MemberMemberships\MemberMembershipService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberMembershipLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private MemberMembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MemberMembershipService::class);
    }

    private function plan(string $status = 'active'): MembershipPlan
    {
        return MembershipPlan::create([
            'name' => 'Monthly',
            'price' => 2500,
            'duration_days' => 30,
            'status' => $status,
        ]);
    }

    private function membership(
        Member $member,
        MembershipPlan $plan,
        MembershipStatus $status
    ): MemberMembership {
        return MemberMembership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(29),
            'status' => $status,
            'price' => $plan->price,
            'final_price' => $plan->price,
        ]);
    }

    private function assignment(Member $member, MembershipPlan $plan): CreateMemberMembershipData
    {
        return new CreateMemberMembershipData(
            memberId: $member->id,
            membershipPlanId: $plan->id,
            startDate: today()->toDateString(),
            notes: null,
        );
    }

    private function assertValidationError(Closure $callback, string $key): void
    {
        try {
            $callback();
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }

    public function test_paused_membership_blocks_another_assignment(): void
    {
        $member = Member::factory()->create();
        $plan = $this->plan();
        $this->membership($member, $plan, MembershipStatus::PAUSED);

        $this->assertValidationError(
            fn () => $this->service->create($this->assignment($member, $plan)),
            'membership_plan_id'
        );

        $this->assertDatabaseCount('member_memberships', 1);
    }

    public function test_resume_cannot_create_a_second_current_membership(): void
    {
        $member = Member::factory()->create();
        $plan = $this->plan();
        $paused = $this->membership($member, $plan, MembershipStatus::PAUSED);
        $this->membership($member, $plan, MembershipStatus::ACTIVE);

        $this->assertValidationError(
            fn () => $this->service->resume($paused),
            'membership'
        );

        $this->assertSame(MembershipStatus::PAUSED, $paused->fresh()->status);
    }

    public function test_inactive_plan_cannot_be_assigned_or_renewed(): void
    {
        $member = Member::factory()->create();
        $plan = $this->plan('inactive');

        $this->assertValidationError(
            fn () => $this->service->create($this->assignment($member, $plan)),
            'membership_plan_id'
        );

        $expired = $this->membership($member, $plan, MembershipStatus::EXPIRED);
        $this->assertValidationError(
            fn () => $this->service->renew($expired),
            'membership_plan_id'
        );
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $member = Member::factory()->create();
        $plan = $this->plan();
        $cancelled = $this->membership($member, $plan, MembershipStatus::CANCELLED);
        $expired = $this->membership($member, $plan, MembershipStatus::EXPIRED);
        $paused = $this->membership($member, $plan, MembershipStatus::PAUSED);
        $active = $this->membership(Member::factory()->create(), $plan, MembershipStatus::ACTIVE);

        $this->assertValidationError(fn () => $this->service->pause($cancelled), 'membership');
        $this->assertValidationError(fn () => $this->service->cancel($expired), 'membership');
        $this->assertValidationError(fn () => $this->service->renew($paused), 'membership');
        $this->assertValidationError(fn () => $this->service->resume($active), 'membership');
    }

    public function test_expiration_command_expires_ended_active_and_paused_memberships(): void
    {
        $member = Member::factory()->create();
        $plan = $this->plan();
        $active = $this->membership($member, $plan, MembershipStatus::ACTIVE);
        $paused = $this->membership(Member::factory()->create(), $plan, MembershipStatus::PAUSED);
        $active->update(['end_date' => today()->subDay()]);
        $paused->update(['end_date' => today()->subDay()]);

        $this->artisan('memberships:expire')->assertSuccessful();

        $this->assertSame(MembershipStatus::EXPIRED, $active->fresh()->status);
        $this->assertSame(MembershipStatus::EXPIRED, $paused->fresh()->status);
    }
}

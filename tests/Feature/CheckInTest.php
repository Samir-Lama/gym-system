<?php

namespace Tests\Feature;

use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Services\CheckIns\CheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): MembershipPlan
    {
        return MembershipPlan::create([
            'name' => 'Monthly',
            'price' => 2500,
            'duration_days' => 30,
            'status' => 'active',
        ]);
    }

    private function memberWithMembership(
        string $membershipStatus = 'active',
        ?string $startDate = null,
        ?string $endDate = null,
        string $memberStatus = 'active',
    ): array {
        $member = Member::factory()->create(['status' => $memberStatus]);
        $membership = MemberMembership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $this->plan()->id,
            'start_date' => $startDate ?? today()->toDateString(),
            'end_date' => $endDate ?? today()->addDays(29)->toDateString(),
            'status' => $membershipStatus,
            'price' => 2500,
            'final_price' => 2500,
        ]);

        return [$member, $membership];
    }

    public function test_check_in_routes_require_staff_or_admin_role(): void
    {
        $this->get(route('check-ins.index'))->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->get(route('check-ins.index'))->assertForbidden();

        $this->actingAsRole('Staff');
        $this->get(route('check-ins.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CheckIns/Index')
                ->has('methods', 5));
    }

    public function test_active_member_with_valid_membership_can_check_in(): void
    {
        $this->actingAsRole('Staff');
        [$member, $membership] = $this->memberWithMembership();

        $this->post(route('check-ins.store'), [
            'member_id' => $member->id,
            'method' => 'qr',
            'device_id' => 'front-desk-1',
            'notes' => 'Morning visit',
        ])->assertSessionHasNoErrors();

        $checkIn = CheckIn::sole();
        $this->assertSame($member->id, $checkIn->member_id);
        $this->assertSame($membership->id, $checkIn->member_membership_id);
        $this->assertSame(CheckInMethod::QR, $checkIn->method);
        $this->assertSame('front-desk-1', $checkIn->device_id);
        $this->assertNull($checkIn->check_out_at);
    }

    public function test_missing_method_defaults_to_manual(): void
    {
        $this->actingAsRole('Staff');
        [$member] = $this->memberWithMembership();

        $this->post(route('check-ins.store'), [
            'member_id' => $member->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(CheckInMethod::MANUAL, CheckIn::sole()->method);
    }

    public function test_invalid_member_or_membership_state_is_rejected(): void
    {
        $service = app(CheckInService::class);
        [$inactiveMember] = $this->memberWithMembership(memberStatus: 'inactive');
        [$pausedMember] = $this->memberWithMembership(membershipStatus: 'paused');
        [$futureMember] = $this->memberWithMembership(
            startDate: today()->addDay()->toDateString(),
            endDate: today()->addMonth()->toDateString(),
        );
        [$endedMember] = $this->memberWithMembership(
            startDate: today()->subMonth()->toDateString(),
            endDate: today()->subDay()->toDateString(),
        );

        foreach ([$inactiveMember, $pausedMember, $futureMember, $endedMember] as $member) {
            try {
                $service->checkIn($member->id);
                $this->fail('Invalid member or membership should not check in.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('member_id', $exception->errors());
            }
        }

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_member_cannot_have_two_open_check_ins(): void
    {
        $service = app(CheckInService::class);
        [$member] = $this->memberWithMembership();
        $service->checkIn($member->id);

        try {
            $service->checkIn($member->id);
            $this->fail('A second open check-in should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('member_id', $exception->errors());
        }

        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_checkout_closes_an_open_check_in_once(): void
    {
        $service = app(CheckInService::class);
        [$member] = $this->memberWithMembership();
        $checkIn = $service->checkIn($member->id);

        $closed = $service->checkOut($checkIn);
        $this->assertNotNull($closed->check_out_at);

        try {
            $service->checkOut($closed);
            $this->fail('A closed check-in should not close twice.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('check_in', $exception->errors());
        }
    }

    public function test_request_rejects_an_invalid_method(): void
    {
        $this->actingAsRole('Staff');
        [$member] = $this->memberWithMembership();

        $this->post(route('check-ins.store'), [
            'member_id' => $member->id,
            'method' => 'turnstile',
        ])->assertSessionHasErrors('method');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_history_filters_by_member_presence_and_method(): void
    {
        $this->actingAsRole('Staff');
        [$member] = $this->memberWithMembership();
        $member->update(['first_name' => 'Samir', 'last_name' => 'Lama']);
        $service = app(CheckInService::class);
        $open = $service->checkIn($member->id, CheckInMethod::RFID);
        [$otherMember] = $this->memberWithMembership();
        $closed = $service->checkIn($otherMember->id, CheckInMethod::MANUAL);
        $service->checkOut($closed);

        $this->get(route('check-ins.index', [
            'search' => 'samir lama',
            'presence' => 'open',
            'method' => 'rfid',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('checkIns.data', 1)
            ->where('checkIns.data.0.id', $open->id)
            ->where('filters.search', 'samir lama')
            ->where('filters.presence', 'open')
            ->where('filters.method', 'rfid'));
    }

    public function test_history_search_does_not_treat_zero_as_empty(): void
    {
        $this->actingAsRole('Staff');
        [$matchingMember] = $this->memberWithMembership();
        $matchingMember->update(['membership_number' => 'GYM-00001']);
        [$otherMember] = $this->memberWithMembership();
        $otherMember->update(['membership_number' => 'GYM-ABC']);
        $service = app(CheckInService::class);
        $matching = $service->checkIn($matchingMember->id);
        $service->checkIn($otherMember->id);

        $this->get(route('check-ins.index', ['search' => '0']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('checkIns.data', 1)
                ->where('checkIns.data.0.id', $matching->id)
                ->where('filters.search', '0'));
    }
}

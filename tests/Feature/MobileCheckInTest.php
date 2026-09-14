<?php

namespace Tests\Feature;

use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\CheckIns\CheckInChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function linkedEligibleMember(User $user, bool $linkUser = true): Member
    {
        if (! $user->hasRole('Member')) {
            $user->assignRole(Role::findOrCreate('Member'));
        }

        $member = Member::factory()->create([
            'user_id' => $linkUser ? $user->id : null,
            'status' => 'active',
        ]);
        $plan = MembershipPlan::create([
            'name' => "Mobile Plan {$member->id}",
            'price' => 2500,
            'duration_days' => 30,
            'status' => 'active',
        ]);
        MemberMembership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(29),
            'status' => 'active',
            'price' => 2500,
            'final_price' => 2500,
        ]);

        return $member;
    }

    public function test_entrance_display_requires_staff_or_admin(): void
    {
        $this->get(route('checkins.display'))->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->get(route('checkins.display'))->assertForbidden();

        $this->actingAsRole('Staff');
        $response = $this->get(route('checkins.display'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CheckIns/Display')
                ->where('location', 'main_entrance')
                ->has('serverTime')
                ->where('checkInUrl', fn ($value) => str_contains(
                    (string) $value,
                    '/check-in/mobile/'
                )));
        $response->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_challenge_refresh_endpoint_is_protected_and_returns_current_qr(): void
    {
        $this->getJson(route('checkins.challenge'))->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->getJson(route('checkins.challenge'))->assertForbidden();

        $this->actingAsRole('Staff');
        $first = $this->getJson(route('checkins.challenge'))
            ->assertOk()
            ->assertJsonStructure([
                'checkInUrl',
                'expiresAt',
                'location',
                'serverTime',
            ])
            ->assertJsonPath('location', 'main_entrance');
        $second = $this->getJson(route('checkins.challenge'))->assertOk();

        $this->assertSame(
            $first->json('checkInUrl'),
            $second->json('checkInUrl')
        );
        $first->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_mobile_link_requires_authentication_and_rejects_expired_token(): void
    {
        $result = app(CheckInChallengeService::class)->create();

        $this->get(route('mobile-checkin.show', $result['token']))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->travel(3)->minutes();
        $this->get(route('mobile-checkin.show', $result['token']))
            ->assertGone();
    }

    public function test_valid_mobile_link_shows_confirmation_page(): void
    {
        $this->actingAs(User::factory()->create());
        $result = app(CheckInChallengeService::class)->create('main_entrance');

        $this->get(route('mobile-checkin.show', $result['token']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CheckIns/Mobile')
                ->where('token', $result['token'])
                ->where('location', 'main_entrance'));
    }

    public function test_linked_member_can_check_in_with_mobile_challenge(): void
    {
        $user = User::factory()->create();
        $member = $this->linkedEligibleMember($user);
        $result = app(CheckInChallengeService::class)->create('main_entrance');

        $this->actingAs($user)
            ->post(route('mobile-checkin.store'), [
                'token' => $result['token'],
            ])
            ->assertRedirect(route('mobile-checkin.success'))
            ->assertSessionHas('success', 'Check-in successful.');

        $checkIn = CheckIn::sole();
        $this->assertSame($member->id, $checkIn->member_id);
        $this->assertSame(CheckInMethod::MOBILE, $checkIn->method);
        $this->assertSame('main_entrance', $checkIn->device_id);
    }

    public function test_staff_can_link_member_account_for_mobile_check_in(): void
    {
        $memberUser = User::factory()->create();
        $member = $this->linkedEligibleMember($memberUser, linkUser: false);
        $challenge = app(CheckInChallengeService::class)->create('main_entrance');

        $this->actingAsRole('Staff');
        $this->from(route('members.show', $member))
            ->patch(route('members.account.link', $member), [
                'user_id' => $memberUser->id,
            ])
            ->assertRedirect(route('members.show', $member))
            ->assertSessionHasNoErrors();

        $this->actingAs($memberUser)
            ->post(route('mobile-checkin.store'), [
                'token' => $challenge['token'],
            ])
            ->assertRedirect(route('mobile-checkin.success'));

        $this->assertSame($memberUser->id, $member->fresh()->user_id);
        $this->assertSame($member->id, CheckIn::sole()->member_id);
    }

    public function test_staff_can_unlink_member_account(): void
    {
        $memberUser = User::factory()->create();
        $member = $this->linkedEligibleMember($memberUser);

        $this->actingAsRole('Staff');
        $this->patch(route('members.account.link', $member), [
            'user_id' => null,
        ])->assertSessionHasNoErrors();

        $this->assertNull($member->fresh()->user_id);
    }

    public function test_account_link_request_must_explicitly_include_user_id(): void
    {
        $memberUser = User::factory()->create();
        $member = $this->linkedEligibleMember($memberUser);

        $this->actingAsRole('Staff');
        $this->patch(route('members.account.link', $member), [])
            ->assertSessionHasErrors('user_id');

        $this->assertSame($memberUser->id, $member->fresh()->user_id);
    }

    public function test_linked_user_must_retain_member_role(): void
    {
        $memberUser = User::factory()->create();
        $this->linkedEligibleMember($memberUser);
        $challenge = app(CheckInChallengeService::class)->create();
        $memberUser->removeRole('Member');

        $this->actingAs($memberUser)
            ->post(route('mobile-checkin.store'), [
                'token' => $challenge['token'],
            ])
            ->assertSessionHasErrors('member');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_challenge_can_check_in_multiple_members_before_expiry(): void
    {
        $challenge = app(CheckInChallengeService::class)->create('main_entrance');

        foreach ([User::factory()->create(), User::factory()->create()] as $user) {
            $this->linkedEligibleMember($user);
            $this->actingAs($user)->post(route('mobile-checkin.store'), [
                'token' => $challenge['token'],
            ])->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('check_ins', 2);
        $this->assertNull($challenge['challenge']->fresh()->used_at);
    }

    public function test_mobile_check_in_requires_linked_member_and_valid_challenge(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Member'));
        $challenge = app(CheckInChallengeService::class)->create();
        $this->actingAs($user);

        $this->post(route('mobile-checkin.store'), [
            'token' => $challenge['token'],
        ])->assertSessionHasErrors('member');

        $this->travel(3)->minutes();
        $this->post(route('mobile-checkin.store'), [
            'token' => $challenge['token'],
        ])->assertSessionHasErrors('token');

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_mobile_check_in_returns_membership_eligibility_error(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Member'));
        Member::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        $challenge = app(CheckInChallengeService::class)->create();

        $this->actingAs($user)
            ->post(route('mobile-checkin.store'), [
                'token' => $challenge['token'],
            ])
            ->assertSessionHasErrors('member_id');

        $this->assertDatabaseCount('check_ins', 0);
    }
}

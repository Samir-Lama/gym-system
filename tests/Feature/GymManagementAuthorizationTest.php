<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_search_rejects_guests_and_members(): void
    {
        $this->getJson(route('members.search', ['search' => 'Samir']))
            ->assertUnauthorized();

        $this->actingAsRole('Member');

        $this->getJson(route('members.search', ['search' => 'Samir']))
            ->assertForbidden();
    }

    public function test_staff_can_search_members_by_full_name(): void
    {
        $this->actingAsRole('Staff');
        $member = Member::factory()->create([
            'first_name' => 'Samir',
            'last_name' => 'Lama',
            'email' => 'samir@example.com',
        ]);
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'price' => 2500,
            'duration_days' => 30,
            'status' => 'active',
        ]);
        $membership = MemberMembership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(29),
            'status' => 'active',
            'price' => 2500,
            'final_price' => 2500,
        ]);

        $this->getJson(route('members.search', ['search' => 'Samir Lama']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $member->id)
            ->assertJsonPath('0.email', 'samir@example.com')
            ->assertJsonPath('0.memberships.0.id', $membership->id)
            ->assertJsonPath('0.memberships.0.plan.id', $plan->id);
    }

    public function test_management_routes_enforce_the_role_matrix(): void
    {
        $member = Member::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'price' => 2500,
            'duration_days' => 30,
            'status' => 'active',
        ]);

        $this->actingAsRole('Member');
        $this->get(route('members.index'))->assertForbidden();

        $this->actingAsRole('Staff');
        $this->get(route('members.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.roles', ['Staff']));
        $this->get(route('membership-plans.index'))->assertOk();
        $this->get(route('membership-plans.edit', $plan))->assertForbidden();
        $this->delete(route('members.destroy', $member))->assertForbidden();

        $this->actingAsRole('Admin');
        $this->get(route('membership-plans.edit', $plan))->assertOk();
    }
}

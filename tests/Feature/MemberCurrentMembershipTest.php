<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberCurrentMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_supplies_paused_membership_outside_the_history_page(): void
    {
        $this->actingAsRole();
        $member = Member::factory()->create();
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'price' => 2500,
            'duration_days' => 30,
            'status' => 'active',
        ]);
        $current = MemberMembership::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(29),
            'status' => 'paused',
            'price' => 2500,
            'final_price' => 2500,
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ]);

        foreach (range(1, 6) as $day) {
            MemberMembership::create([
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'start_date' => today()->subDays($day + 30),
                'end_date' => today()->subDays($day),
                'status' => 'expired',
                'price' => 2500,
                'final_price' => 2500,
            ]);
        }

        $this->get(route('members.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members/Show')
                ->where('currentMembership.id', $current->id)
                ->where('currentMembership.status', 'paused')
                ->where('currentMembership.plan.id', $plan->id)
                ->has('memberships.data', 5));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanProtectionTest extends TestCase
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

    public function test_in_use_plan_cannot_be_deleted(): void
    {
        $this->actingAsRole('Admin');
        $plan = $this->plan();
        MemberMembership::create([
            'member_id' => Member::factory()->create()->id,
            'membership_plan_id' => $plan->id,
            'start_date' => today(),
            'end_date' => today()->addDays(29),
            'status' => 'active',
            'price' => 2500,
            'final_price' => 2500,
        ]);

        $this->delete(route('membership-plans.destroy', $plan))
            ->assertSessionHasErrors('membershipPlan');

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id]);
    }

    public function test_unused_plan_can_be_deleted(): void
    {
        $this->actingAsRole('Admin');
        $plan = $this->plan();

        $this->delete(route('membership-plans.destroy', $plan))
            ->assertRedirect(route('membership-plans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('membership_plans', ['id' => $plan->id]);
    }
}

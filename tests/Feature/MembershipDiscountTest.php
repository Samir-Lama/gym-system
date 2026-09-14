<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MembershipDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(array $overrides = []): array
    {
        $this->actingAsRole();

        return array_merge([
            'member_id' => Member::factory()->create()->id,
            'membership_plan_id' => MembershipPlan::create([
                'name' => 'Monthly Basic', 'price' => 2500,
                'duration_days' => 30, 'status' => 'active',
            ])->id,
            'start_date' => '2026-09-13',
        ], $overrides);
    }

    public function test_assignment_snapshots_discount_without_changing_plan(): void
    {
        $data = $this->assignment(['discount_amount' => 500, 'discount_reason' => 'Student discount']);
        $this->post(route('member-memberships.store'), $data)->assertSessionHasNoErrors();
        $membership = MemberMembership::sole();
        $this->assertSame('2500.00', $membership->price);
        $this->assertSame('500.00', $membership->discount_amount);
        $this->assertSame('2000.00', $membership->final_price);
        $this->assertSame('Student discount', $membership->discount_reason);
        $this->assertSame('2500.00', $membership->plan->price);
    }

    public function test_discount_defaults_to_zero_and_accepts_full_price(): void
    {
        foreach ([null, 2500, 0.25] as $discount) {
            $data = $this->assignment(['discount_amount' => $discount]);
            $this->post(route('member-memberships.store'), $data)->assertSessionHasNoErrors();
            $membership = MemberMembership::where('member_id', $data['member_id'])->sole();
            $this->assertSame(number_format(2500 - ($discount ?? 0), 2, '.', ''), $membership->final_price);
        }
    }

    public function test_invalid_discounts_are_rejected(): void
    {
        foreach ([-1, 2500.01, 'invalid', 0.001] as $discount) {
            $this->post(route('member-memberships.store'), $this->assignment([
                'discount_amount' => $discount,
            ]))->assertSessionHasErrors('discount_amount');
        }
        $this->assertDatabaseCount('member_memberships', 0);
    }

    public function test_reason_length_is_validated(): void
    {
        $this->post(route('member-memberships.store'), $this->assignment([
            'discount_reason' => str_repeat('x', 256),
        ]))->assertSessionHasErrors('discount_reason');
    }

    public function test_renewal_uses_current_plan_price_without_carrying_discount(): void
    {
        $this->post(route('member-memberships.store'), $this->assignment(['discount_amount' => 500]));
        $old = MemberMembership::sole();
        $old->update(['status' => 'expired']);
        $old->plan->update(['price' => 3000]);
        $this->post(route('member-memberships.renew', $old))->assertSessionHasNoErrors();
        $renewed = MemberMembership::latest('id')->first();
        $this->assertSame('3000.00', $renewed->final_price);
        $this->assertSame('0.00', $renewed->discount_amount);
        $this->assertSame('2000.00', $old->fresh()->final_price);
    }

    public function test_migration_backfills_existing_memberships(): void
    {
        $data = $this->assignment();
        $migration = require database_path('migrations/2026_09_13_000000_add_discounts_to_member_memberships_table.php');
        $migration->down();
        DB::table('member_memberships')->insert([
            'member_id' => $data['member_id'], 'membership_plan_id' => $data['membership_plan_id'],
            'price' => 2500, 'start_date' => '2026-09-13', 'end_date' => '2026-10-12',
            'status' => 'active',
        ]);
        $migration->up();
        $this->assertSame('2500.00', MemberMembership::sole()->final_price);
        $this->assertSame('0.00', MemberMembership::sole()->discount_amount);
        $migration->up();
        $this->assertSame('2500.00', MemberMembership::sole()->final_price);
    }
}

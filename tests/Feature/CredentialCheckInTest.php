<?php

namespace Tests\Feature;

use App\Enums\AccessCredentialType;
use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberAccessCredential;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Services\MemberAccessCredentials\MemberAccessCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function eligibleMember(): Member
    {
        $member = Member::factory()->create(['status' => 'active']);
        $plan = MembershipPlan::create([
            'name' => 'Monthly',
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

    public function test_credential_route_requires_staff_or_admin(): void
    {
        $this->post(route('checkins.credential'))->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->post(route('checkins.credential'))->assertForbidden();
    }

    public function test_qr_credential_checks_in_member_and_records_usage(): void
    {
        $this->actingAsRole('Staff');
        $issued = app(MemberAccessCredentialService::class)->issue(
            $this->eligibleMember(),
            AccessCredentialType::QR
        );

        $this->post(route('checkins.credential'), [
            'credential' => $issued->token,
            'type' => 'qr',
        ])->assertSessionHasNoErrors();

        $checkIn = CheckIn::sole();
        $this->assertSame(CheckInMethod::QR, $checkIn->method);
        $this->assertSame($issued->credential->member_id, $checkIn->member_id);
        $this->assertNotNull($issued->credential->fresh()->last_used_at);
    }

    public function test_pre_normalization_qr_credential_can_still_check_in(): void
    {
        $this->actingAsRole('Staff');
        $member = $this->eligibleMember();
        $token = 'LegacyMixedCaseQrToken';
        MemberAccessCredential::create([
            'member_id' => $member->id,
            'type' => AccessCredentialType::QR,
            'credential_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->post(route('checkins.credential'), [
            'credential' => $token,
            'type' => 'qr',
        ])->assertSessionHasNoErrors();

        $this->assertSame($member->id, CheckIn::sole()->member_id);
    }

    public function test_rfid_credential_maps_to_rfid_check_in(): void
    {
        $this->actingAsRole('Staff');
        $credential = app(MemberAccessCredentialService::class)->issueRfidCredential(
            $this->eligibleMember(),
            '04A1B2'
        );

        $this->post(route('checkins.credential'), [
            'credential' => '04-a1 b2',
            'type' => 'rfid',
        ])->assertSessionHasNoErrors();

        $checkIn = CheckIn::sole();
        $this->assertSame(CheckInMethod::RFID, $checkIn->method);
        $this->assertSame($credential->member_id, $checkIn->member_id);
        $this->assertNotNull($credential->fresh()->last_used_at);
    }

    public function test_invalid_or_revoked_credential_is_rejected(): void
    {
        $this->actingAsRole('Staff');
        $service = app(MemberAccessCredentialService::class);
        $issued = $service->issue($this->eligibleMember(), AccessCredentialType::QR);
        $service->revoke($issued->credential);

        foreach (['invalid-token', $issued->token] as $token) {
            $this->post(route('checkins.credential'), [
                'credential' => $token,
                'type' => 'qr',
            ])->assertSessionHasErrors('credential');
        }

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_failed_member_validation_does_not_record_credential_usage(): void
    {
        $this->actingAsRole('Staff');
        $issued = app(MemberAccessCredentialService::class)->issue(
            Member::factory()->create(['status' => 'inactive']),
            AccessCredentialType::QR
        );

        $this->post(route('checkins.credential'), [
            'credential' => $issued->token,
            'type' => 'qr',
        ])->assertSessionHasErrors('member_id');

        $this->assertNull($issued->credential->fresh()->last_used_at);
        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_credential_type_is_required_and_validated(): void
    {
        $this->actingAsRole('Staff');

        $token = 'sensitive-scanned-token';
        $this->post(route('checkins.credential'), [
            'credential' => $token,
            'type' => 'biometric',
        ])->assertSessionHasErrors('type');

        $this->assertArrayNotHasKey('credential', session()->getOldInput());
    }
}

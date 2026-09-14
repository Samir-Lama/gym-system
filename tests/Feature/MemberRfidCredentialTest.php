<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRfidCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfid_registration_requires_staff_or_admin(): void
    {
        $member = Member::factory()->create();

        $this->post(route('members.rfid.register', $member), [
            'credential' => '04A1B2',
        ])->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->post(route('members.rfid.register', $member), [
            'credential' => '04A1B2',
        ])->assertForbidden();
    }

    public function test_staff_can_register_an_rfid_card_for_a_member(): void
    {
        $this->actingAsRole('Staff');
        $member = Member::factory()->create();

        $this->from(route('members.show', $member))
            ->post(route('members.rfid.register', $member), [
                'credential' => '04-a1 b2',
            ])
            ->assertRedirect(route('members.show', $member))
            ->assertSessionHas('success', 'RFID card registered successfully.');

        $this->assertDatabaseHas('member_access_credentials', [
            'member_id' => $member->id,
            'type' => 'rfid',
            'credential_hash' => hash('sha256', '04A1B2'),
            'is_active' => true,
        ]);
    }

    public function test_rfid_registration_validates_and_rejects_duplicate_cards(): void
    {
        $this->actingAsRole('Staff');
        $firstMember = Member::factory()->create();
        $secondMember = Member::factory()->create();

        $this->post(route('members.rfid.register', $firstMember), [
            'credential' => '04 A1 B2',
        ])->assertSessionHasNoErrors();

        $this->post(route('members.rfid.register', $secondMember), [
            'credential' => '04-a1-b2',
        ])->assertSessionHasErrors('credential');

        $this->post(route('members.rfid.register', $secondMember), [
            'credential' => '',
        ])->assertSessionHasErrors('credential');

        $this->assertDatabaseCount('member_access_credentials', 1);
    }
}

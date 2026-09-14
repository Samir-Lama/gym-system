<?php

namespace Tests\Feature;

use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\Enums\AccessCredentialType;
use App\Models\Member;
use App\Models\MemberAccessCredential;
use App\Services\MemberAccessCredentials\MemberAccessCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberAccessCredentialTest extends TestCase
{
    use RefreshDatabase;

    private MemberAccessCredentialService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MemberAccessCredentialService::class);
    }

    public function test_issuance_returns_raw_token_and_stores_only_its_hash(): void
    {
        $member = Member::factory()->create();

        $issued = $this->service->issue(
            $member,
            AccessCredentialType::QR,
            'Membership card'
        );

        $this->assertSame(64, strlen($issued->token));
        $this->assertSame(
            hash('sha256', strtoupper($issued->token)),
            $issued->credential->credential_hash
        );
        $this->assertNotSame($issued->token, $issued->credential->credential_hash);
        $this->assertSame(AccessCredentialType::QR, $issued->credential->type);
        $this->assertSame('Membership card', $issued->credential->label);
        $this->assertTrue($issued->credential->is_active);
        $this->assertArrayNotHasKey('credential_hash', $issued->credential->toArray());
        $this->assertDatabaseMissing('member_access_credentials', [
            'credential_hash' => $issued->token,
        ]);
    }

    public function test_active_token_can_be_resolved_for_the_expected_type(): void
    {
        $issued = $this->service->issue(
            Member::factory()->create(),
            AccessCredentialType::QR
        );

        $resolved = $this->service->resolve($issued->token, AccessCredentialType::QR);

        $this->assertTrue($resolved->is($issued->credential));

        $this->expectException(ValidationException::class);
        $this->service->resolve($issued->token, AccessCredentialType::RFID);
    }

    public function test_pre_normalization_qr_token_can_still_be_resolved(): void
    {
        $token = 'LegacyMixedCaseQrToken';
        $credential = MemberAccessCredential::create([
            'member_id' => Member::factory()->create()->id,
            'type' => AccessCredentialType::QR,
            'credential_hash' => hash('sha256', $token),
            'is_active' => true,
        ]);

        $this->assertTrue(
            $this->service->findByToken($token, AccessCredentialType::QR)
                ->is($credential)
        );
    }

    public function test_issuing_a_new_credential_rotates_only_the_same_type(): void
    {
        $member = Member::factory()->create();
        $oldQr = $this->service->issue($member, AccessCredentialType::QR);
        $rfid = $this->service->issue($member, AccessCredentialType::RFID);
        $newQr = $this->service->issue($member, AccessCredentialType::QR);

        $this->assertFalse($oldQr->credential->fresh()->is_active);
        $this->assertTrue($rfid->credential->fresh()->is_active);
        $this->assertTrue($newQr->credential->fresh()->is_active);

        try {
            $this->service->resolve($oldQr->token, AccessCredentialType::QR);
            $this->fail('A rotated credential should be inactive.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('credential', $exception->errors());
        }

        $this->assertTrue(
            $this->service->resolve($newQr->token, AccessCredentialType::QR)
                ->is($newQr->credential)
        );
    }

    public function test_rfid_uid_is_normalized_for_issuance_and_lookup(): void
    {
        $member = Member::factory()->create();

        $credential = $this->service->issueRfidCredential($member, ' 04-a1 b2 ');

        $this->assertSame(
            hash('sha256', '04A1B2'),
            $credential->credential_hash
        );
        $this->assertSame(AccessCredentialType::RFID, $credential->type);
        $this->assertTrue($credential->is_active);
        $this->assertTrue(
            $this->service->findByToken('04 A1-B2', AccessCredentialType::RFID)
                ->is($credential)
        );
    }

    public function test_registering_rfid_rotates_the_members_card(): void
    {
        $member = Member::factory()->create();
        $first = $this->service->issueRfidCredential($member, '04A1B2');
        $second = $this->service->issueRfidCredential($member, '05C3D4');

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_duplicate_rfid_card_is_rejected_even_when_inactive(): void
    {
        $credential = $this->service->issueRfidCredential(
            Member::factory()->create(),
            '04A1B2'
        );
        $this->service->revoke($credential);

        try {
            $this->service->issueRfidCredential(
                Member::factory()->create(),
                '04-A1-B2'
            );
            $this->fail('A duplicate RFID card should not be registered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('credential', $exception->errors());
        }
    }

    public function test_rfid_uid_must_contain_alphanumeric_characters(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->issueRfidCredential(
            Member::factory()->create(),
            ' -- '
        );
    }

    public function test_revocation_and_usage_tracking_update_the_credential(): void
    {
        $issued = $this->service->issue(
            Member::factory()->create(),
            AccessCredentialType::QR
        );

        $used = $this->service->markUsed($issued->credential);
        $this->assertNotNull($used->last_used_at);

        $revoked = $this->service->revoke($used);
        $this->assertFalse($revoked->is_active);

        $this->expectException(ValidationException::class);
        $this->service->resolve($issued->token, AccessCredentialType::QR);
    }

    public function test_inactive_credential_cannot_be_marked_as_used(): void
    {
        $issued = $this->service->issue(
            Member::factory()->create(),
            AccessCredentialType::QR
        );
        $revoked = $this->service->revoke($issued->credential);

        $this->expectException(ValidationException::class);
        $this->service->markUsed($revoked);
    }

    public function test_credentials_are_deleted_with_the_member(): void
    {
        $member = Member::factory()->create();
        $this->service->issue($member, AccessCredentialType::QR);

        $member->delete();

        $this->assertDatabaseCount('member_access_credentials', 0);
    }

    public function test_service_interface_is_bound(): void
    {
        $this->assertInstanceOf(
            MemberAccessCredentialService::class,
            app(MemberAccessCredentialServiceInterface::class)
        );
    }
}

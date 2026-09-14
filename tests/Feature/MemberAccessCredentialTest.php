<?php

namespace Tests\Feature;

use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\Enums\AccessCredentialType;
use App\Models\Member;
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
        $this->assertSame(hash('sha256', $issued->token), $issued->credential->credential_hash);
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

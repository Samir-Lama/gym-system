<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberAccessCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Inertia\Support\Header;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberQrCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_routes_require_staff_or_admin(): void
    {
        $member = Member::factory()->create();

        $this->post(route('members.qr.issue', $member))->assertRedirect(route('login'));

        $this->actingAsRole('Member');
        $this->post(route('members.qr.issue', $member))->assertForbidden();
    }

    public function test_issuing_qr_redirects_to_one_time_printable_card(): void
    {
        $this->actingAsRole('Staff');
        $member = Member::factory()->create();

        $response = $this->post(route('members.qr.issue', $member));
        $response->assertRedirect(route('members.qr.show', $member));
        $encryptedToken = $response->getSession()->get("issued_qr_credentials.{$member->id}");
        $token = Crypt::decryptString($encryptedToken);

        $this->assertNotNull($token);
        $this->assertNotSame($token, $encryptedToken);
        $this->assertDatabaseHas('member_access_credentials', [
            'member_id' => $member->id,
            'credential_hash' => hash('sha256', $token),
            'type' => 'qr',
            'is_active' => true,
        ]);

        $cardResponse = $this->get(route('members.qr.show', $member));
        $cardResponse->assertOk()
            ->assertInertia(function (Assert $page) use ($member) {
                $page
                    ->component('Members/QrCredential')
                    ->where('member.id', $member->id)
                    ->where('qrCode', fn ($value) => str_contains((string) $value, '<svg'));

                $this->assertTrue($page->toArray()['encryptHistory']);
            });

        $this->get(route('members.qr.show', $member))
            ->assertRedirect(route('members.show', $member))
            ->assertSessionHas('error');
    }

    public function test_asset_version_reload_does_not_consume_issued_token(): void
    {
        $this->actingAsRole('Staff');
        $member = Member::factory()->create();
        $this->post(route('members.qr.issue', $member));
        $sessionKey = "issued_qr_credentials.{$member->id}";

        $this->withHeaders([
            Header::INERTIA => 'true',
            Header::VERSION => 'stale-version',
        ])->get(route('members.qr.show', $member))
            ->assertStatus(409)
            ->assertHeader(Header::LOCATION, route('members.qr.show', $member));

        $this->assertNotNull(session()->get($sessionKey));
        $this->withoutHeaders([Header::INERTIA, Header::VERSION]);
        $this->get(route('members.qr.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('member.id', $member->id)
                ->has('qrCode'));
    }

    public function test_reissuing_qr_revokes_the_previous_credential(): void
    {
        $this->actingAsRole('Staff');
        $member = Member::factory()->create();

        $this->post(route('members.qr.issue', $member));
        $first = MemberAccessCredential::sole();
        $this->post(route('members.qr.issue', $member));

        $this->assertFalse($first->fresh()->is_active);
        $this->assertSame(1, MemberAccessCredential::query()
            ->where('member_id', $member->id)
            ->where('type', 'qr')
            ->where('is_active', true)
            ->count());
    }
}

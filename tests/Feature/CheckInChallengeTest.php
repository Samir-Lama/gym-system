<?php

namespace Tests\Feature;

use App\Models\CheckInChallenge;
use App\Services\CheckIns\CheckInChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckInChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_stores_only_hash_and_resolves_until_expiry(): void
    {
        $this->travelTo(now()->startOfSecond());
        $service = app(CheckInChallengeService::class);
        $result = $service->create('main_entrance', 3);

        $this->assertSame(
            hash('sha256', $result['token']),
            $result['challenge']->token_hash
        );
        $this->assertArrayNotHasKey('token_hash', $result['challenge']->toArray());
        $this->assertTrue($service->findValid($result['token'])->is($result['challenge']));

        $this->travel(3)->minutes();

        $this->assertNull($service->findValid($result['token']));
    }

    public function test_used_at_does_not_invalidate_reusable_entrance_challenge(): void
    {
        $service = app(CheckInChallengeService::class);
        $result = $service->create('main_entrance');
        $result['challenge']->update(['used_at' => now()]);

        $this->assertTrue($service->findValid($result['token'])->is($result['challenge']));
    }

    public function test_new_challenge_replaces_current_challenge_at_same_location(): void
    {
        $service = app(CheckInChallengeService::class);
        $first = $service->create('main_entrance');
        $second = $service->create('main_entrance');

        $this->assertNull($service->findValid($first['token']));
        $this->assertTrue($service->findValid($second['token'])->is($second['challenge']));
    }

    public function test_displays_share_current_challenge_until_it_expires(): void
    {
        $service = app(CheckInChallengeService::class);
        $firstDisplay = $service->currentOrCreate('main_entrance');
        $secondDisplay = $service->currentOrCreate('main_entrance');

        $this->assertSame($firstDisplay['token'], $secondDisplay['token']);
        $this->assertTrue($firstDisplay['challenge']->is($secondDisplay['challenge']));
        $this->assertDatabaseCount('check_in_challenges', 1);

        $this->travel(3)->minutes();
        $rotated = $service->currentOrCreate('main_entrance');

        $this->assertNotSame($firstDisplay['token'], $rotated['token']);
        $this->assertNull($service->findValid($firstDisplay['token']));
    }

    public function test_creating_challenge_prunes_records_expired_over_a_day_ago(): void
    {
        CheckInChallenge::create([
            'token_hash' => hash('sha256', 'old-token'),
            'expires_at' => now()->subDays(2),
        ]);

        app(CheckInChallengeService::class)->create('main_entrance');

        $this->assertDatabaseMissing('check_in_challenges', [
            'token_hash' => hash('sha256', 'old-token'),
        ]);
    }

    public function test_invalid_challenge_duration_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(CheckInChallengeService::class)->create(minutes: 0);
    }

    public function test_unknown_challenge_is_not_valid(): void
    {
        $this->assertNull(
            app(CheckInChallengeService::class)->findValid('unknown-token')
        );
        $this->assertDatabaseCount(CheckInChallenge::class, 0);
    }
}

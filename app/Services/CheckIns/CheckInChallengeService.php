<?php

namespace App\Services\CheckIns;

use App\Models\CheckInChallenge;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckInChallengeService
{
    public function create(
        ?string $location = null,
        int $minutes = 3
    ): array {
        $this->validateDuration($minutes);

        return Cache::lock($this->lockKey($location), 10)->block(
            5,
            fn () => $this->createForLocation($location, $minutes)
        );
    }

    public function currentOrCreate(
        ?string $location = null,
        int $minutes = 3
    ): array {
        $this->validateDuration($minutes);

        return Cache::lock($this->lockKey($location), 10)->block(
            5,
            function () use ($location, $minutes) {
                $cached = Cache::get($this->cacheKey($location));

                if (is_array($cached) && isset($cached['token'])) {
                    try {
                        $token = Crypt::decryptString($cached['token']);
                        $challenge = $this->findValid($token);

                        if ($challenge && $challenge->location === $location) {
                            return compact('challenge', 'token');
                        }
                    } catch (DecryptException) {
                        Cache::forget($this->cacheKey($location));
                    }
                }

                return $this->createForLocation($location, $minutes);
            }
        );
    }

    public function findValid(
        string $token,
        bool $lockForUpdate = false,
    ): ?CheckInChallenge {
        if ($token === '') {
            return null;
        }

        return CheckInChallenge::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    private function createForLocation(?string $location, int $minutes): array
    {
        $result = DB::transaction(function () use ($location, $minutes) {
            CheckInChallenge::query()
                ->where('expires_at', '<=', now()->subDay())
                ->delete();

            CheckInChallenge::query()
                ->where('location', $location)
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            $token = Str::random(64);
            $challenge = CheckInChallenge::create([
                'token_hash' => hash('sha256', $token),
                'location' => $location,
                'expires_at' => now()->addMinutes($minutes),
            ]);

            return compact('challenge', 'token');
        });

        Cache::put(
            $this->cacheKey($location),
            ['token' => Crypt::encryptString($result['token'])],
            $result['challenge']->expires_at,
        );

        return $result;
    }

    private function validateDuration(int $minutes): void
    {
        if ($minutes < 1) {
            throw ValidationException::withMessages([
                'minutes' => 'The challenge duration must be at least one minute.',
            ]);
        }
    }

    private function cacheKey(?string $location): string
    {
        return 'current-check-in-challenge:'.hash('sha256', $location ?? 'default');
    }

    private function lockKey(?string $location): string
    {
        return 'check-in-challenge-lock:'.hash('sha256', $location ?? 'default');
    }
}

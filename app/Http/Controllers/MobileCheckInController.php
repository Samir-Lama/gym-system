<?php

namespace App\Http\Controllers;

use App\Contracts\Services\CheckInServiceInterface;
use App\Enums\CheckInMethod;
use App\Services\CheckIns\CheckInChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MobileCheckInController extends Controller
{
    public function __construct(
        private readonly CheckInChallengeService $challengeService,
        private readonly CheckInServiceInterface $checkInService,
    ) {}

    public function display(): Response
    {
        $result = $this->challengeService->currentOrCreate(
            location: 'main_entrance',
            minutes: 3,
        );
        $checkInUrl = route('mobile-checkin.show', [
            'token' => $result['token'],
        ]);

        return Inertia::render('CheckIns/Display', [
            'checkInUrl' => $checkInUrl,
            'expiresAt' => $result['challenge']->expires_at,
            'location' => $result['challenge']->location,
            'serverTime' => now(),
        ]);
    }

    public function challenge(): JsonResponse
    {
        $result = $this->challengeService->currentOrCreate(
            location: 'main_entrance',
            minutes: 3,
        );

        return response()->json([
            'checkInUrl' => route('mobile-checkin.show', [
                'token' => $result['token'],
            ]),
            'expiresAt' => $result['challenge']->expires_at->toIso8601String(),
            'location' => $result['challenge']->location,
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function show(string $token): Response
    {
        $challenge = $this->challengeService->findValid($token);

        abort_if(! $challenge, 410, 'This check-in QR has expired.');

        return Inertia::render('CheckIns/Mobile', [
            'token' => $token,
            'location' => $challenge->location,
            'expiresAt' => $challenge->expires_at,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);
        DB::transaction(function () use ($request, $validated) {
            $challenge = $this->challengeService->findValid(
                $validated['token'],
                lockForUpdate: true,
            );

            if (! $challenge) {
                throw ValidationException::withMessages([
                    'token' => 'This check-in QR has expired. Please scan the current QR.',
                ]);
            }

            $user = $request->user();

            if (! $user->hasRole('Member')) {
                throw ValidationException::withMessages([
                    'member' => 'Your account is not authorized for member check-in.',
                ]);
            }

            $member = $user->member;

            if (! $member) {
                throw ValidationException::withMessages([
                    'member' => 'No gym member profile is linked to this account.',
                ]);
            }

            $this->checkInService->checkIn(
                memberId: $member->id,
                method: CheckInMethod::MOBILE,
                deviceId: $challenge->location,
            );

            if (! $challenge->expires_at->isFuture()) {
                throw ValidationException::withMessages([
                    'token' => 'This check-in QR has expired. Please scan the current QR.',
                ]);
            }
        });

        return redirect()
            ->route('mobile-checkin.success')
            ->with('success', 'Check-in successful.');
    }

    public function success(): Response
    {
        return Inertia::render('CheckIns/MobileSuccess');
    }
}

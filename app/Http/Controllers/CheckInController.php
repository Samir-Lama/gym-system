<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Services\CheckInServiceInterface;
use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\DTOs\CheckIns\CreateCheckInData;
use App\Enums\AccessCredentialType;
use App\Enums\CheckInMethod;
use App\Http\Requests\CheckIns\StoreCheckInRequest;
use App\Models\CheckIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CheckInController extends Controller
{
    public function __construct(
        private readonly CheckInServiceInterface $checkInService,
        private readonly MemberRepositoryInterface $memberRepository,
        private readonly MemberAccessCredentialServiceInterface $memberAccessCredentialService,
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'presence' => ['nullable', Rule::in(['open', 'closed'])],
            'method' => ['nullable', Rule::enum(CheckInMethod::class)],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                ...($request->filled('from_date') ? ['after_or_equal:from_date'] : []),
            ],
        ]);

        $filters = [
            'search' => trim($validated['search'] ?? ''),
            'presence' => $validated['presence'] ?? '',
            'method' => $validated['method'] ?? '',
            'from_date' => $validated['from_date'] ?? '',
            'to_date' => $validated['to_date'] ?? '',
        ];

        return Inertia::render('CheckIns/Index', [
            'checkIns' => $this->checkInService->paginate(
                perPage: 15,
                search: $filters['search'] === '' ? null : $filters['search'],
                presence: $filters['presence'] ?: null,
                method: $filters['method'] ?: null,
                fromDate: $filters['from_date'] ?: null,
                toDate: $filters['to_date'] ?: null,
            ),
            'filters' => $filters,
            'methods' => CheckInMethod::options(),
        ]);
    }

    public function store(StoreCheckInRequest $request): RedirectResponse
    {
        $data = CreateCheckInData::fromRequest($request);

        $this->checkInService->checkIn(
            memberId: $data->memberId,
            method: $data->method,
            deviceId: $data->deviceId,
            notes: $data->notes,
        );

        return back()->with('success', 'Member checked in successfully.');
    }

    public function checkout(CheckIn $checkIn): RedirectResponse
    {
        $this->checkInService->checkOut($checkIn);

        return back()->with('success', 'Member checked out successfully.');
    }

    public function credentialCheckIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', 'max:512'],
            'type' => ['required', Rule::enum(AccessCredentialType::class)],
        ]);

        $this->memberAccessCredentialService->checkInWithCredential(
            trim($validated['credential']),
            AccessCredentialType::from($validated['type'])
        );

        return back()->with('success', 'Member checked in successfully.');
    }

    public function searchMembers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->memberRepository->searchForCheckIn($search)
        );
    }
}

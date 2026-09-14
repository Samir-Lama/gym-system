<?php

namespace App\Http\Controllers;

use App\Contracts\Services\PaymentServiceInterface;
use App\DTOs\Payments\CreatePaymentData;
use App\Enums\MembershipStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from_date') ? ['after_or_equal:from_date'] : [])],
        ]);

        $filters = [
            'search' => trim($validated['search'] ?? ''),
            'status' => $validated['status'] ?? '',
            'method' => $validated['method'] ?? '',
            'from_date' => $validated['from_date'] ?? '',
            'to_date' => $validated['to_date'] ?? '',
        ];

        $payments = $this->paymentService->paginate(
            perPage: 15,
            search: $filters['search'] === '' ? null : $filters['search'],
            status: $filters['status'] ?: null,
            method: $filters['method'] ?: null,
            fromDate: $filters['from_date'] ?: null,
            toDate: $filters['to_date'] ?: null,
        );

        return Inertia::render('Billing/Index', [
            'payments' => $payments,
            'filters' => $filters,
        ]);
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['member', 'membership.plan']);

        return Inertia::render('Billing/Show', [
            'payment' => $payment,
        ]);
    }

    public function searchMembers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $membersQuery = Member::query();
        $fullName = $membersQuery->getConnection()->getDriverName() === 'sqlite'
            ? "first_name || ' ' || last_name"
            : "CONCAT(first_name, ' ', last_name)";

        $members = $membersQuery
            ->where(function ($query) use ($search, $fullName) {
                $query
                    ->where('membership_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereRaw(
                        "{$fullName} LIKE ?",
                        ["%{$search}%"]
                    );
            })
            ->with([
                'memberships' => fn ($query) => $query
                    ->where('status', MembershipStatus::ACTIVE)
                    ->latest()
                    ->with('plan'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get([
                'id',
                'membership_number',
                'first_name',
                'last_name',
                'email',
                'phone',
            ]);

        return response()->json($members);
    }

    public function store(
        StorePaymentRequest $request
    ): RedirectResponse {
        $data = CreatePaymentData::fromRequest($request);

        $this->paymentService->create($data);

        return back()->with(
            'success',
            'Payment recorded successfully.'
        );
    }
}

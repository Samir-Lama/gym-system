<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MemberServiceInterface;
use App\DTOs\Members\CreateMemberData;
use App\DTOs\Members\UpdateMemberData;
use App\Enums\MembershipPlanStatus;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberServiceInterface $memberService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $status = $request->filled('status')
            ? MemberStatus::tryFrom(
                $request->string('status')->value()
            )
            : null;

        $allowedSorts = [
            'first_name',
            'email',
            'status',
            'joined_at',
            'created_at',
        ];

        $sort = $request->string('sort')->value();

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $direction = $request->string('direction')->value();

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $members = $this->memberService->paginate(
            perPage: 10,
            search: $search ?: null,
            status: $status,
            sort: $sort,
            direction: $direction,
        );

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Members/Create');
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $data = CreateMemberData::formRequest($request);
        $this->memberService->create($data);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member created successfully.');
    }

    public function show(Member $member): Response
    {
        $plans = MembershipPlan::query()
            ->where('status', MembershipPlanStatus::ACTIVE)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'price',
                'duration_days',
            ]);

        $memberships = $member
            ->memberships()
            ->with('plan')
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'membership_page')
            ->withQueryString();

        $currentMembership = $member
            ->memberships()
            ->with('plan')
            ->whereIn('status', [
                MembershipStatus::ACTIVE,
                MembershipStatus::PAUSED,
            ])
            ->latest()
            ->first();

        $linkedUserIds = Member::query()
            ->whereNotNull('user_id')
            ->whereKeyNot($member->id)
            ->pluck('user_id');
        $memberAccounts = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Member'))
            ->whereNotIn('id', $linkedUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Members/Show', [
            'member' => $member,
            'plans' => $plans,
            'memberships' => $memberships,
            'currentMembership' => $currentMembership,
            'memberAccounts' => $memberAccounts,
        ]);
    }

    public function edit(Member $member): Response
    {
        return Inertia::render('Members/Edit', [
            'member' => $member,
        ]);
    }

    public function update(
        UpdateMemberRequest $request,
        Member $member
    ): RedirectResponse {
        $data = UpdateMemberData::formRequest($request);

        $this->memberService->update($member, $data);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member updated successullly.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->memberService->delete($member);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }

    public function bulkStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'member_ids.*' => [
                'integer',
                'exists:members,id',
            ],

            'status' => [
                'required',
                Rule::enum(MemberStatus::class),
            ],
        ]);

        $status = MemberStatus::from(
            $validated['status']
        );

        $this->memberService->bulkUpdateStatus(
            $validated['member_ids'],
            $status,
        );

        return back()->with(
            'success',
            'Member statuses updated successfully.'
        );
    }

    public function linkAccount(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['present', 'nullable', 'integer', 'exists:users,id'],
        ]);
        $userId = $validated['user_id'] ?? null;

        if (! $userId) {
            $member->update(['user_id' => null]);

            return back()->with('success', 'Member account unlinked successfully.');
        }

        try {
            DB::transaction(function () use ($member, $userId) {
                $member = Member::query()->lockForUpdate()->findOrFail($member->id);
                $user = User::query()->lockForUpdate()->findOrFail($userId);

                if (! $user->hasRole('Member')) {
                    throw ValidationException::withMessages([
                        'user_id' => 'The selected user must have the Member role.',
                    ]);
                }

                if (Member::query()
                    ->where('user_id', $user->id)
                    ->whereKeyNot($member->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'user_id' => 'This user is already linked to another member.',
                    ]);
                }

                $member->update(['user_id' => $user->id]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'user_id' => 'This user is already linked to another member.',
            ]);
        }

        return back()->with('success', 'Member account linked successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'member_ids.*' => [
                'integer',
                'exists:members,id',
            ],
        ]);

        $count = $this->memberService->bulkDelete(
            $validated['member_ids']
        );

        return back()->with(
            'success',
            "{$count} member".($count === 1 ? '' : 's').' deleted successfully.'
        );
    }
}

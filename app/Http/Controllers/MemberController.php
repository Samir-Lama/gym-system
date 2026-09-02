<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MemberServiceInterface;
use App\DTOs\Members\CreateMemberData;
use App\DTOs\Members\UpdateMemberData;
use App\Enums\MemberStatus;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $direction = $request->string('direction')->value();

        if (!in_array($direction, ['asc', 'desc'], true)) {
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
        return Inertia::render('Members/Show', [
            'member' => [
                ...$member->toArray(),
                'status' => $member->status?->value,
                'status_label' => $member->status?->label(),
                'status_color' => $member->status?->color(),
            ]
        ]);
    }

    public function edit(Member $member): Response
    {
        return Inertia::render('Members/Edit', [
            'member' => $member
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
            "{$count} member" . ($count === 1 ? '' : 's') . " deleted successfully."
        );
    }
}

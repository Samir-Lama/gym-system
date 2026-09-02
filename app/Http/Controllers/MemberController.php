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
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberServiceInterface $memberService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->value() ?: null;

        $statusValue = $request->string('status')->value() ?: null;

        $status = $statusValue
            ? MemberStatus::tryFrom($statusValue)
            : null;

        $members = $this->memberService->paginate(
            10,
            $search,
            $status,
        );

        return Inertia::render('Members/Index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'status' => $status?->value,
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
}

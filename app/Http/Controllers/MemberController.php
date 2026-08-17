<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MemberServiceInterface;
use App\DTOs\Members\CreateMemberData;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberServiceInterface $memberService
    ) {}

    public function index(): Response
    {
        $members = Member::latest()->paginate(10);
        return Inertia::render('Members/Index', [
            'members' => $members
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
            'member' => $member,
        ]);
    }

    public function edit(Member $member): Response
    {
        return Inertia::render('Members/Edit', [
            'member' => $member
        ]);
    }

    public function update(
        StoreMemberRequest $request,
        Member $member
    ): RedirectResponse {
        $data = CreateMemberData::formRequest($request);

        $this->memberService->update($member, $data);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member updated successullly.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Contracts\Services\MemberMembershipServiceInterface;
use App\DTOs\MemberMemberships\CreateMemberMembershipData;
use App\Http\Requests\MemberMemberships\StoreMemberMembershipRequest;
use App\Models\MemberMembership;
use Illuminate\Http\RedirectResponse;

class MemberMembershipController extends Controller
{
    public function __construct(
        private readonly MemberMembershipServiceInterface $memberMembershipService
    ) {}

    public function store(
        StoreMemberMembershipRequest $request
    ): RedirectResponse {
        $data = CreateMemberMembershipData::fromRequest($request);

        $this->memberMembershipService->create($data);

        return redirect()
            ->route('members.show', $data->memberId)
            ->with('success', 'Membership assigned successfully.');
    }

    public function pause(
        MemberMembership $memberMembership
    ): RedirectResponse {
        $this->memberMembershipService->pause($memberMembership);

        return back()->with('success', 'Membership paused successfully.');
    }

    public function cancel(
        MemberMembership $memberMembership
    ): RedirectResponse {
        $this->memberMembershipService->cancel($memberMembership);

        return back()->with('success', 'Membership cancelled successfully.');
    }

    public function renew(
        MemberMembership $memberMembership
    ): RedirectResponse {
        $this->memberMembershipService->renew($memberMembership);

        return back()->with('success', 'Membership renewed successfully.');
    }

    public function resume(
        MemberMembership $memberMembership
    ): RedirectResponse {
        $this->memberMembershipService->resume($memberMembership);

        return back()->with(
            'success',
            'Membership resumed successfully.'
        );
    }
}

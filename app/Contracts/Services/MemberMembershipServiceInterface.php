<?php

namespace App\Contracts\Services;

use App\DTOs\MemberMemberships\CreateMemberMembershipData;
use App\Models\MemberMembership;

interface MemberMembershipServiceInterface
{
    public function create(
        CreateMemberMembershipData $data
    ): MemberMembership;

    public function pause(
        MemberMembership $membership
    ): MemberMembership;

    public function cancel(
        MemberMembership $membership
    ): MemberMembership;

    public function renew(
        MemberMembership $membership
    ): MemberMembership;

    public function resume(
        MemberMembership $membership
    ): MemberMembership;
}

<?php

namespace App\Contracts\Services;

use App\DTOs\Members\CreateMemberData;
use App\Models\Member;

interface MemberServiceInterface
{
    public function create(CreateMemberData $data): Member;

    public function update(Member $member, CreateMemberData $data): Member;
}

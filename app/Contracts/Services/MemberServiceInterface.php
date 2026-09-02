<?php

namespace App\Contracts\Services;

use App\DTOs\Members\CreateMemberData;
use App\DTOs\Members\UpdateMemberData;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberServiceInterface
{
    public function create(CreateMemberData $data): Member;

    public function update(Member $member, UpdateMemberData $data): Member;

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
    ): LengthAwarePaginator;
}

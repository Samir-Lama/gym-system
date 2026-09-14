<?php

namespace App\Contracts\Repositories;

use App\Enums\MemberStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateMembers(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
        string $sort = 'created_at',
        string $direction = 'desc',
    ): LengthAwarePaginator;

    public function existsByMembershipNumber(
        string $membershipNumber
    ): bool;

    public function updateStatusByIds(
        array $ids,
        MemberStatus $status
    ): int;
}

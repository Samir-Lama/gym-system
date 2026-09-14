<?php

namespace App\Contracts\Repositories;

use App\Enums\MemberStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    public function searchForCheckIn(
        string $search,
        int $limit = 10
    ): Collection;

    public function updateStatusByIds(
        array $ids,
        MemberStatus $status
    ): int;
}

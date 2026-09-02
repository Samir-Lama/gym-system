<?php

namespace App\Contracts\Repositories;

use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MemberRepositoryInterface
{
    public function create(array $data): Member;

    public function update(Member $member, array $data): Member;

    public function delete(Member $member): void;

    public function find(int $id): ?Member;

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
        string $sort = 'created_at',
        string $direction = 'desc',
    ): LengthAwarePaginator;

    public function existsByMembershipNumber(string $membershipNumber): bool;

    public function updateStatusByIds(
        array $ids,
        MemberStatus $status
    ): int;

    public function deleteByIds(array $ids): int;
}

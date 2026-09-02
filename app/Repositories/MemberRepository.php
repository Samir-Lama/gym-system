<?php

namespace App\Repositories;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberRepository implements MemberRepositoryInterface
{
    public function create(array $data): Member
    {
        return Member::create($data);
    }

    public function update(Member $member, array $data): Member
    {
        $member->update($data);
        return $member;
    }

    public function delete(Member $member): void
    {
        $member->delete();
    }

    public function find(int $id): ?Member
    {
        return Member::find($id);
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
        string $sort = 'created_at',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        return Member::query()->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('membership_number', 'like', "%{$search}%");
            });
        })->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function existsByMembershipNumber(string $membershipNumber): bool
    {
        return Member::query()->where('membership_number', $membershipNumber)->exists();
    }

    public function updateStatusByIds(
        array $ids,
        MemberStatus $status
    ): int {
        return Member::query()->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function deleteByIds(array $ids): int {
        return Member::query()->whereIn('id', $ids)->delete();
    }
}

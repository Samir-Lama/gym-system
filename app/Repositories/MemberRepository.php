<?php

namespace App\Repositories;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberRepository implements MemberRepositoryInterface
{
    public function create(array $data): Member
    {
        return Member::create($data);
    }

    public function update(Member $member, array $data): Member {
        $member->update($data);
        return $member;
    }

    public function delete (Member $member): void {
        $member->delete();
    }

    public function find(int $id): ?Member {
        return Member::find($id);
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator {
        return Member::query()->latest()->paginate($perPage);
    } 

    public function existsByMembershipNumber(string $membershipNumber): bool{
        return Member::query()->where('membership_number', $membershipNumber)->exists();
    }

}

<?php

namespace App\Repositories;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberRepository extends BaseRepository implements MemberRepositoryInterface
{
    public function __construct(Member $model)
    {
        parent::__construct($model);
    }

    public function paginateMembers(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
        string $sort = 'created_at',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        return $this->model
            ->newQuery()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere(
                            'membership_number',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->when(
                $status,
                fn($query) => $query->where('status', $status)
            )
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function existsByMembershipNumber(
        string $membershipNumber
    ): bool {
        return $this->model
            ->newQuery()
            ->where('membership_number', $membershipNumber)
            ->exists();
    }

    public function updateStatusByIds(
        array $ids,
        MemberStatus $status
    ): int {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->update([
                'status' => $status,
            ]);
    }
}

<?php

namespace App\Repositories;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
                fn ($query) => $query->where('status', $status)
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

    public function searchForCheckIn(
        string $search,
        int $limit = 10
    ): Collection {
        $query = $this->model->newQuery();
        $driver = $query->getConnection()->getDriverName();
        $fullName = $driver === 'sqlite'
            ? "first_name || ' ' || last_name"
            : "CONCAT(first_name, ' ', last_name)";
        $like = $driver === 'pgsql' ? 'ilike' : 'like';

        return $query
            ->where(function ($query) use ($search, $fullName, $like) {
                $query
                    ->where('membership_number', $like, "%{$search}%")
                    ->orWhere('first_name', $like, "%{$search}%")
                    ->orWhere('last_name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->orWhere('phone', $like, "%{$search}%")
                    ->orWhereRaw("{$fullName} {$like} ?", ["%{$search}%"]);
            })
            ->with([
                'memberships' => fn ($query) => $query
                    ->where('status', MembershipStatus::ACTIVE)
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today())
                    ->latest('start_date')
                    ->with('plan'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit($limit)
            ->get([
                'id',
                'membership_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'status',
            ]);
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

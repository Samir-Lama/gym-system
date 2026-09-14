<?php

namespace App\Repositories;

use App\Models\CheckIn;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CheckInRepository extends BaseRepository
{
    public function __construct(CheckIn $model)
    {
        parent::__construct($model);
    }

    public function hasOpenCheckIn(int $memberId): bool
    {
        return $this->model
            ->newQuery()
            ->where('member_id', $memberId)
            ->whereNull('check_out_at')
            ->exists();
    }

    public function paginateCheckIns(
        int $perPage = 15,
        ?string $search = null,
        ?string $presence = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator {
        return $this->model
            ->newQuery()
            ->with([
                'member:id,membership_number,first_name,last_name',
                'membership.plan:id,name',
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->whereHas('member', function ($memberQuery) use ($search) {
                    $driver = $memberQuery->getConnection()->getDriverName();
                    $fullName = $driver === 'sqlite'
                        ? "first_name || ' ' || last_name"
                        : "CONCAT(first_name, ' ', last_name)";
                    $like = $driver === 'pgsql' ? 'ilike' : 'like';

                    $memberQuery->where(function ($memberQuery) use ($search, $fullName, $like) {
                        $memberQuery->where('membership_number', $like, "%{$search}%")
                            ->orWhere('first_name', $like, "%{$search}%")
                            ->orWhere('last_name', $like, "%{$search}%")
                            ->orWhereRaw("{$fullName} {$like} ?", ["%{$search}%"]);
                    });
                });
            })
            ->when($presence === 'open', fn ($query) => $query->whereNull('check_out_at'))
            ->when($presence === 'closed', fn ($query) => $query->whereNotNull('check_out_at'))
            ->when($method, fn ($query) => $query->where('method', $method))
            ->when($fromDate, fn ($query) => $query->where(
                'check_in_at', '>=', CarbonImmutable::parse($fromDate)->startOfDay()
            ))
            ->when($toDate, fn ($query) => $query->where(
                'check_in_at', '<', CarbonImmutable::parse($toDate)->addDay()->startOfDay()
            ))
            ->orderByDesc('check_in_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}

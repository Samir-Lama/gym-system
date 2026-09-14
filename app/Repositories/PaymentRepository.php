<?php

namespace App\Repositories;

use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function paginatePayments(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator {
        return $this->model
            ->newQuery()
            ->with('member')
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $fullName = $memberQuery->getConnection()->getDriverName() === 'sqlite'
                                ? "first_name || ' ' || last_name"
                                : "CONCAT(first_name, ' ', last_name)";

                            $memberQuery->where(function ($memberQuery) use ($search, $fullName) {
                                $memberQuery->where('membership_number', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("{$fullName} LIKE ?", ["%{$search}%"]);
                            });
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('payment_status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->when($fromDate, fn ($query) => $query->where('paid_at', '>=', $fromDate.' 00:00:00'))
            ->when($toDate, fn ($query) => $query->where(
                'paid_at', '<', CarbonImmutable::parse($toDate)->addDay()->startOfDay()
            ))
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}

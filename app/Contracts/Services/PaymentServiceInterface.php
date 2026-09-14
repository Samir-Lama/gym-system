<?php

namespace App\Contracts\Services;

use App\DTOs\Payments\CreatePaymentData;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentServiceInterface
{
    public function create(
        CreatePaymentData $data
    ): Payment;

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator;
}

<?php

namespace App\Services\Payments;

use App\Contracts\Services\PaymentServiceInterface;
use App\DTOs\Payments\CreatePaymentData;
use App\Enums\MembershipStatus;
use App\Models\MemberMembership;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PaymentService extends BaseService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository
    ) {
        parent::__construct($paymentRepository);
    }

    public function create(
        CreatePaymentData $data
    ): Payment {
        $membership = MemberMembership::query()
            ->where('id', $data->memberMembershipId)
            ->where('member_id', $data->memberId)
            ->first();

        if (! $membership) {
            throw ValidationException::withMessages([
                'member_membership_id' => 'The selected membership does not belong to this member.',
            ]);
        }

        if ($membership->status !== MembershipStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'member_membership_id' => 'Payments can only be recorded for active memberships.',
            ]);
        }

        return $this->paymentRepository->create([
            'member_id' => $data->memberId,
            'member_membership_id' => $membership->id,
            'amount' => $membership->final_price,
            'payment_method' => $data->paymentMethod,
            'payment_status' => $data->paymentStatus,
            'paid_at' => $data->paidAt,
            'reference' => $data->reference,
            'notes' => $data->notes,
        ]);
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $status = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator {
        return $this->paymentRepository->paginatePayments(
            perPage: $perPage,
            search: $search,
            status: $status,
            method: $method,
            fromDate: $fromDate,
            toDate: $toDate,
        );
    }
}

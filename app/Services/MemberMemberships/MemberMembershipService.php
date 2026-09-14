<?php

namespace App\Services\MemberMemberships;

use App\Contracts\Services\MemberMembershipServiceInterface;
use App\DTOs\MemberMemberships\CreateMemberMembershipData;
use App\Enums\MembershipPlanStatus;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Repositories\MemberMembershipRepository;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberMembershipService extends BaseService implements MemberMembershipServiceInterface
{
    public function __construct(
        private readonly MemberMembershipRepository $memberMembershipRepository
    ) {
        parent::__construct($memberMembershipRepository);
    }

    public function create(
        CreateMemberMembershipData $data
    ): MemberMembership {
        return DB::transaction(function () use ($data) {
            Member::query()->lockForUpdate()->findOrFail($data->memberId);

            return $this->createMembership($data);
        });
    }

    private function createMembership(
        CreateMemberMembershipData $data
    ): MemberMembership {
        $plan = MembershipPlan::query()
            ->lockForUpdate()
            ->findOrFail($data->membershipPlanId);

        if ($plan->status !== MembershipPlanStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'membership_plan_id' => 'The selected membership plan is not active.',
            ]);
        }

        $price = (float) $plan->price;
        $discount = $data->discountAmount;

        if (! is_finite($discount) || $discount < 0 || $discount > $price) {
            throw ValidationException::withMessages([
                'discount_amount' => 'Discount must be between zero and the membership price.',
            ]);
        }

        $discount = round($discount, 2);
        $finalPrice = round($price - $discount, 2);

        $startDate = Carbon::parse(
            $data->startDate
        );

        $endDate = $startDate
            ->copy()
            ->addDays($plan->duration_days - 1);

        if (
            $this->memberMembershipRepository
                ->hasCurrentMembership($data->memberId)
        ) {
            throw ValidationException::withMessages([
                'membership_plan_id' => 'This member already has a current membership.',
            ]);
        }

        return $this->memberMembershipRepository->create([
            'member_id' => $data->memberId,
            'membership_plan_id' => $plan->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),

            'price' => $price,
            'discount_amount' => $discount,
            'final_price' => $finalPrice,
            'discount_reason' => $data->discountReason,

            'status' => MembershipStatus::ACTIVE,
            'notes' => $data->notes,
        ]);
    }

    public function pause(
        MemberMembership $membership
    ): MemberMembership {
        return DB::transaction(function () use ($membership) {
            Member::query()->lockForUpdate()->findOrFail($membership->member_id);
            $membership = MemberMembership::query()
                ->lockForUpdate()
                ->findOrFail($membership->id);

            if ($membership->status !== MembershipStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'membership' => 'Only active memberships can be paused.',
                ]);
            }

            return $this->memberMembershipRepository->updateStatus(
                $membership,
                MembershipStatus::PAUSED
            );
        });
    }

    public function cancel(
        MemberMembership $membership
    ): MemberMembership {
        return DB::transaction(function () use ($membership) {
            Member::query()->lockForUpdate()->findOrFail($membership->member_id);
            $membership = MemberMembership::query()
                ->lockForUpdate()
                ->findOrFail($membership->id);

            if (! in_array($membership->status, [
                MembershipStatus::ACTIVE,
                MembershipStatus::PAUSED,
            ], true)) {
                throw ValidationException::withMessages([
                    'membership' => 'Only active or paused memberships can be cancelled.',
                ]);
            }

            return $this->memberMembershipRepository->updateStatus(
                $membership,
                MembershipStatus::CANCELLED
            );
        });
    }

    public function renew(
        MemberMembership $membership
    ): MemberMembership {
        return DB::transaction(function () use ($membership) {
            Member::query()->lockForUpdate()->findOrFail($membership->member_id);
            $membership->refresh();

            if (! in_array($membership->status, [
                MembershipStatus::EXPIRED,
                MembershipStatus::CANCELLED,
            ], true)) {
                throw ValidationException::withMessages([
                    'membership' => 'Only expired or cancelled memberships can be renewed.',
                ]);
            }

            return $this->createMembership(new CreateMemberMembershipData(
                memberId: $membership->member_id,
                membershipPlanId: $membership->membership_plan_id,
                startDate: now()->toDateString(),
                notes: $membership->notes,
            ));
        });
    }

    public function resume(
        MemberMembership $membership
    ): MemberMembership {
        return DB::transaction(function () use ($membership) {
            Member::query()->lockForUpdate()->findOrFail($membership->member_id);
            $membership->refresh();

            if ($membership->status !== MembershipStatus::PAUSED) {
                throw ValidationException::withMessages([
                    'membership' => 'Only paused memberships can be resumed.',
                ]);
            }

            if ($membership->end_date->isBefore(today())) {
                throw ValidationException::withMessages([
                    'membership' => 'An ended membership cannot be resumed.',
                ]);
            }

            if ($this->memberMembershipRepository->hasCurrentMembership(
                $membership->member_id,
                $membership->id
            )) {
                throw ValidationException::withMessages([
                    'membership' => 'This member already has another current membership.',
                ]);
            }

            return $this->memberMembershipRepository->updateStatus(
                $membership,
                MembershipStatus::ACTIVE
            );
        });
    }
}

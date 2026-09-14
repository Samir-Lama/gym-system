<?php

namespace App\Services\MembershipPlans;

use App\Contracts\Services\MembershipPlanServiceInterface;
use App\DTOs\MembershipPlans\CreateMembershipPlanData;
use App\DTOs\MembershipPlans\UpdateMembershipPlanData;
use App\Models\MembershipPlan;
use App\Repositories\MembershipPlanRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MembershipPlanService extends BaseService implements MembershipPlanServiceInterface
{
    public function __construct(
        private readonly MembershipPlanRepository $membershipPlanRepository
    ) {
        parent::__construct($membershipPlanRepository);
    }

    public function create(
        CreateMembershipPlanData $data
    ): MembershipPlan {
        return $this->membershipPlanRepository->create([
            'name' => $data->name,
            'description' => $data->description,
            'price' => $data->price,
            'duration_days' => $data->durationDays,
            'status' => $data->status,
        ]);
    }

    public function update(
        MembershipPlan $membershipPlan,
        UpdateMembershipPlanData $data
    ): MembershipPlan {
        return $this->membershipPlanRepository->update(
            $membershipPlan,
            [
                'name' => $data->name,
                'description' => $data->description,
                'price' => $data->price,
                'duration_days' => $data->durationDays,
                'status' => $data->status,
            ]
        );
    }

    public function paginate(
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->membershipPlanRepository->paginate($perPage);
    }

    public function deletePlan(MembershipPlan $membershipPlan): void
    {
        if ($membershipPlan->memberships()->exists()) {
            throw ValidationException::withMessages([
                'membershipPlan' => 'This membership plan cannot be deleted because it has membership history.',
            ]);
        }

        $this->membershipPlanRepository->delete($membershipPlan);
    }
}

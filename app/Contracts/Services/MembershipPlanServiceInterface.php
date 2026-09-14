<?php

namespace App\Contracts\Services;

use App\DTOs\MembershipPlans\CreateMembershipPlanData;
use App\DTOs\MembershipPlans\UpdateMembershipPlanData;
use App\Models\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MembershipPlanServiceInterface
{
    public function create(
        CreateMembershipPlanData $data
    ): MembershipPlan;

    public function update(
        MembershipPlan $membershipPlan,
        UpdateMembershipPlanData $data
    ): MembershipPlan;

    public function deletePlan(MembershipPlan $membershipPlan): void;

    public function paginate(
        int $perPage = 10
    ): LengthAwarePaginator;
}

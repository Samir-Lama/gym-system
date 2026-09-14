<?php

namespace App\Contracts\Repositories;

use App\Models\MembershipPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MembershipPlanRepositoryInterface
{
    public function create(array $data): MembershipPlan;

    public function update(
        MembershipPlan $membershipPlan,
        array $data
    ): MembershipPlan;

    public function delete(MembershipPlan $membershipPlan): void;

    public function paginate(
        int $perPage = 10
    ): LengthAwarePaginator;
}

<?php

namespace App\Repositories;

use App\Models\MembershipPlan;

class MembershipPlanRepository extends BaseRepository
{
    public function __construct(MembershipPlan $model)
    {
        parent::__construct($model);
    }
}

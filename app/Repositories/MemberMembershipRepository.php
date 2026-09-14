<?php

namespace App\Repositories;

use App\Enums\MembershipStatus;
use App\Models\MemberMembership;

class MemberMembershipRepository extends BaseRepository
{
    public function __construct(MemberMembership $model)
    {
        parent::__construct($model);
    }

    public function hasCurrentMembership(
        int $memberId,
        ?int $exceptMembershipId = null
    ): bool {
        return $this->model
            ->newQuery()
            ->where('member_id', $memberId)
            ->whereIn('status', [
                MembershipStatus::ACTIVE,
                MembershipStatus::PAUSED,
            ])
            ->when(
                $exceptMembershipId !== null,
                fn ($query) => $query->whereKeyNot($exceptMembershipId)
            )
            ->exists();
    }

    public function expireEndedMemberships(): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('status', [
                MembershipStatus::ACTIVE,
                MembershipStatus::PAUSED,
            ])
            ->whereDate('end_date', '<', now()->toDateString())
            ->update([
                'status' => MembershipStatus::EXPIRED,
            ]);
    }

    public function updateStatus(
        MemberMembership $membership,
        MembershipStatus $status
    ): MemberMembership {
        $membership->update([
            'status' => $status,
        ]);

        return $membership->refresh();
    }
}

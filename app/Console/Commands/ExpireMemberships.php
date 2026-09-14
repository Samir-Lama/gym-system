<?php

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Models\MemberMembership;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('memberships:expire')]
#[Description('Expire memberships whose end date has passed')]
class ExpireMemberships extends Command
{
    public function handle(): int
    {
        $count = MemberMembership::query()
            ->whereIn('status', [
                MembershipStatus::ACTIVE,
                MembershipStatus::PAUSED,
            ])
            ->whereDate('end_date', '<', now()->toDateString())
            ->update([
                'status' => MembershipStatus::EXPIRED,
            ]);

        $this->info("Expired {$count} membership(s).");

        return self::SUCCESS;
    }
}

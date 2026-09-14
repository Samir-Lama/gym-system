<?php

namespace App\Services\CheckIns;

use App\Contracts\Services\CheckInServiceInterface;
use App\Enums\CheckInMethod;
use App\Enums\MembershipStatus;
use App\Enums\MemberStatus;
use App\Models\CheckIn;
use App\Models\Member;
use App\Repositories\CheckInRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInService extends BaseService implements CheckInServiceInterface
{
    public function __construct(
        private readonly CheckInRepository $checkInRepository
    ) {
        parent::__construct($checkInRepository);
    }

    public function checkIn(
        int $memberId,
        CheckInMethod $method = CheckInMethod::MANUAL,
        ?string $deviceId = null,
        ?string $notes = null,
    ): CheckIn {
        return DB::transaction(function () use ($memberId, $method, $deviceId, $notes) {
            $member = Member::query()
                ->lockForUpdate()
                ->findOrFail($memberId);

            if ($member->status !== MemberStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'member_id' => 'Only active members can check in.',
                ]);
            }

            $membership = $member
                ->memberships()
                ->where('status', MembershipStatus::ACTIVE)
                ->whereDate('start_date', '<=', today())
                ->whereDate('end_date', '>=', today())
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages([
                    'member_id' => 'This member does not have a valid active membership.',
                ]);
            }

            if ($this->checkInRepository->hasOpenCheckIn($member->id)) {
                throw ValidationException::withMessages([
                    'member_id' => 'This member is already checked in.',
                ]);
            }

            return $this->checkInRepository->create([
                'member_id' => $member->id,
                'member_membership_id' => $membership->id,
                'method' => $method,
                'check_in_at' => now(),
                'device_id' => $deviceId,
                'notes' => $notes,
            ]);
        });
    }

    public function checkOut(CheckIn $checkIn): CheckIn
    {
        return DB::transaction(function () use ($checkIn) {
            $checkIn = CheckIn::query()
                ->lockForUpdate()
                ->findOrFail($checkIn->id);

            if ($checkIn->check_out_at) {
                throw ValidationException::withMessages([
                    'check_in' => 'This check-in has already been closed.',
                ]);
            }

            $checkIn->update([
                'check_out_at' => now(),
            ]);

            return $checkIn->refresh();
        });
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $presence = null,
        ?string $method = null,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): LengthAwarePaginator {
        return $this->checkInRepository->paginateCheckIns(
            perPage: $perPage,
            search: $search,
            presence: $presence,
            method: $method,
            fromDate: $fromDate,
            toDate: $toDate,
        );
    }
}

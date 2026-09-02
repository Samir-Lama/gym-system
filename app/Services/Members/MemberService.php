<?php

namespace App\Services\Members;

use App\Contracts\Repositories\MemberRepositoryInterface;
use App\Contracts\Services\MemberServiceInterface;
use App\DTOs\Members\CreateMemberData;
use App\DTOs\Members\UpdateMemberData;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Pagination\LengthAwarePaginator;
use Override;

class MemberService implements MemberServiceInterface
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository
    ) {}

    public function create(CreateMemberData $data): Member
    {
        return $this->memberRepository->create([
            'membership_number' => $this->generateMembershipNumber(),
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'gender' => $data->gender,
            'date_of_birth' => $data->dateOfBirth,

            'street' => $data->street,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'postal_code' => $data->postalCode,

            'emergency_contact_name' => $data->emergencyContactName,
            'emergency_contact_phone' => $data->emergencyContactPhone,
            'emergency_relationship' => $data->emergencyRelationship,

            'photo' => $data->photo,
            'waiver_file' => $data->waiverFile,
            'medical_file' => $data->medicalFile,

            'joined_at' => $data->joinedAt,
            'status' => MemberStatus::ACTIVE,
            'notes' => $data->notes,
        ]);
    }

    public function generateMembershipNumber(): string
    {
        do {
            $number = 'GYM-' . strtoupper(
                str()->random(8)
            );
        } while (
            $this->memberRepository->existsByMembershipNumber($number)
        );

        return $number;
    }

    public function update(Member $member, UpdateMemberData $data): Member
    {
        $member->update([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'phone' => $data->phone,
            'gender' => $data->gender,
            'date_of_birth' => $data->dateOfBirth,

            'street' => $data->street,
            'city' => $data->city,
            'state' => $data->state,
            'country' => $data->country,
            'postal_code' => $data->postalCode,

            'emergency_contact_name' => $data->emergencyContactName,
            'emergency_contact_phone' => $data->emergencyContactPhone,
            'emergency_relationship' => $data->emergencyRelationship,

            'notes' => $data->notes,
            'status' => $data->status,
        ]);

        return $member->refresh();
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?MemberStatus $status = null,
        string $sort = 'created_at',
        string $direction = 'desc',
    ): LengthAwarePaginator {
        return $this->memberRepository->paginate(
            $perPage,
            $search,
            $status,
            $sort,
            $direction,
        );
    }

    public function delete(Member $member): void
    {
        $this->memberRepository->delete($member);
    }

    public function bulkUpdateStatus(
        array $ids,
        MemberStatus $status
    ): int {
        return $this->memberRepository->updateStatusByIds($ids, $status);
    }

    public function bulkDelete(array $ids): int {
        return $this->memberRepository->deleteByIds($ids);
    }
}

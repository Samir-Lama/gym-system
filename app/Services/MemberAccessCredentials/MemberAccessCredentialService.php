<?php

namespace App\Services\MemberAccessCredentials;

use App\Contracts\Services\CheckInServiceInterface;
use App\Contracts\Services\MemberAccessCredentialServiceInterface;
use App\DTOs\MemberAccessCredentials\IssuedMemberAccessCredentialData;
use App\Enums\AccessCredentialType;
use App\Enums\CheckInMethod;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberAccessCredential;
use App\Repositories\MemberAccessCredentialRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberAccessCredentialService extends BaseService implements MemberAccessCredentialServiceInterface
{
    public function __construct(
        private readonly MemberAccessCredentialRepository $memberAccessCredentialRepository,
        private readonly CheckInServiceInterface $checkInService,
    ) {
        parent::__construct($memberAccessCredentialRepository);
    }

    public function issue(
        Member $member,
        AccessCredentialType $type,
        ?string $label = null
    ): IssuedMemberAccessCredentialData {
        return DB::transaction(function () use ($member, $type, $label) {
            $member = Member::query()
                ->lockForUpdate()
                ->findOrFail($member->id);

            $this->memberAccessCredentialRepository
                ->deactivateActiveForMemberType($member->id, $type);

            $token = Str::random(64);
            $credential = $this->memberAccessCredentialRepository->create([
                'member_id' => $member->id,
                'type' => $type,
                'credential_hash' => hash('sha256', $token),
                'label' => $label,
                'is_active' => true,
            ]);

            return new IssuedMemberAccessCredentialData(
                credential: $credential,
                token: $token,
            );
        });
    }

    public function resolve(
        string $token,
        ?AccessCredentialType $expectedType = null
    ): MemberAccessCredential {
        $credential = $token === ''
            ? null
            : $this->memberAccessCredentialRepository->findActiveByHash(
                hash('sha256', $token),
                $expectedType
            );

        if (! $credential) {
            throw ValidationException::withMessages([
                'credential' => 'The access credential is invalid or inactive.',
            ]);
        }

        return $credential;
    }

    public function checkInWithCredential(
        string $token,
        AccessCredentialType $type
    ): CheckIn {
        $credential = $this->resolve($token, $type);

        return DB::transaction(function () use ($token, $type, $credential) {
            Member::query()
                ->lockForUpdate()
                ->findOrFail($credential->member_id);

            $credential = $this->memberAccessCredentialRepository->findActiveByHash(
                hash('sha256', $token),
                $type,
                lockForUpdate: true,
            );

            if (! $credential) {
                throw ValidationException::withMessages([
                    'credential' => 'The access credential is invalid or inactive.',
                ]);
            }

            $method = match ($type) {
                AccessCredentialType::QR => CheckInMethod::QR,
                AccessCredentialType::RFID => CheckInMethod::RFID,
            };

            $checkIn = $this->checkInService->checkIn(
                memberId: $credential->member_id,
                method: $method,
            );

            $this->memberAccessCredentialRepository->update($credential, [
                'last_used_at' => now(),
            ]);

            return $checkIn;
        });
    }

    public function markUsed(MemberAccessCredential $credential): MemberAccessCredential
    {
        return DB::transaction(function () use ($credential) {
            $credential = MemberAccessCredential::query()
                ->lockForUpdate()
                ->findOrFail($credential->id);

            if (! $credential->is_active) {
                throw ValidationException::withMessages([
                    'credential' => 'An inactive credential cannot be marked as used.',
                ]);
            }

            return $this->memberAccessCredentialRepository->update($credential, [
                'last_used_at' => now(),
            ]);
        });
    }

    public function revoke(MemberAccessCredential $credential): MemberAccessCredential
    {
        return DB::transaction(function () use ($credential) {
            $credential = MemberAccessCredential::query()
                ->lockForUpdate()
                ->findOrFail($credential->id);

            return $this->memberAccessCredentialRepository->update($credential, [
                'is_active' => false,
            ]);
        });
    }
}

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
use Illuminate\Database\UniqueConstraintViolationException;
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
                'credential_hash' => hash('sha256', $this->normalizeCredential($token)),
                'label' => $label,
                'is_active' => true,
            ]);

            return new IssuedMemberAccessCredentialData(
                credential: $credential,
                token: $token,
            );
        });
    }

    public function issueRfidCredential(
        Member $member,
        string $cardUid
    ): MemberAccessCredential {
        $normalizedUid = $this->normalizeCredential($cardUid);

        if ($normalizedUid === '') {
            throw ValidationException::withMessages([
                'credential' => 'Enter a valid RFID card UID.',
            ]);
        }

        $credentialHash = hash('sha256', $normalizedUid);

        try {
            return DB::transaction(function () use ($member, $credentialHash) {
                $member = Member::query()
                    ->lockForUpdate()
                    ->findOrFail($member->id);

                if ($this->memberAccessCredentialRepository->findByHash($credentialHash, true)) {
                    throw ValidationException::withMessages([
                        'credential' => 'This RFID card is already registered.',
                    ]);
                }

                $this->memberAccessCredentialRepository->deactivateActiveForMemberType(
                    $member->id,
                    AccessCredentialType::RFID
                );

                return $this->memberAccessCredentialRepository->create([
                    'member_id' => $member->id,
                    'type' => AccessCredentialType::RFID,
                    'credential_hash' => $credentialHash,
                    'label' => 'RFID Card',
                    'is_active' => true,
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'credential' => 'This RFID card is already registered.',
            ]);
        }
    }

    public function findByToken(
        string $token,
        AccessCredentialType $type
    ): ?MemberAccessCredential {
        return $this->findActiveCredential($token, $type);
    }

    public function resolve(
        string $token,
        ?AccessCredentialType $expectedType = null
    ): MemberAccessCredential {
        $credential = $expectedType
            ? $this->findByToken($token, $expectedType)
            : $this->findByAnyToken($token);

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

            $credential = $this->findActiveCredential($token, $type, true);

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

    private function findByAnyToken(string $token): ?MemberAccessCredential
    {
        $normalizedToken = $this->normalizeCredential($token);

        if ($normalizedToken === '') {
            return null;
        }

        $credential = $this->memberAccessCredentialRepository->findActiveByHash(
            hash('sha256', $normalizedToken)
        );

        if ($credential || $normalizedToken === trim($token)) {
            return $credential;
        }

        return $this->memberAccessCredentialRepository->findActiveByHash(
            hash('sha256', trim($token))
        );
    }

    private function findActiveCredential(
        string $token,
        AccessCredentialType $type,
        bool $lockForUpdate = false,
    ): ?MemberAccessCredential {
        $normalizedToken = $this->normalizeCredential($token);

        if ($normalizedToken === '') {
            return null;
        }

        $credential = $this->memberAccessCredentialRepository->findActiveByHash(
            hash('sha256', $normalizedToken),
            $type,
            $lockForUpdate,
        );

        if (
            $credential
            || $type !== AccessCredentialType::QR
            || $normalizedToken === trim($token)
        ) {
            return $credential;
        }

        return $this->memberAccessCredentialRepository->findActiveByHash(
            hash('sha256', trim($token)),
            $type,
            $lockForUpdate,
        );
    }

    private function normalizeCredential(string $credential): string
    {
        return strtoupper((string) preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            trim($credential)
        ));
    }
}

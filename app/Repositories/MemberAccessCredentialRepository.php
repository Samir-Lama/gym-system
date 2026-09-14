<?php

namespace App\Repositories;

use App\Enums\AccessCredentialType;
use App\Models\MemberAccessCredential;

class MemberAccessCredentialRepository extends BaseRepository
{
    public function __construct(MemberAccessCredential $model)
    {
        parent::__construct($model);
    }

    public function deactivateActiveForMemberType(
        int $memberId,
        AccessCredentialType $type
    ): int {
        return $this->model
            ->newQuery()
            ->where('member_id', $memberId)
            ->where('type', $type)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public function findActiveByHash(
        string $credentialHash,
        ?AccessCredentialType $type = null,
        bool $lockForUpdate = false,
    ): ?MemberAccessCredential {
        return $this->model
            ->newQuery()
            ->where('credential_hash', $credentialHash)
            ->where('is_active', true)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();
    }
}

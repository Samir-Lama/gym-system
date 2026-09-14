<?php

namespace App\Contracts\Services;

use App\DTOs\MemberAccessCredentials\IssuedMemberAccessCredentialData;
use App\Enums\AccessCredentialType;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberAccessCredential;

interface MemberAccessCredentialServiceInterface
{
    public function issue(
        Member $member,
        AccessCredentialType $type,
        ?string $label = null
    ): IssuedMemberAccessCredentialData;

    public function issueRfidCredential(
        Member $member,
        string $cardUid
    ): MemberAccessCredential;

    public function findByToken(
        string $token,
        AccessCredentialType $type
    ): ?MemberAccessCredential;

    public function resolve(
        string $token,
        ?AccessCredentialType $expectedType = null
    ): MemberAccessCredential;

    public function checkInWithCredential(
        string $token,
        AccessCredentialType $type
    ): CheckIn;

    public function markUsed(MemberAccessCredential $credential): MemberAccessCredential;

    public function revoke(MemberAccessCredential $credential): MemberAccessCredential;
}

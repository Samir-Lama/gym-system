<?php

namespace App\DTOs\MemberAccessCredentials;

use App\Models\MemberAccessCredential;

readonly class IssuedMemberAccessCredentialData
{
    public function __construct(
        public MemberAccessCredential $credential,
        public string $token,
    ) {}
}

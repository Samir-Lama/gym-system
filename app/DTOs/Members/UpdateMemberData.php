<?php

namespace App\DTOs\Members;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Http\Requests\Members\UpdateMemberRequest;

readonly class UpdateMemberData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,

        public ?string $phone,
        public ?Gender $gender,
        public ?string $dateOfBirth,

        public ?string $street,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $postalCode,

        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
        public ?string $emergencyRelationship,

        public MemberStatus $status,
        public ?string $notes,
    ) {}

    public static function formRequest(
        UpdateMemberRequest $request
    ): self {
        return new self(
            firstName: $request->string('first_name')->value(),
            lastName: $request->string('last_name')->value(),
            email: $request->string('email')->value(),

            phone: $request->input('phone'),

            gender: $request->filled('gender')
                ? Gender::from($request->string('gender')->value())
                : null,

            dateOfBirth: $request->input('date_of_birth'),

            street: $request->input('street'),
            city: $request->input('city'),
            state: $request->input('state'),
            country: $request->input('country'),
            postalCode: $request->input('postal_code'),

            emergencyContactName: $request->input(
                'emergency_contact_name'
            ),
            emergencyContactPhone: $request->input(
                'emergency_contact_phone'
            ),
            emergencyRelationship: $request->input(
                'emergency_relationship'
            ),

            status: MemberStatus::from(
                $request->string('status')->value()
            ),

            notes: $request->input('notes'),
        );
    }
}

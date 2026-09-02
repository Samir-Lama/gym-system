<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'membership_number' => 'GYM-' . strtoupper(
                $this->faker->unique()->bothify('########')
            ),

            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),

            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),

            'gender' => $this->faker->randomElement(
                Gender::cases()
            ),

            'date_of_birth' => $this->faker->date(
                'Y-m-d',
                '-18 years'
            ),

            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),

            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_relationship' => $this->faker->randomElement([
                'Parent',
                'Sibling',
                'Spouse',
                'Friend',
                'Partner',
            ]),

            'photo' => null,
            'waiver_file' => null,
            'medical_file' => null,

            'joined_at' => $this->faker->dateTimeBetween(
                '-2 years',
                'now'
            )->format('Y-m-d'),

            'status' => $this->faker->randomElement(
                MemberStatus::cases()
            ),

            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}

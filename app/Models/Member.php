<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'membership_number',

        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',

        'street',
        'city',
        'state',
        'country',
        'postal_code',

        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_relationship',

        'photo',
        'waiver_file',
        'medical_file',

        'joined_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joined_at' => 'date',

        'gender' => Gender::class,
        'status' => MemberStatus::class,
    ];
}

<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MemberStatus;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
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

    public function memberships(): HasMany
    {
        return $this->hasMany(MemberMembership::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function accessCredentials(): HasMany
    {
        return $this->hasMany(MemberAccessCredential::class);
    }
}

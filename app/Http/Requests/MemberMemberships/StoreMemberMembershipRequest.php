<?php

namespace App\Http\Requests\MemberMemberships;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'required',
                'integer',
                'exists:members,id',
            ],

            'membership_plan_id' => [
                'required',
                'integer',
                'exists:membership_plans,id',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'discount_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'discount_reason' => ['nullable', 'string', 'max:255'],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}

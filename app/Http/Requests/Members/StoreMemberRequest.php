<?php

namespace App\Http\Requests\Members;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($this->member),
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'nullable',
                Rule::enum(Gender::class),
            ],

            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],

            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'emergency_contact_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'emergency_relationship' => [
                'nullable',
                'string',
                'max:50',
            ],

            'photo' => [
                'nullable',
                'image',
                'max:5120',
            ],

            'waiver_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'medical_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

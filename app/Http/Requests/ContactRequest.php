<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:255'],
            'company'     => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'address'     => ['nullable', 'string', 'max:500'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
            'pincode'     => ['nullable', 'string', 'max:10'],
            'gst_number'  => ['nullable', 'string', 'max:20'],
            'notes'       => ['nullable', 'string', 'max:5000'],
            'lead_id'     => ['nullable', 'exists:leads,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Contact name is required.',
            'phone.required' => 'Phone number is required.',
            'email.email'    => 'Please enter a valid email.',
        ];
    }
}
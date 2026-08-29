<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'company'    => ['nullable', 'string', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'email'      => ['nullable', 'email', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:20'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'bank_details'       => ['nullable', 'string', 'max:2000'],
            'address'    => ['nullable', 'string', 'max:500'],
            'city'       => ['nullable', 'string', 'max:100'],
            'state'      => ['nullable', 'string', 'max:100'],
            'pincode'    => ['nullable', 'string', 'max:12'],
            'notes'      => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vendor name is required.',
        ];
    }
}

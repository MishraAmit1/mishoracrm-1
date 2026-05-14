<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['required', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:255'],
            'company'              => ['nullable', 'string', 'max:255'],
            'designation'          => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'source'               => ['nullable', 'in:facebook,instagram,google,website,whatsapp,referral,cold_call,email,walk_in,other'],
            'status'               => ['nullable', 'in:new,contacted,qualified,proposal,negotiation,converted,lost'],
            'priority'             => ['nullable', 'in:low,medium,high'],
            'lead_value'           => ['nullable', 'numeric', 'min:0'],
            'assigned_to'          => ['nullable', 'exists:users,id'],
            'notes'                => ['nullable', 'string', 'max:5000'],
            'lost_reason'          => ['nullable', 'string', 'max:500'],
            'expected_close_date'  => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Lead name is required.',
            'phone.required'         => 'Phone number is required.',
            'email.email'            => 'Please enter a valid email address.',
            'source.in'              => 'Invalid lead source selected.',
            'status.in'              => 'Invalid status selected.',
            'priority.in'            => 'Priority must be low, medium or high.',
            'lead_value.numeric'     => 'Lead value must be a number.',
            'assigned_to.exists'     => 'Selected staff member does not exist.',
            'expected_close_date.after' => 'Expected close date must be a future date.',
        ];
    }

    // Auto-set created_by on store
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST')) {
            $this->merge([
                'created_by' => Auth::id(),
                'status'     => $this->status ?? 'new',
                'priority'   => $this->priority ?? 'medium',
                'source'     => $this->source ?? 'other',
            ]);
        }
    }
}
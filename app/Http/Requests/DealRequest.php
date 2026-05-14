<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'value'               => ['required', 'numeric', 'min:0'],
            'stage'               => ['required', 'in:new,proposal,negotiation,won,lost'],
            'probability'         => ['nullable', 'integer', 'min:0', 'max:100'],
            'contact_id'          => ['nullable', 'exists:contacts,id'],
            'lead_id'             => ['nullable', 'exists:leads,id'],
            'assigned_to'         => ['nullable', 'exists:users,id'],
            'expected_close_date' => ['nullable', 'date'],
            'actual_close_date'   => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'lost_reason'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Deal title is required.',
            'value.required' => 'Deal value is required.',
            'stage.required' => 'Deal stage is required.',
            'stage.in'       => 'Invalid stage selected.',
        ];
    }
}
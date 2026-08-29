<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'  => ['required', 'integer', 'exists:products,id'],
            'quantity'    => ['required', 'numeric', 'min:0.01'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes'       => ['nullable', 'string', 'max:2000'],
            'stages'                 => ['nullable', 'array'],
            'stages.*.name'          => ['nullable', 'string', 'max:120'],
            'stages.*.assigned_to'   => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Select the finished good to build.',
            'quantity.required'   => 'Quantity is required.',
        ];
    }
}

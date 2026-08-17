<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id'        => ['nullable', 'exists:departments,id'],
            'date'                 => ['required', 'date'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'         => ['required', 'string', 'max:255'],
            'items.*.description'  => ['nullable', 'string', 'max:500'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.reason'       => ['nullable', 'string', 'max:500'],
            'reason'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'             => 'At least one item is required.',
            'items.*.name.required'      => 'Item name is required.',
            'items.*.quantity.required'  => 'Quantity is required.',
        ];
    }
}

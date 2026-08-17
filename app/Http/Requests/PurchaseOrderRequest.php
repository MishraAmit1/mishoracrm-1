<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'                => ['nullable', 'exists:vendors,id'],
            'purchase_request_id'      => ['nullable', 'exists:purchase_requests,id'],
            'date'                     => ['required', 'date'],
            'expected_delivery_date'   => ['nullable', 'date', 'after_or_equal:date'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.product_id'       => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'             => ['required', 'string', 'max:255'],
            'items.*.description'      => ['nullable', 'string', 'max:500'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'             => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.amount'           => ['required', 'numeric', 'min:0'],
            'discount'                 => ['nullable', 'numeric', 'min:0'],
            'tax_percent'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
            'terms'                    => ['nullable', 'string', 'max:2000'],
            'status'                   => ['nullable', 'in:draft,sent,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'          => 'At least one item is required.',
            'items.*.name.required'   => 'Item name is required.',
            'items.*.quantity.required' => 'Quantity is required.',
            'items.*.rate.required'   => 'Rate is required.',
        ];
    }
}

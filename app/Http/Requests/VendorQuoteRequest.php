<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id'             => ['required', 'integer', 'exists:vendors,id'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name'          => ['required', 'string', 'max:255'],
            'items.*.description'   => ['nullable', 'string', 'max:500'],
            'items.*.quantity'      => ['required', 'numeric', 'min:0.01'],
            'items.*.rate'          => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent'   => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}

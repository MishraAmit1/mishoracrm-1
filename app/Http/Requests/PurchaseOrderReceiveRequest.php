<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_date'                => ['nullable', 'date'],
            'note'                         => ['nullable', 'string', 'max:1000'],
            'items'                        => ['required', 'array', 'min:1'],
            // Quantity that arrived in THIS delivery (incremental, not cumulative).
            'items.*.received_quantity'    => ['required', 'numeric', 'min:0'],
            // How much of it passed inspection — defaults to received when blank.
            'items.*.accepted_quantity'    => ['nullable', 'numeric', 'min:0'],
            'items.*.rejection_reason'     => ['nullable', 'string', 'max:500'],
            'items.*.batch_number'         => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date'          => ['nullable', 'date'],
        ];
    }
}

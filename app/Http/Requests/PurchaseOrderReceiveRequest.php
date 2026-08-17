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
            'items'                        => ['required', 'array', 'min:1'],
            'items.*.received_quantity'    => ['required', 'numeric', 'min:0'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    /**
     * ─────────────────────────────────────────────────────────
     * Authorize
     * ─────────────────────────────────────────────────────────
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * ─────────────────────────────────────────────────────────
     * Prepare Data
     * ─────────────────────────────────────────────────────────
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->items ?? [])
            ->filter(fn ($item) =>
                !empty($item['description']) ||
                !empty($item['qty']) ||
                !empty($item['rate'])
            )
            ->values()
            ->toArray();

        $this->merge([
            'items' => $items,
        ]);
    }

    /**
     * ─────────────────────────────────────────────────────────
     * Rules
     * ─────────────────────────────────────────────────────────
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')
                    ->where('tenant_id', Auth::id()),
            ],

            'quotation_id' => [
                'nullable',
                'integer',
                Rule::exists('quotations', 'id')
                    ->where('tenant_id', Auth::id()),
            ],

            'date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:date',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'sent',
                    'partial',
                    'paid',
                    'overdue',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'terms' => [
                'nullable',
                'string',
                'max:10000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.description' => [
                'required',
                'string',
                'max:1000',
            ],

            'items.*.hsn' => [
                'nullable',
                'string',
                'max:50',
            ],

            'items.*.qty' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.unit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'items.*.rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.tax' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ];
    }

    /**
     * ─────────────────────────────────────────────────────────
     * Attributes
     * ─────────────────────────────────────────────────────────
     */
    public function attributes(): array
    {
        return [

            'contact_id'            => 'customer',
            'quotation_id'          => 'quotation',
            'date'                  => 'invoice date',
            'due_date'              => 'due date',
            'paid_amount'           => 'paid amount',

            'items'                 => 'invoice items',

            'items.*.description'   => 'item description',
            'items.*.qty'           => 'quantity',
            'items.*.rate'          => 'rate',
            'items.*.tax'           => 'tax',
            'items.*.hsn'           => 'HSN/SAC',
        ];
    }

    /**
     * ─────────────────────────────────────────────────────────
     * Messages
     * ─────────────────────────────────────────────────────────
     */
    public function messages(): array
    {
        return [

            'items.required' => 'At least one invoice item is required.',
            'items.min'      => 'Please add at least one invoice item.',

            'items.*.description.required' =>
                'Item description is required.',

            'items.*.qty.required' =>
                'Quantity is required.',

            'items.*.qty.min' =>
                'Quantity must be greater than 0.',

            'items.*.rate.required' =>
                'Rate is required.',

            'due_date.after_or_equal' =>
                'Due date must be after invoice date.',
        ];
    }

    /**
     * ─────────────────────────────────────────────────────────
     * Extra Validation
     * ─────────────────────────────────────────────────────────
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $items = $this->items ?? [];

            $subtotal = 0;
            $taxTotal = 0;

            foreach ($items as $item) {

                $qty  = (float) ($item['qty'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);
                $tax  = (float) ($item['tax'] ?? 0);

                $amount = $qty * $rate;

                $subtotal += $amount;
                $taxTotal += ($amount * $tax) / 100;
            }

            $grandTotal = $subtotal + $taxTotal - ((float) $this->discount);

            if ($grandTotal < 0) {
                $validator->errors()->add(
                    'discount',
                    'Discount cannot exceed invoice total.'
                );
            }

            if (
                $this->filled('paid_amount') &&
                $this->paid_amount > $grandTotal
            ) {
                $validator->errors()->add(
                    'paid_amount',
                    'Paid amount cannot exceed invoice total.'
                );
            }
        });
    }
}
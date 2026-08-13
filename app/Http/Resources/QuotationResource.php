<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'number'          => $this->number,
            'date'            => $this->date?->toDateString(),
            'valid_until'     => $this->valid_until?->toDateString(),
            'is_expired'      => $this->isExpired(),
            'items'           => $this->items,
            'subtotal'        => $this->subtotal,
            'discount'        => $this->discount,
            'tax_percent'     => $this->tax_percent,
            'tax_amount'      => $this->tax_amount,
            'total'           => $this->total,
            'currency'        => $this->currency,
            'formatted_total' => $this->formatted_total,
            'notes'           => $this->notes,
            'terms'           => $this->terms,
            'status'          => $this->status,
            'status_label'    => \App\Models\Quotation::statuses()[$this->status] ?? ucfirst($this->status),
            'version'         => $this->version,
            'parent_quotation_id' => $this->parent_quotation_id,
            'created_at'      => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),

            'contact' => $this->whenLoaded('contact', fn() => [
                'id'      => $this->contact->id,
                'name'    => $this->contact->name,
                'phone'   => $this->contact->phone,
                'company' => $this->contact->company,
            ]),

            'lead' => $this->whenLoaded('lead', fn() => [
                'id'   => $this->lead->id,
                'name' => $this->lead->name,
            ]),

            'deal' => $this->whenLoaded('deal', fn() => [
                'id'    => $this->deal->id,
                'title' => $this->deal->title,
            ]),

            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),

            'invoice' => $this->whenLoaded('invoice', fn() => $this->invoice ? [
                'id'     => $this->invoice->id,
                'number' => $this->invoice->number,
            ] : null),
        ];
    }
}

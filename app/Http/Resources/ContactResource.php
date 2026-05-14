<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'company'     => $this->company,
            'designation' => $this->designation,
            'address'     => $this->address,
            'city'        => $this->city,
            'state'       => $this->state,
            'pincode'     => $this->pincode,
            'gst_number'  => $this->gst_number,
            'notes'       => $this->notes,
            'full_address'=> $this->full_address,
            'created_at'  => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),

            'lead' => $this->whenLoaded('lead', fn() => [
                'id'   => $this->lead->id,
                'name' => $this->lead->name,
            ]),

            'deals_count'     => $this->whenCounted('deals'),
            'followups_count' => $this->whenCounted('followups'),
            'invoices_count'  => $this->whenCounted('invoices'),
        ];
    }
}
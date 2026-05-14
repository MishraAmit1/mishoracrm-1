<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'value'               => $this->value,
            'formatted_value'     => $this->formatted_value,
            'stage'               => $this->stage,
            'stage_label'         => \App\Models\Deal::stages()[$this->stage] ?? ucfirst($this->stage),
            'stage_color'         => $this->stage_color,
            'probability'         => $this->probability,
            'notes'               => $this->notes,
            'lost_reason'         => $this->lost_reason,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'actual_close_date'   => $this->actual_close_date?->toDateString(),
            'created_at'          => $this->created_at->toDateTimeString(),
            'created_at_human'    => $this->created_at->diffForHumans(),

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

            'assigned_to' => $this->whenLoaded('assignedTo', fn() => [
                'id'   => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),

            'tasks_count'     => $this->whenCounted('tasks'),
            'followups_count' => $this->whenCounted('followups'),
        ];
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'phone'               => $this->phone,
            'email'               => $this->email,
            'company'             => $this->company,
            'designation'         => $this->designation,
            'city'                => $this->city,
            'state'               => $this->state,
            'source'              => $this->source,
            'source_label'        => \App\Models\Lead::sources()[$this->source] ?? ucfirst($this->source),
            'status'              => $this->status,
            'status_label'        => \App\Models\Lead::statuses()[$this->status] ?? ucfirst($this->status),
            'status_color'        => $this->status_color,
            'priority'            => $this->priority,
            'priority_color'      => $this->priority_color,
            'lead_value'          => $this->lead_value,
            'formatted_value'     => $this->formatted_value,
            'notes'               => $this->notes,
            'lost_reason'         => $this->lost_reason,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'contacted_at'        => $this->contacted_at?->toDateTimeString(),
            'converted_at'        => $this->converted_at?->toDateTimeString(),
            'created_at'          => $this->created_at->toDateTimeString(),
            'updated_at'          => $this->updated_at->toDateTimeString(),
            'created_at_human'    => $this->created_at->diffForHumans(),

            // Relationships (loaded when needed)
            'assigned_to' => $this->whenLoaded('assignedTo', fn() => [
                'id'     => $this->assignedTo->id,
                'name'   => $this->assignedTo->name,
                'avatar' => $this->assignedTo->avatar_url,
            ]),

            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),

            'followups_count' => $this->whenCounted('followups'),
            'tasks_count'     => $this->whenCounted('tasks'),

            'latest_followup' => $this->whenLoaded(
                'followups',
                fn() =>
                $this->followups->first() ? [
                    'type'         => $this->followups->first()->type,
                    'scheduled_at' => $this->followups->first()->scheduled_at->toDateTimeString(),
                    'status'       => $this->followups->first()->status,
                ] : null
            ),
        ];
    }
}

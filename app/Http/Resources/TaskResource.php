<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'status'           => $this->status,
            'priority'         => $this->priority,
            'tags'             => $this->tags,
            'due_at'           => $this->due_at?->toDateString(),
            'completed_at'     => $this->completed_at?->toDateString(),
            'is_overdue'       => $this->isOverdue(),
            'estimated_hours'  => $this->estimated_hours,
            'actual_hours'     => $this->actual_hours,
            'is_recurring'     => $this->isRecurring(),
            'recurrence_type'  => $this->recurrence_type,
            'taskable_type'    => $this->taskable_type ? class_basename($this->taskable_type) : null,
            'taskable_id'      => $this->taskable_id,
            'created_at'       => $this->created_at->toDateTimeString(),
            'updated_at'       => $this->updated_at->toDateTimeString(),

            'assigned_to' => $this->whenLoaded('assignedTo', fn() => $this->assignedTo ? [
                'id'   => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ] : null),

            'created_by' => $this->whenLoaded('creator', fn() => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),

            'checklist_progress' => $this->whenLoaded('checklistItems', fn() => $this->checklist_progress),

            'checklist_items_count' => $this->whenCounted('checklistItems'),
            'comments_count'        => $this->whenCounted('comments'),
            'attachments_count'     => $this->whenCounted('attachments'),
        ];
    }
}

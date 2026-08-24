<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadCallLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// Merges Followups + (Lead-only) call/note logs + Email/WhatsApp send logs
// into one chronological feed for the Lead/Contact show pages. Each entry:
// ['icon_type','title','badge'=>['label','bg','color']|null,'description','meta','time','url']
class ActivityTimelineService
{
    public static function forLead(Lead $lead): Collection
    {
        return static::followupEntries($lead->followups)
            ->concat(static::taskEntries($lead->tasks))
            ->concat(static::callLogEntries($lead->callLogs))
            ->concat(static::emailEntries($lead->emailLogs))
            ->concat(static::whatsappEntries($lead->whatsappLogs))
            ->sortByDesc('time')
            ->values();
    }

    public static function forContact(Contact $contact): Collection
    {
        return static::followupEntries($contact->followups)
            ->concat(static::taskEntries($contact->tasks))
            ->concat(static::emailEntries($contact->emailLogs))
            ->concat(static::whatsappEntries($contact->whatsappLogs))
            ->sortByDesc('time')
            ->values();
    }

    private static function taskEntries(Collection $tasks): Collection
    {
        $statusMap = [
            'pending'     => ['bg' => 'var(--accent-dim)', 'color' => 'var(--accent)', 'label' => 'Pending'],
            'in_progress' => ['bg' => 'var(--amber-dim)', 'color' => 'var(--amber)', 'label' => 'In Progress'],
            'completed'   => ['bg' => 'var(--green-dim)', 'color' => 'var(--green)', 'label' => 'Completed'],
            'cancelled'   => ['bg' => 'var(--red-dim)', 'color' => 'var(--red)', 'label' => 'Cancelled'],
        ];

        return $tasks->map(fn($task) => [
            'icon_type'   => 'task',
            'title'       => $task->title,
            'badge'       => $statusMap[$task->status] ?? ['bg' => 'var(--bg-hover)', 'color' => 'var(--text-300)', 'label' => ucfirst($task->status)],
            'description' => $task->description,
            'meta'        => ucfirst($task->priority) . ' priority',
            'time'        => $task->due_at ?? $task->created_at,
            'footer'      => $task->assignedTo ? 'assigned to ' . $task->assignedTo->name : null,
            'url'         => route('tenant.tasks.show', $task->id),
        ]);
    }

    private static function followupEntries(Collection $followups): Collection
    {
        $statusMap = [
            'scheduled'   => ['bg' => 'var(--accent-dim)', 'color' => 'var(--accent)', 'label' => 'Scheduled'],
            'done'        => ['bg' => 'var(--green-dim)', 'color' => 'var(--green)', 'label' => 'Done'],
            'missed'      => ['bg' => 'var(--red-dim)', 'color' => 'var(--red)', 'label' => 'Missed'],
            'rescheduled' => ['bg' => 'var(--amber-dim)', 'color' => 'var(--amber)', 'label' => 'Rescheduled'],
        ];

        return $followups->map(function ($fu) use ($statusMap) {
            $badge = $statusMap[$fu->status] ?? ['bg' => 'var(--bg-hover)', 'color' => 'var(--text-300)', 'label' => ucfirst($fu->status)];

            return [
                'icon_type'   => $fu->type === 'other' ? 'note' : $fu->type,
                'title'       => \App\Models\Followup::types()[$fu->type] ?? ucfirst($fu->type),
                'badge'       => $badge,
                'description' => $fu->status === 'done' && $fu->outcome
                    ? $fu->outcome
                    : ($fu->notes ?: null),
                'meta'        => null,
                'time'        => $fu->scheduled_at,
                'footer'      => null,
                'url'         => route('tenant.followups.show', $fu),
            ];
        });
    }

    private static function callLogEntries(Collection $logs): Collection
    {
        $outcomeLabels = LeadCallLog::outcomes();

        return $logs->map(fn($log) => [
            'icon_type'   => $log->type,
            'title'       => LeadCallLog::types()[$log->type] ?? ucfirst($log->type),
            'badge'       => ($log->type === 'call' && $log->call_outcome)
                ? ['bg' => 'var(--bg-hover)', 'color' => 'var(--text-300)', 'label' => $outcomeLabels[$log->call_outcome] ?? $log->call_outcome]
                : null,
            'description' => $log->description,
            'meta'        => ($log->type === 'call' && $log->call_duration) ? $log->call_duration . 'm' : null,
            'time'        => $log->created_at,
            'footer'      => $log->createdBy ? 'by ' . $log->createdBy->name : null,
            'url'         => null,
        ]);
    }

    private static function emailEntries(Collection $logs): Collection
    {
        return $logs->map(fn($log) => [
            'icon_type'   => 'email',
            'title'       => 'Email Sent',
            'badge'       => $log->status === 'failed' ? ['bg' => 'var(--red-dim)', 'color' => 'var(--red)', 'label' => 'Failed'] : null,
            'description' => $log->subject ? Str::limit($log->subject, 140) : 'To: ' . $log->to_email,
            'meta'        => null,
            'time'        => $log->sent_at ?? $log->created_at,
            'footer'      => $log->sentBy ? 'by ' . $log->sentBy->name : null,
            'url'         => null,
        ]);
    }

    private static function whatsappEntries(Collection $logs): Collection
    {
        return $logs->map(fn($log) => [
            'icon_type'   => 'whatsapp',
            'title'       => 'WhatsApp Message Sent',
            'badge'       => null,
            'description' => Str::limit($log->message, 140),
            'meta'        => null,
            'time'        => $log->sent_at ?? $log->created_at,
            'footer'      => $log->sentBy ? 'by ' . $log->sentBy->name : null,
            'url'         => null,
        ]);
    }
}

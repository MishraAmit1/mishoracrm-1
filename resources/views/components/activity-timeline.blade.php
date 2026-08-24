{{--
    Unified Activity Timeline — Lead & Contact show pages
    ========================================================
    Usage:
        @include('components.activity-timeline', ['entries' => $timeline])

    $entries: Collection from App\Services\ActivityTimelineService, each item:
        ['icon_type','title','badge'=>['label','bg','color']|null,'description','meta','time','footer','url']
--}}

@once
@push('styles')
<style>
.at-timeline-wrap { padding: 18px 22px; }
.at-tl-item { display: flex; gap: 12px; padding-bottom: 18px; position: relative; }
.at-tl-item:last-child { padding-bottom: 0; }
.at-tl-item:not(:last-child)::after {
    content: ''; position: absolute; left: 15px; top: 32px; bottom: 0;
    width: 1px; background: var(--border-subtle);
}
.at-tl-icon { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; z-index: 1; }
.at-tl-body { flex: 1; padding-top: 4px; }
.at-tl-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; flex-wrap: wrap; }
.at-tl-action { font-size: 13px; font-weight: 600; color: var(--text-100); }
.at-tl-time { font-size: 11.5px; color: var(--text-300); font-family: 'DM Mono', monospace; }
.at-tl-desc { font-size: 12.5px; color: var(--text-300); line-height: 1.5; }
.at-tl-badge { padding: 2px 8px; border-radius: 20px; font-size: 10.5px; font-weight: 600; }
.at-tl-empty { padding: 26px 22px; text-align: center; color: var(--text-300); font-size: 13px; }
</style>
@endpush
@endonce

@php
$atIcons = [
    'call'     => ['bg' => 'var(--green-dim)', 'stroke' => 'var(--green)', 'path' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
    'email'    => ['bg' => 'var(--amber-dim)', 'stroke' => 'var(--amber)', 'path' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    'whatsapp' => ['bg' => 'var(--green-dim)', 'stroke' => 'var(--green)', 'path' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
    'meeting'  => ['bg' => 'var(--purple-dim)', 'stroke' => 'var(--purple)', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    'note'     => ['bg' => 'var(--accent-dim)', 'stroke' => 'var(--accent)', 'path' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
    'task'     => ['bg' => 'var(--purple-dim)', 'stroke' => 'var(--purple)', 'path' => 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11'],
];
@endphp

<div class="at-timeline-wrap">
    @forelse($entries as $entry)
    @php $ic = $atIcons[$entry['icon_type']] ?? $atIcons['note']; @endphp
    <div class="at-tl-item">
        <div class="at-tl-icon" style="background:{{ $ic['bg'] }}">
            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic['path'] }}"/></svg>
        </div>
        <div class="at-tl-body">
            <div class="at-tl-meta">
                <span class="at-tl-action">{{ $entry['title'] }}</span>
                @if($entry['badge'])
                <span class="at-tl-badge" style="background:{{ $entry['badge']['bg'] }};color:{{ $entry['badge']['color'] }}">{{ $entry['badge']['label'] }}</span>
                @endif
                @if($entry['meta'])
                <span style="font-size:11px;color:var(--text-300)">{{ $entry['meta'] }}</span>
                @endif
                <span class="at-tl-time">{{ $entry['time']?->format('M d, Y · g:i A') }}</span>
            </div>
            @if($entry['description'])
            <div class="at-tl-desc">{{ $entry['description'] }}</div>
            @else
            <div class="at-tl-desc" style="color:var(--text-400);font-style:italic">No notes added</div>
            @endif
            @if($entry['footer'])
            <div style="font-size:11px;color:var(--text-400);margin-top:3px">{{ $entry['footer'] }}</div>
            @endif
            @if($entry['url'])
            <a href="{{ $entry['url'] }}" style="font-size:11px;color:var(--accent);text-decoration:none;margin-top:3px;display:inline-block">View details &rarr;</a>
            @endif
        </div>
    </div>
    @empty
    <div class="at-tl-empty">No activity yet.</div>
    @endforelse
</div>

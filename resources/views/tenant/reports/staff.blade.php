@extends('layouts.app')
@section('title', 'Staff Performance')

@push('styles')
<style>
.range-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }

/* ── Staff cards ─────────────────────────────────────────────────── */
.staff-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:14px; margin-bottom:20px; }

.staff-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden;
    transition:border-color .15s, transform .15s;
}
.staff-card:hover { border-color:var(--border-strong); transform:translateY(-1px); }
.staff-card.rank-1 { border-top:3px solid #FFD700; }
.staff-card.rank-2 { border-top:3px solid #C0C0C0; }
.staff-card.rank-3 { border-top:3px solid #CD7F32; }

.sc-head { padding:16px; display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--border-subtle); }
.sc-av   { width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; flex-shrink:0; }
.sc-name { font-size:14px; font-weight:700; color:var(--text-100); }
.sc-role { font-size:12px; color:var(--text-300); margin-top:1px; }
.sc-rank { margin-left:auto; font-size:20px; }

.sc-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:0; }
.metric-cell { padding:12px; text-align:center; border-right:1px solid var(--border-subtle); border-bottom:1px solid var(--border-subtle); }
.metric-cell:nth-child(3n) { border-right:none; }
.metric-cell:nth-child(n+4) { border-bottom:none; }
.metric-num   { font-size:18px; font-weight:800; font-family:var(--mono); color:var(--text-100); }
.metric-label { font-size:10.5px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-top:2px; }

.sc-foot { padding:12px 16px; background:var(--bg-elevated); display:flex; align-items:center; justify-content:space-between; }
.score-bar { flex:1; height:6px; background:var(--border-subtle); border-radius:3px; overflow:hidden; margin:0 12px; }
.score-fill { height:100%; border-radius:3px; background:var(--accent); }
.score-label { font-size:12px; font-family:var(--mono); color:var(--text-300); }
.conv-badge { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }

/* ── Leaderboard table ───────────────────────────────────────────── */
.lb-table { width:100%; border-collapse:collapse; }
.lb-table th { padding:10px 16px; text-align:left; font-size:11px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.lb-table th.right { text-align:right; }
.lb-table td { padding:12px 16px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.lb-table td.right { text-align:right; font-family:var(--mono); }
.lb-table tr:last-child td { border-bottom:none; }
.lb-table tbody tr:hover td { background:var(--bg-elevated); }

.rank-badge { width:26px; height:26px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; }
</style>
@endpush

@section('content')

@php
    $ranges  = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
    $avColors = [['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],['#FEE2E2','#991B1B']];
    $rankEmojis = ['🥇','🥈','🥉'];
    $maxScore = collect($staffList)->max('score') ?: 1;
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Staff Performance
        </div>
        <div class="page-title">Staff Performance</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.staff', ['range'=>$key]) }}"
       class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- Info box --}}
<div style="padding:12px 16px;background:var(--accent-dim);border:1.5px solid rgba(99,120,255,.2);border-radius:var(--r-md);margin-bottom:20px;font-size:13px;color:var(--text-200)">
    <strong style="color:var(--accent)">Score Formula:</strong>
    Leads Created (×2) + Deals Won (×10) + Tasks Completed (×1) + Follow-ups Done (×2)
</div>

@if($staffList->isEmpty())
<div class="card" style="padding:60px 20px;text-align:center">
    <div style="font-size:40px;margin-bottom:12px">👥</div>
    <div style="font-size:15px;font-weight:700;color:var(--text-100);margin-bottom:6px">No staff data found</div>
    <div style="font-size:13px;color:var(--text-300)">Add staff members to see performance metrics</div>
</div>
@else

{{-- Staff performance cards --}}
<div class="staff-grid">
    @foreach($staffList as $i => $s)
    @php
        $user       = $s['user'];
        [$avBg, $avTx] = $avColors[$i % 5];
        $rankClass  = $i < 3 ? 'rank-'.($i+1) : '';
        $scorePct   = $maxScore > 0 ? round(($s['score'] / $maxScore) * 100) : 0;
        $convColor  = $s['conversion_rate'] >= 50 ? 'var(--green)' : ($s['conversion_rate'] >= 25 ? 'var(--amber)' : 'var(--red)');
    @endphp
    <div class="staff-card {{ $rankClass }}">

        {{-- Head --}}
        <div class="sc-head">
            <div class="sc-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div class="sc-name">{{ $user->name }}</div>
                <div class="sc-role">{{ ucfirst(str_replace('_',' ', $user->roles->first()?->name ?? 'staff')) }}</div>
            </div>
            @if($i < 3)
            <div class="sc-rank">{{ $rankEmojis[$i] }}</div>
            @else
            <div class="sc-rank" style="font-size:13px;font-weight:700;color:var(--text-400);font-family:var(--mono)">#{{ $i+1 }}</div>
            @endif
        </div>

        {{-- Metrics grid --}}
        <div class="sc-metrics">
            @php $metricsData = [
                ['num'=>$s['leads_created'],  'label'=>'Leads Created',  'color'=>'var(--accent)'],
                ['num'=>$s['leads_assigned'], 'label'=>'Leads Assigned', 'color'=>'var(--accent)'],
                ['num'=>$s['deals_won'],      'label'=>'Deals Won',      'color'=>'var(--green)'],
                ['num'=>'₹'.number_format($s['deal_value']/1000,0).'K', 'label'=>'Deal Value', 'color'=>'var(--green)'],
                ['num'=>$s['tasks_done'],     'label'=>'Tasks Done',     'color'=>'var(--amber)'],
                ['num'=>$s['followups_done'], 'label'=>'Follow-ups',     'color'=>'var(--purple)'],
            ]; @endphp
            @foreach($metricsData as $m)
            <div class="metric-cell">
                <div class="metric-num" style="color:{{ $m['color'] }}">{{ $m['num'] }}</div>
                <div class="metric-label">{{ $m['label'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Footer: score bar + conversion --}}
        <div class="sc-foot">
            <div style="font-size:11px;color:var(--text-400);font-weight:600;white-space:nowrap">Score: <span style="color:var(--accent);font-family:var(--mono)">{{ $s['score'] }}</span></div>
            <div class="score-bar">
                <div class="score-fill" style="width:{{ $scorePct }}%"></div>
            </div>
            <span class="conv-badge" style="background:{{ $s['conversion_rate'] >= 50 ? 'var(--green-dim)' : 'var(--amber-dim)' }};color:{{ $convColor }}">
                {{ $s['conversion_rate'] }}% conv.
            </span>
        </div>

    </div>
    @endforeach
</div>

{{-- Leaderboard table --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
    <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);font-size:14px;font-weight:700;color:var(--text-100)">
        Full Leaderboard
    </div>
    <div style="overflow-x:auto">
        <table class="lb-table data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Staff</th>
                    <th class="right">Leads</th>
                    <th class="right">Deals Won</th>
                    <th class="right">Deal Value</th>
                    <th class="right">Tasks Done</th>
                    <th class="right">Follow-ups</th>
                    <th class="right">Conversion</th>
                    <th class="right">Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffList as $i => $s)
                @php
                    $user = $s['user'];
                    [$avBg,$avTx] = $avColors[$i % 5];
                    $rankBg    = match($i) { 0=>'#FFD700', 1=>'#C0C0C0', 2=>'#CD7F32', default=>'var(--bg-elevated)' };
                    $rankColor = $i < 3 ? '#1a1a1a' : 'var(--text-300)';
                @endphp
                <tr>
                    <td data-label="Rank">
                        <span class="rank-badge" style="background:{{ $rankBg }};color:{{ $rankColor }}">
                            {{ $i < 3 ? ($rankEmojis[$i]) : ($i+1) }}
                        </span>
                    </td>
                    <td data-label="Staff">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:30px;height:30px;border-radius:50%;background:{{ $avBg }};color:{{ $avTx }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($user->name,0,1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13.5px;color:var(--text-100)">{{ $user->name }}</div>
                                <div style="font-size:11.5px;color:var(--text-400)">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="right" style="color:var(--accent)" data-label="Leads">{{ $s['leads_created'] }}</td>
                    <td class="right" style="color:var(--green)" data-label="Deals Won">{{ $s['deals_won'] }}</td>
                    <td class="right" style="color:var(--green)" data-label="Deal Value">₹{{ number_format($s['deal_value']/1000,0) }}K</td>
                    <td class="right" style="color:var(--amber)" data-label="Tasks Done">{{ $s['tasks_done'] }}</td>
                    <td class="right" style="color:var(--purple)" data-label="Follow-ups">{{ $s['followups_done'] }}</td>
                    <td class="right" data-label="Conversion">
                        <span style="font-size:12px;font-weight:700;color:{{ $s['conversion_rate'] >= 50 ? 'var(--green)' : 'var(--amber)' }}">
                            {{ $s['conversion_rate'] }}%
                        </span>
                    </td>
                    <td class="right" data-label="Score">
                        <span style="font-size:14px;font-weight:800;color:var(--accent);font-family:var(--mono)">
                            {{ $s['score'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif

@endsection
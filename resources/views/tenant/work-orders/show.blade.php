@extends('layouts.app')
@section('title', 'Work Order — ' . $workOrder->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.ps { font-family: 'DM Sans', var(--font), sans-serif; }
.ps-layout { display:grid; grid-template-columns:minmax(0,1fr) 290px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .ps-layout { grid-template-columns:1fr; } }
.ps-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.ps-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); }
.ps-card-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }
.ps-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.ps-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.ps-hero { padding:22px; }
.ps-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; margin-bottom:3px; }
.ps-status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.ps-items-table { width:100%; border-collapse:collapse; }
.ps-items-table thead tr { background:var(--bg-elevated); }
.ps-items-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.ps-items-table td { padding:13px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.ps-items-table tr:last-child td { border-bottom:none; }
.dl-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.dl-row:last-child { border-bottom:none; }
.dl-key { font-size:12px; color:var(--text-300); }
.dl-val { font-size:12.5px; font-weight:500; color:var(--text-100); text-align:right; }
.qs-action-btn { display:flex; align-items:center; gap:9px; padding:10px 13px; border-radius:9px; border:1px solid var(--border-default); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.qs-action-btn:hover { background:var(--bg-surface); border-color:var(--border-strong); }
.qs-action-btn + .qs-action-btn { margin-top:7px; }
.qs-act-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bom-ok { color:var(--green); font-weight:600; }
.bom-short { color:var(--red); font-weight:600; }
.pf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
}
.pf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'pending'     => ['bg' => 'var(--amber-dim)', 'color' => 'var(--amber)', 'text' => 'var(--amber)', 'icon' => 'ti-clock'],
        'in_progress' => ['bg' => 'var(--accent-dim)', 'color' => 'var(--accent)', 'text' => 'var(--accent)', 'icon' => 'ti-player-play'],
        'completed'   => ['bg' => 'var(--green-dim)', 'color' => 'var(--green)', 'text' => 'var(--green)', 'icon' => 'ti-circle-check'],
        'cancelled'   => ['bg' => 'var(--red-dim)', 'color' => 'var(--red)', 'text' => 'var(--red)', 'icon' => 'ti-circle-x'],
    ];
    $st = $statusMeta[$workOrder->status] ?? $statusMeta['pending'];
    $bom = $workOrder->product->billOfMaterials ?? collect();
@endphp

<div class="ps">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.work-orders.index') }}" style="color:var(--text-300);text-decoration:none">Work Orders</a>
                <span style="opacity:.4">›</span>
                <span>{{ $workOrder->number }}</span>
            </div>
            <div class="page-title">Work Order Detail</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @can('modify', $workOrder)
            <a href="{{ route('tenant.work-orders.edit',$workOrder->id) }}" class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    @foreach(['success','error'] as $type)
    @if(session($type))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:{{ $type==='success'?'var(--green-dim)':'var(--red-dim)' }};border:1px solid {{ $type==='success'?'var(--green)':'var(--red)' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $type==='success'?'var(--green)':'var(--red)' }};font-weight:500">
        {{ session($type) }}
    </div>
    @endif
    @endforeach

    <div class="ps-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="ps-card">
                <div class="ps-hero">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <div>
                            <div class="ps-number">{{ $workOrder->number }}</div>
                            <div style="font-size:13px;color:var(--text-300)">Building {{ number_format($workOrder->quantity, 2) }} × {{ $workOrder->product?->name }}</div>
                        </div>
                        <span class="ps-status-badge" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};border:1px solid {{ $st['color'] }}40">
                            <i class="ti {{ $st['icon'] }}" style="font-size:14px"></i> {{ \App\Models\WorkOrder::statuses()[$workOrder->status] ?? ucfirst($workOrder->status) }}
                        </span>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:18px">
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Created By</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100)">{{ $workOrder->createdBy?->name ?? '—' }}</div>
                        </div>
                        @if($workOrder->started_at)
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Started</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100);font-family:'DM Mono',monospace">{{ $workOrder->started_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                        @if($workOrder->completed_at)
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Completed</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100);font-family:'DM Mono',monospace">{{ $workOrder->completed_at->format('d M Y, h:i A') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title"><i class="ti ti-list-details" style="font-size:13px;margin-right:5px"></i> Bill of Materials</div>
                </div>
                <div style="overflow-x:auto">
                    <table class="ps-items-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th style="text-align:right">Needed</th>
                                @if(in_array($workOrder->status, ['pending','in_progress']))
                                <th style="text-align:right">In Stock</th>
                                <th style="text-align:right">Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bom as $line)
                            @php
                                $material = $line->material;
                                $needed = (float) $line->quantity_per_unit * (float) $workOrder->quantity;
                                $short = collect($shortfall)->firstWhere('material_id', $material?->id);
                            @endphp
                            <tr>
                                <td style="font-weight:500">{{ $material?->name ?? '—' }}</td>
                                <td style="text-align:right;font-family:'DM Mono',monospace">{{ number_format($needed, 2) }}</td>
                                @if(in_array($workOrder->status, ['pending','in_progress']))
                                <td style="text-align:right;font-family:'DM Mono',monospace">{{ number_format($material?->current_stock ?? 0, 2) }}</td>
                                <td style="text-align:right" class="{{ $short ? 'bom-short' : 'bom-ok' }}">{{ $short ? 'Short by '.$short['shortfall'] : 'OK' }}</td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;color:var(--text-300)">No BOM configured for this product.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($workOrder->notes)
                <div style="padding:16px 20px;border-top:1px solid var(--border-subtle)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Notes</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6">{{ $workOrder->notes }}</div>
                </div>
                @endif
            </div>

            @can('editCosts', $workOrder)
            <div class="ps-card">
                <div class="ps-card-head">
                    <div class="ps-card-title"><i class="ti ti-calculator" style="font-size:13px;margin-right:5px"></i> Production Cost</div>
                </div>
                <div style="padding:16px 20px">
                    <div class="dl-row">
                        <span class="dl-key">
                            Material Cost
                            <span style="opacity:.6">({{ $workOrder->material_cost_snapshot !== null ? 'frozen at completion' : 'live BOM estimate' }})</span>
                        </span>
                        <span class="dl-val" style="font-family:'DM Mono',monospace">₹{{ number_format($workOrder->material_cost, 2) }}</span>
                    </div>

                    <form method="POST" action="{{ route('tenant.work-orders.update-costs',$workOrder->id) }}" style="margin-top:10px">
                        @csrf
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                            <div>
                                <label class="pf-label" for="wo_labor_cost">Labor Cost (₹)</label>
                                <input type="number" name="labor_cost" id="wo_labor_cost" class="pf-input" min="0" step="0.01"
                                       value="{{ old('labor_cost', $workOrder->labor_cost) }}" style="margin-top:5px"/>
                            </div>
                            <div>
                                <label class="pf-label" for="wo_machine_cost">Machine Cost (₹)</label>
                                <input type="number" name="machine_cost" id="wo_machine_cost" class="pf-input" min="0" step="0.01"
                                       value="{{ old('machine_cost', $workOrder->machine_cost) }}" style="margin-top:5px"/>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-secondary" style="margin-top:10px;width:100%;justify-content:center">
                            <i class="ti ti-device-floppy" style="font-size:14px"></i> Save Costs
                        </button>
                    </form>

                    <div style="height:1px;background:var(--border-subtle);margin:14px 0"></div>

                    <div class="dl-row"><span class="dl-key">Total Cost</span><span class="dl-val" style="font-family:'DM Mono',monospace;font-weight:700">₹{{ number_format($workOrder->total_cost, 2) }}</span></div>
                    <div class="dl-row"><span class="dl-key">Cost / Unit</span><span class="dl-val" style="font-family:'DM Mono',monospace">₹{{ number_format($workOrder->cost_per_unit, 2) }}</span></div>

                    @if($workOrder->product?->rate)
                    <div style="height:1px;background:var(--border-subtle);margin:14px 0"></div>
                    <div class="dl-row"><span class="dl-key">Selling Value <span style="opacity:.6">(at current rate × qty)</span></span><span class="dl-val" style="font-family:'DM Mono',monospace">₹{{ number_format($workOrder->selling_value, 2) }}</span></div>
                    <div class="dl-row">
                        <span class="dl-key">Margin</span>
                        <span class="dl-val" style="font-family:'DM Mono',monospace;font-weight:700;color:{{ $workOrder->margin >= 0 ? 'var(--green)' : 'var(--red)' }}">
                            ₹{{ number_format($workOrder->margin, 2) }} ({{ $workOrder->margin_percent }}%)
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endcan

        </div>

        <div style="display:flex;flex-direction:column;gap:14px">

            @can('manage', $workOrder)
            <div class="ps-sc">
                <div class="ps-sc-title">Production</div>

                @if($workOrder->isPending())
                <form method="POST" action="{{ route('tenant.work-orders.start',$workOrder->id) }}"
                      onsubmit="return confirm('Start production on this work order?')">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="background:var(--accent-dim);border-color:#B7D6F3;color:var(--accent)">
                        <div class="qs-act-icon" style="background:var(--accent-dim)"><i class="ti ti-player-play" style="font-size:15px;color:var(--accent)"></i></div>
                        Start Production
                    </button>
                </form>
                @endif

                @if($workOrder->isInProgress())
                <form method="POST" action="{{ route('tenant.work-orders.complete',$workOrder->id) }}"
                      onsubmit="return confirm('Complete this work order? Raw materials will be consumed and finished-good stock credited.')">
                    @csrf
                    <label class="pf-label" for="wo_fg_expiry" style="display:block;margin-bottom:5px">Finished Good Expiry (optional)</label>
                    <input type="date" name="expiry_date" id="wo_fg_expiry" class="pf-input" style="margin-bottom:8px"/>
                    <button type="submit" class="qs-action-btn" style="background:var(--green-dim);border-color:var(--green);color:var(--green)">
                        <div class="qs-act-icon" style="background:var(--green-dim)"><i class="ti ti-circle-check" style="font-size:15px;color:var(--green)"></i></div>
                        Mark Completed
                    </button>
                </form>
                @endif

                <form method="POST" action="{{ route('tenant.work-orders.cancel',$workOrder->id) }}" style="margin-top:7px"
                      onsubmit="return confirm('Cancel this work order?')">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                        <div class="qs-act-icon" style="background:var(--red-dim)"><i class="ti ti-circle-x" style="font-size:15px;color:var(--red)"></i></div>
                        Cancel Work Order
                    </button>
                </form>
            </div>
            @endcan

            <div class="ps-sc">
                <div class="ps-sc-title">Details</div>
                <div>
                    <div class="dl-row"><span class="dl-key">Number</span><span class="dl-val" style="font-family:'DM Mono',monospace">{{ $workOrder->number }}</span></div>
                    <div class="dl-row"><span class="dl-key">Product</span><span class="dl-val">{{ $workOrder->product?->name ?? '—' }}</span></div>
                    <div class="dl-row"><span class="dl-key">Quantity</span><span class="dl-val">{{ number_format($workOrder->quantity, 2) }}</span></div>
                    <div class="dl-row"><span class="dl-key">Created</span><span class="dl-val">{{ $workOrder->created_at->format('M d, Y') }}</span></div>
                </div>
            </div>

            @can('delete', $workOrder)
            <div class="ps-sc" style="border-color:var(--red)">
                <div class="ps-sc-title" style="color:var(--red)">Danger Zone</div>
                <form method="POST" action="{{ route('tenant.work-orders.destroy',$workOrder->id) }}"
                      onsubmit="return confirm('Delete work order {{ $workOrder->number }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                        <i class="ti ti-trash" style="font-size:14px"></i> Delete Work Order
                    </button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection

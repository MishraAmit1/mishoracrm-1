@extends('layouts.app')
@section('title', 'Purchase Order — ' . $purchaseOrder->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.qs { font-family: 'DM Sans', var(--font), sans-serif; }
.qs-layout { display:grid; grid-template-columns:minmax(0,1fr) 290px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .qs-layout { grid-template-columns:1fr; } }
.qs-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.qs-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); }
.qs-card-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }
.qs-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.qs-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.qs-hero { padding:22px; }
.qs-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; margin-bottom:3px; }
.qs-status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.qs-items-table { width:100%; border-collapse:collapse; }
.qs-items-table thead tr { background:var(--bg-elevated); }
.qs-items-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.qs-items-table th:last-child { text-align:right; }
.qs-items-table td { padding:13px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.qs-items-table tr:last-child td { border-bottom:none; }
.qs-items-table .td-right { text-align:right; font-family:'DM Mono',monospace; font-weight:500; }
.qs-totals-wrap { padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; }
.qs-totals-table { width:280px; }
.qs-totals-table tr td { padding:5px 0; font-size:13px; color:var(--text-200); }
.qs-totals-table tr td:last-child { text-align:right; font-family:'DM Mono',monospace; font-weight:500; color:var(--text-100); }
.qs-totals-table .grand td { padding-top:10px; font-size:15px; font-weight:600; color:var(--text-100); border-top:1px solid var(--border-default); }
.qs-totals-table .grand td:last-child { color:#185FA5; font-size:17px; }
.qs-action-btn { display:flex; align-items:center; gap:9px; padding:10px 13px; border-radius:9px; border:1px solid var(--border-default); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.qs-action-btn:hover { background:var(--bg-surface); border-color:var(--border-strong); }
.qs-action-btn + .qs-action-btn { margin-top:7px; }
.qs-act-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qs-status-opt { display:flex; align-items:center; gap:8px; padding:8px 11px; border-radius:8px; border:1.5px solid var(--border-default); cursor:pointer; font-size:12.5px; font-weight:500; background:var(--bg-input); color:var(--text-200); font-family:'DM Sans',var(--font),sans-serif; width:100%; text-align:left; }
.qs-status-opt + .qs-status-opt { margin-top:6px; }
.qs-status-opt:hover { border-color:var(--border-strong); color:var(--text-100); }
.dl-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.dl-row:last-child { border-bottom:none; }
.dl-key { font-size:12px; color:var(--text-300); }
.dl-val { font-size:12.5px; font-weight:500; color:var(--text-100); text-align:right; }
.recv-input { width:90px; padding:6px 8px; border:1.5px solid var(--border-default); border-radius:6px; background:var(--bg-input); color:var(--text-100); font-family:'DM Mono',monospace; font-size:13px; }
.recv-input:focus { border-color:var(--accent); outline:none; box-shadow:0 0 0 2px var(--accent-dim); }
</style>
@endpush

@section('content')
@php
    $statusMeta = [
        'draft'               => ['bg' => '#F3F4F6', 'color' => '#374151', 'text' => '#374151', 'icon' => 'ti-file'],
        'sent'                => ['bg' => '#FAEEDA', 'color' => '#BA7517', 'text' => '#854F0B', 'icon' => 'ti-send'],
        'partially_received'  => ['bg' => '#E6F1FB', 'color' => '#185FA5', 'text' => '#185FA5', 'icon' => 'ti-package'],
        'received'            => ['bg' => '#E1F5EE', 'color' => '#1D9E75', 'text' => '#0F6E56', 'icon' => 'ti-circle-check'],
        'cancelled'           => ['bg' => '#FCEBEB', 'color' => '#E24B4A', 'text' => '#A32D2D', 'icon' => 'ti-circle-x'],
    ];
    $st = $statusMeta[$purchaseOrder->status] ?? $statusMeta['draft'];
    $items = $purchaseOrder->items ?? [];
    $canEdit = !in_array($purchaseOrder->status, ['received','cancelled']);
    $canReceive = in_array($purchaseOrder->status, ['sent','partially_received']);
@endphp

<div class="qs">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.purchase-orders.index') }}" style="color:var(--text-300);text-decoration:none">Purchase Orders</a>
                <span style="opacity:.4">›</span>
                <span>{{ $purchaseOrder->number }}</span>
            </div>
            <div class="page-title">Purchase Order Detail</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('tenant.purchase-orders.pdf',$purchaseOrder->id) }}" target="_blank" class="btn btn-secondary">
                <i class="ti ti-download" style="font-size:14px"></i> PDF
            </a>
            @can('modify', $purchaseOrder)
            @if($canEdit)
            <a href="{{ route('tenant.purchase-orders.edit',$purchaseOrder->id) }}" class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px"></i> Edit
            </a>
            @endif
            @endcan
        </div>
    </div>

    @foreach(['success','error'] as $type)
    @if(session($type))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:{{ $type==='success'?'#E1F5EE':'#FCEBEB' }};border:1px solid {{ $type==='success'?'#9FE1CB':'#F09595' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $type==='success'?'#0F6E56':'#A32D2D' }};font-weight:500">
        {{ session($type) }}
    </div>
    @endif
    @endforeach

    <div class="qs-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="qs-card">
                <div class="qs-hero">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <div>
                            <div class="qs-number">{{ $purchaseOrder->number }}</div>
                            <div style="font-size:13px;color:var(--text-300)">{{ auth()->user()->tenant->name ?? auth()->user()->name }}</div>
                        </div>
                        <span class="qs-status-badge" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};border:1px solid {{ $st['color'] }}40">
                            <i class="ti {{ $st['icon'] }}" style="font-size:14px"></i> {{ ucfirst(str_replace('_',' ',$purchaseOrder->status)) }}
                        </span>
                    </div>
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:18px">
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Date</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100);font-family:'DM Mono',monospace">{{ $purchaseOrder->date?->format('M d, Y') }}</div>
                        </div>
                        @if($purchaseOrder->expected_delivery_date)
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Expected Delivery</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100);font-family:'DM Mono',monospace">{{ $purchaseOrder->expected_delivery_date->format('M d, Y') }}</div>
                        </div>
                        @endif
                        <div>
                            <div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px">Created By</div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-100)">{{ $purchaseOrder->createdBy?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if($purchaseOrder->vendor)
            <div class="qs-card">
                <div class="qs-card-head"><div class="qs-card-title"><i class="ti ti-building-warehouse" style="font-size:13px;margin-right:5px"></i> Vendor</div></div>
                <div style="padding:18px 20px">
                    <div style="font-size:15px;font-weight:600;color:var(--text-100)">{{ $purchaseOrder->vendor->name }}</div>
                    @if($purchaseOrder->vendor->company)<div style="font-size:13px;color:var(--text-300);margin-top:2px">{{ $purchaseOrder->vendor->company }}</div>@endif
                    <div style="margin-top:8px;font-size:12.5px;color:var(--text-300);display:flex;flex-direction:column;gap:3px">
                        @if($purchaseOrder->vendor->phone)<div><i class="ti ti-phone" style="font-size:12px"></i> {{ $purchaseOrder->vendor->phone }}</div>@endif
                        @if($purchaseOrder->vendor->email)<div><i class="ti ti-mail" style="font-size:12px"></i> {{ $purchaseOrder->vendor->email }}</div>@endif
                    </div>
                </div>
            </div>
            @else
            <div style="display:flex;align-items:center;gap:9px;padding:11px 15px;background:#FAEEDA;border:1px solid #F0D9A8;border-radius:8px;font-size:13px;color:#854F0B;font-weight:500">
                <i class="ti ti-alert-triangle" style="font-size:15px"></i>
                No vendor selected yet — required before this order can be marked Sent.
            </div>
            @endif

            <div class="qs-card">
                <div class="qs-card-head"><div class="qs-card-title"><i class="ti ti-list-details" style="font-size:13px;margin-right:5px"></i> Line Items ({{ count($items) }})</div></div>
                <div style="overflow-x:auto">
                    <table class="qs-items-table">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:22%">Item</th>
                                <th style="width:18%">Description</th>
                                <th style="width:8%;text-align:right">Qty</th>
                                <th style="width:12%;text-align:right">Rate</th>
                                <th style="width:8%;text-align:right">GST %</th>
                                <th style="width:12%;text-align:right">Amount</th>
                                <th style="width:15%;text-align:right">Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $idx => $item)
                            @php
                                $qty = (float)($item['quantity'] ?? 0);
                                $recv = (float)($item['received_quantity'] ?? 0);
                            @endphp
                            <tr>
                                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-400)">{{ $idx+1 }}</td>
                                <td style="font-weight:500">{{ $item['name'] ?? '—' }}</td>
                                <td style="color:var(--text-300);font-size:12.5px">{{ $item['description'] ?? '—' }}</td>
                                <td class="td-right">{{ number_format($qty, 2) }}</td>
                                <td class="td-right">{{ number_format($item['rate'] ?? 0, 2) }}</td>
                                <td class="td-right">{{ number_format($item['tax_percent'] ?? 0, 1) }}%</td>
                                <td class="td-right" style="font-weight:600">{{ number_format($item['amount'] ?? 0, 2) }}</td>
                                <td class="td-right" style="{{ $recv >= $qty && $qty > 0 ? 'color:#1D9E75' : ($recv > 0 ? 'color:#BA7517' : '') }}">{{ number_format($recv, 2) }} / {{ number_format($qty, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="qs-totals-wrap">
                    <table class="qs-totals-table">
                        <tr><td>Subtotal</td><td>₹{{ number_format($purchaseOrder->subtotal ?? 0, 2) }}</td></tr>
                        @if(($purchaseOrder->discount ?? 0) > 0)
                        <tr><td>Discount</td><td style="color:#E24B4A">-₹{{ number_format($purchaseOrder->discount, 2) }}</td></tr>
                        @endif
                        <tr><td>Tax ({{ number_format($purchaseOrder->tax_percent ?? 0, 1) }}%)</td><td style="color:#1D9E75">+₹{{ number_format($purchaseOrder->tax_amount ?? 0, 2) }}</td></tr>
                        <tr class="grand"><td><strong>Total</strong></td><td><strong>₹{{ number_format($purchaseOrder->total ?? 0, 2) }}</strong></td></tr>
                    </table>
                </div>
                @if($purchaseOrder->notes)
                <div style="padding:16px 20px;border-top:1px solid var(--border-subtle)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Notes</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6;white-space:pre-wrap">{{ $purchaseOrder->notes }}</div>
                </div>
                @endif
                @if($purchaseOrder->terms)
                <div style="padding:16px 20px;border-top:1px solid var(--border-subtle);background:var(--bg-elevated)">
                    <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Terms & Conditions</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6;white-space:pre-wrap">{{ $purchaseOrder->terms }}</div>
                </div>
                @endif
            </div>

            @can('receive', $purchaseOrder)
            @if($canReceive)
            <div class="qs-card">
                <div class="qs-card-head"><div class="qs-card-title"><i class="ti ti-package-import" style="font-size:13px;margin-right:5px"></i> Receive Items</div></div>
                <form method="POST" action="{{ route('tenant.purchase-orders.receive',$purchaseOrder->id) }}">
                    @csrf
                    <div style="overflow-x:auto">
                        <table class="qs-items-table">
                            <thead>
                                <tr>
                                    <th style="width:35%">Item</th>
                                    <th style="width:20%;text-align:right">Ordered</th>
                                    <th style="width:20%;text-align:right">Already Received</th>
                                    <th style="width:25%;text-align:right">Received Now</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $idx => $item)
                                @php $qty = (float)($item['quantity'] ?? 0); $recv = (float)($item['received_quantity'] ?? 0); @endphp
                                <tr>
                                    <td style="font-weight:500">{{ $item['name'] ?? '—' }}</td>
                                    <td class="td-right">{{ number_format($qty,2) }}</td>
                                    <td class="td-right">{{ number_format($recv,2) }}</td>
                                    <td class="td-right">
                                        <input type="number" class="recv-input" name="items[{{ $idx }}][received_quantity]"
                                               value="{{ $recv }}" min="0" max="{{ $qty }}" step="0.01"/>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="padding:14px 20px;display:flex;justify-content:flex-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check" style="font-size:14px"></i> Update Received Quantities
                        </button>
                    </div>
                </form>
            </div>
            @endif
            @endcan

            @if($purchaseOrder->purchaseRequest)
            <div class="qs-card">
                <div class="qs-card-head"><div class="qs-card-title"><i class="ti ti-clipboard-list" style="font-size:13px;margin-right:5px"></i> Linked Purchase Request</div></div>
                <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $purchaseOrder->purchaseRequest->number }}</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px">{{ ucfirst($purchaseOrder->purchaseRequest->status) }}</div>
                    </div>
                    <a href="{{ route('tenant.purchase-requests.show', $purchaseOrder->purchaseRequest->id) }}" class="btn btn-secondary" style="font-size:12px;padding:6px 12px">View PR</a>
                </div>
            </div>
            @endif

        </div>

        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="qs-sc" style="text-align:center">
                <div style="font-size:13px;color:var(--text-300);font-family:'DM Mono',monospace;margin-bottom:4px">{{ $purchaseOrder->number }}</div>
                <div style="font-size:34px;font-weight:600;color:#185FA5;font-family:'DM Mono',monospace">₹{{ number_format($purchaseOrder->total ?? 0, 2) }}</div>
                <div style="margin-top:10px">
                    <span class="qs-status-badge" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};border:1px solid {{ $st['color'] }}40;font-size:12px">
                        <i class="ti {{ $st['icon'] }}" style="font-size:13px"></i> {{ ucfirst(str_replace('_',' ',$purchaseOrder->status)) }}
                    </span>
                </div>
            </div>

            @can('modify', $purchaseOrder)
            <div class="qs-sc">
                <div class="qs-sc-title">Update Status</div>
                @if($purchaseOrder->status === 'draft')
                <form method="POST" action="{{ route('tenant.purchase-orders.update_status',$purchaseOrder->id) }}">
                    @csrf <input type="hidden" name="status" value="sent">
                    <button type="button" class="qs-status-opt" onclick="this.closest('form').submit()">Mark as Sent</button>
                </form>
                @endif
                @if(!in_array($purchaseOrder->status, ['cancelled','received']))
                <form method="POST" action="{{ route('tenant.purchase-orders.update_status',$purchaseOrder->id) }}" style="margin-top:6px"
                      onsubmit="return confirm('Cancel this purchase order?')">
                    @csrf <input type="hidden" name="status" value="cancelled">
                    <button type="button" class="qs-status-opt" style="color:#A32D2D" onclick="this.closest('form').submit()">Cancel Order</button>
                </form>
                @endif
            </div>
            @endcan

            <div class="qs-sc">
                <div class="qs-sc-title">Actions</div>
                <a href="{{ route('tenant.purchase-orders.pdf',$purchaseOrder->id) }}" target="_blank" class="qs-action-btn">
                    <div class="qs-act-icon" style="background:#FAEEDA"><i class="ti ti-file-download" style="font-size:15px;color:#BA7517"></i></div>
                    Download PDF
                </a>
                @if($purchaseOrder->vendor?->email)
                <form method="POST" action="{{ route('tenant.purchase-orders.send',$purchaseOrder->id) }}"
                      onsubmit="return confirm('Send this purchase order to {{ addslashes($purchaseOrder->vendor->email) }}?')">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="margin-top:7px">
                        <div class="qs-act-icon" style="background:#E6F1FB"><i class="ti ti-send" style="font-size:15px;color:#185FA5"></i></div>
                        Send to {{ $purchaseOrder->vendor->email }}
                    </button>
                </form>
                @endif
                @can('modify', $purchaseOrder)
                @if($canEdit)
                <a href="{{ route('tenant.purchase-orders.edit',$purchaseOrder->id) }}" class="qs-action-btn" style="margin-top:7px">
                    <div class="qs-act-icon" style="background:#EEEDFE"><i class="ti ti-edit" style="font-size:15px;color:#534AB7"></i></div>
                    Edit Purchase Order
                </a>
                @endif
                @endcan
            </div>

            <div class="qs-sc">
                <div class="qs-sc-title">Details</div>
                <div>
                    <div class="dl-row"><span class="dl-key">PO #</span><span class="dl-val" style="font-family:'DM Mono',monospace">{{ $purchaseOrder->number }}</span></div>
                    <div class="dl-row"><span class="dl-key">Items</span><span class="dl-val">{{ count($items) }}</span></div>
                    <div class="dl-row"><span class="dl-key">Created</span><span class="dl-val">{{ $purchaseOrder->created_at->format('M d, Y') }}</span></div>
                    <div class="dl-row"><span class="dl-key">Updated</span><span class="dl-val" style="font-size:12px">{{ $purchaseOrder->updated_at->diffForHumans() }}</span></div>
                </div>
            </div>

            @if($purchaseOrder->status === 'draft')
            @can('delete', $purchaseOrder)
            <div class="qs-sc" style="border-color:#F09595">
                <div class="qs-sc-title" style="color:#A32D2D">Danger Zone</div>
                <form method="POST" action="{{ route('tenant.purchase-orders.destroy',$purchaseOrder->id) }}"
                      onsubmit="return confirm('Delete purchase order {{ $purchaseOrder->number }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn" style="width:100%;justify-content:center;background:#FCEBEB;border-color:#F09595;color:#A32D2D;font-size:12.5px">
                        <i class="ti ti-trash" style="font-size:14px"></i> Delete Purchase Order
                    </button>
                </form>
            </div>
            @endcan
            @endif

        </div>
    </div>
</div>
@endsection

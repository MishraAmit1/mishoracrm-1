@extends('layouts.app')
@section('title', 'Edit Quotation — ' . $quotation->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.qf { font-family: 'DM Sans', var(--font), sans-serif; }

.qf-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 280px;
    gap: 16px;
    margin-top: 20px;
}
@media(max-width:960px){ .qf-layout { grid-template-columns:1fr; } }

.qf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.qf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.qf-section:last-of-type { border-bottom:none; }
.qf-sec-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
.qf-sec-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qf-sec-title { font-size:13px; font-weight:600; color:var(--text-100); }
.qf-sec-sub   { font-size:12px; color:var(--text-300); margin-top:1px; }
.qf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.qf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .qf-grid { grid-template-columns:1fr; } .qf-grid .span-full { grid-column:1; } }
.qf-field { display:flex; flex-direction:column; gap:5px; }
.qf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.qf-req   { color:var(--red,#E24B4A); margin-left:2px; }
.qf-hint  { font-size:12px; color:var(--text-400); }
.qf-err   { font-size:12px; color:var(--red,#E24B4A); font-weight:500; }
.qf-input {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100);
    font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s,box-shadow .15s,background .15s; -webkit-appearance:none;
}
.qf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); background:var(--bg-surface); }
.qf-input::placeholder { color:var(--text-400); font-size:13px; }
.qf-input.is-err { border-color:var(--red,#E24B4A); }
.qf-sel  { cursor:pointer; }
.qf-area { resize:vertical; min-height:80px; line-height:1.55; }

/* Items table */
.items-table-wrap { overflow-x:auto; }
.items-table { width:100%; border-collapse:collapse; font-size:13px; min-width:620px; }
.items-table thead tr { background:var(--bg-elevated); }
.items-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.items-table td { padding:7px 6px; border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.items-table tr:last-child td { border-bottom:none; }
.items-table tr:hover td { background:var(--bg-elevated); }
.item-input { width:100%; padding:7px 9px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:7px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; outline:none; transition:border-color .15s; -webkit-appearance:none; }
.item-input:focus { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-dim); }
.item-amount-input { font-family:'DM Mono',monospace; font-weight:600; background:var(--bg-elevated); color:var(--text-100); border-color:var(--border-subtle); cursor:default; }
.del-row-btn { width:28px;height:28px;border-radius:6px;background:transparent;border:1px solid var(--border-subtle);cursor:pointer;color:var(--text-400);display:flex;align-items:center;justify-content:center;transition:all .15s;margin:2px auto 0; }
.del-row-btn:hover { background:#FCEBEB;border-color:#F09595;color:#A32D2D; }
.add-item-btn { display:flex;align-items:center;gap:6px;padding:9px 16px;margin:12px 0 0;background:transparent;border:1.5px dashed var(--border-default);border-radius:8px;font-size:13px;color:var(--text-300);cursor:pointer;font-family:'DM Sans',var(--font),sans-serif;transition:all .15s; }
.add-item-btn:hover { border-color:var(--accent);color:var(--accent);background:rgba(55,138,221,.04); }

/* Totals */
.totals-wrap { display:flex; justify-content:flex-end; padding:16px 22px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); }
.totals-table { width:300px; }
.totals-table tr td { padding:5px 0; font-size:13px; color:var(--text-200); }
.totals-table tr td:last-child { text-align:right; font-family:'DM Mono',monospace; font-weight:500; color:var(--text-100); }
.totals-table .grand-total td { padding-top:10px; font-size:15px; font-weight:600; color:var(--text-100); border-top:1px solid var(--border-default); }
.totals-table .grand-total td:last-child { color:#185FA5; font-size:16px; }

/* Footer */
.qf-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.qf-footer-note { font-size:12px; color:var(--text-300); }
.qf-footer-note strong { color:var(--text-200); }

/* Sidebar */
.qf-sidebar { display:flex; flex-direction:column; gap:13px; }
.qf-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.qf-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }

/* Status Pills */
.status-pills { display:flex; flex-direction:column; gap:6px; }
.sp-opt { display:flex; align-items:center; gap:9px; padding:8px 11px; border-radius:8px; border:1.5px solid var(--border-default); cursor:pointer; transition:all .15s; font-size:12.5px; font-weight:500; background:var(--bg-input); color:var(--text-200); font-family:'DM Sans',var(--font),sans-serif; }
.sp-opt:hover { border-color:var(--border-strong); color:var(--text-100); }
.sp-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* Changed badge */
.changed-badge { display:none; align-items:center; gap:5px; padding:3px 9px; border-radius:20px; background:#FAEEDA; color:#854F0B; font-size:11px; font-weight:600; margin-left:8px; }
.changed-badge.show { display:inline-flex; }

/* Last updated bar */
.lu-bar { display:flex; align-items:center; gap:8px; padding:10px 18px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); font-size:12px; color:var(--text-300); }

/* Quick Actions */
.qa-btn { display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; border:1px solid var(--border-subtle); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:12.5px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.qa-btn:hover { background:var(--bg-surface); border-color:var(--border-default); }
.qa-icon { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot  { width:5px; height:5px; border-radius:50%; background:var(--accent,#378ADD); margin-top:5px; flex-shrink:0; }

@keyframes qf-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $qConfig    = config('quotation');
    $statuses   = $qConfig['statuses'];
    $taxOptions = $qConfig['tax_options'];
    $itemCols   = $qConfig['item_columns'];
    $currentStatus = old('status', $quotation->status);
    $existingItems = old('items', $quotation->items ?? []);
@endphp

<div class="qf">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.quotations.index') }}" style="color:var(--text-300);text-decoration:none">Quotations</a>
                <span style="opacity:.4">›</span>
                <a href="{{ route('tenant.quotations.show',$quotation->id) }}" style="color:var(--text-300);text-decoration:none">{{ $quotation->number }}</a>
                <span style="opacity:.4">›</span>
                <span>Edit</span>
            </div>
            <div class="page-title" style="display:flex;align-items:center">
                Edit Quotation
                <span class="changed-badge" id="changedBadge">
                    <i class="ti ti-pencil" style="font-size:11px"></i>
                    Unsaved changes
                </span>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.quotations.show',$quotation->id) }}" class="btn btn-secondary">
                <i class="ti ti-eye" style="font-size:14px"></i> View
            </a>
            <a href="{{ route('tenant.quotations.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('error'))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:#FCEBEB;border:1px solid #F09595;border-radius:8px;margin-bottom:14px;font-size:13px;color:#A32D2D;font-weight:500">
        <i class="ti ti-alert-circle" style="font-size:16px"></i> {{ session('error') }}
    </div>
    @endif

    <form method="POST"
          action="{{ route('tenant.quotations.update', $quotation->id) }}"
          novalidate id="quotationForm">
        @csrf @method('PUT')
        <input type="hidden" name="status" id="statusHidden" value="{{ $currentStatus }}">

        <div class="qf-layout">

            {{-- ── MAIN ── --}}
            <div>

                {{-- Quotation Details --}}
                <div class="qf-card" style="margin-bottom:14px">

                    {{-- Last Updated --}}
                    <div class="lu-bar">
                        <i class="ti ti-clock" style="font-size:14px"></i>
                        Last updated {{ $quotation->updated_at->diffForHumans() }}
                        @if($quotation->createdBy)
                        · Created by <strong style="color:var(--text-200);margin-left:3px">{{ $quotation->createdBy->name }}</strong>
                        @endif
                    </div>

                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:#E6F1FB">
                                <i class="ti ti-file-text" style="font-size:15px;color:#185FA5"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Quotation Details</div>
                                <div class="qf-sec-sub">Date aur validity period</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field">
                                <label class="qf-label">Quotation Number</label>
                                <input type="text" class="qf-input" value="{{ $quotation->number }}" readonly
                                       style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                            </div>
                            <div class="qf-field">
                                <label class="qf-label">Date <span class="qf-req">*</span></label>
                                <input type="date" name="date"
                                       class="qf-input {{ $errors->has('date')?'is-err':'' }}"
                                       value="{{ old('date', $quotation->date?->format('Y-m-d') ?? $quotation->date) }}" required/>
                                @error('date')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label">Valid Until</label>
                                <input type="date" name="valid_until" id="q_valid_until"
                                       class="qf-input {{ $errors->has('valid_until')?'is-err':'' }}"
                                       value="{{ old('valid_until', $quotation->valid_until?->format('Y-m-d') ?? $quotation->valid_until) }}"/>
                                @error('valid_until')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label">Company</label>
                                <input type="text" class="qf-input" value="{{ $tenant->name ?? auth()->user()->name }}"
                                       readonly style="background:var(--bg-elevated);color:var(--text-300);cursor:default"/>
                            </div>
                        </div>
                    </div>

                    {{-- Contact --}}
                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:#EEEDFE">
                                <i class="ti ti-user" style="font-size:15px;color:#534AB7"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Bill To</div>
                                <div class="qf-sec-sub">Contact ya lead select karo</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field">
                                <label class="qf-label">Contact</label>
                                <select name="contact_id" id="q_contact"
                                        class="qf-input qf-sel {{ $errors->has('contact_id')?'is-err':'' }}"
                                        onchange="onContactChange(this)">
                                    <option value="">— Select Contact —</option>
                                    @foreach($contacts as $c)
                                    <option value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-company="{{ $c->company ?? '' }}"
                                        data-phone="{{ $c->phone ?? '' }}"
                                        data-email="{{ $c->email ?? '' }}"
                                        data-address="{{ $c->address ?? '' }}"
                                        data-city="{{ $c->city ?? '' }}"
                                        data-state="{{ $c->state ?? '' }}"
                                        data-gst="{{ $c->gst_number ?? '' }}"
                                        {{ old('contact_id', $quotation->contact_id) == $c->id ? 'selected':'' }}>
                                        {{ $c->name }}@if($c->company) — {{ $c->company }}@endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="qf-field">
                                <label class="qf-label">Linked Lead</label>
                                <select name="lead_id" class="qf-input qf-sel">
                                    <option value="">— None —</option>
                                    @foreach($leads as $l)
                                    <option value="{{ $l->id }}" {{ old('lead_id', $quotation->lead_id) == $l->id ? 'selected':'' }}>
                                        {{ $l->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Contact Preview --}}
                        <div id="contactPreview"
                             style="{{ $quotation->contact ? 'display:block' : 'display:none' }};margin-top:12px;padding:12px 14px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:8px">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                                <div id="cpAvatar" style="width:32px;height:32px;border-radius:50%;background:#E6F1FB;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                                    {{ $quotation->contact ? strtoupper(substr($quotation->contact->name,0,2)) : '' }}
                                </div>
                                <div>
                                    <div id="cpName" style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $quotation->contact?->name }}</div>
                                    <div id="cpCompany" style="font-size:12px;color:var(--text-300)">{{ $quotation->contact?->company }}</div>
                                </div>
                            </div>
                            <div id="cpDetails" style="font-size:12px;color:var(--text-300);line-height:1.6">
                                @if($quotation->contact?->phone)
                                <i class="ti ti-phone" style="font-size:11px"></i> {{ $quotation->contact->phone }}<br>
                                @endif
                                @if($quotation->contact?->email)
                                <i class="ti ti-mail" style="font-size:11px"></i> {{ $quotation->contact->email }}<br>
                                @endif
                                @if($quotation->contact?->city)
                                <i class="ti ti-map-pin" style="font-size:11px"></i>
                                {{ collect([$quotation->contact->city,$quotation->contact->state])->filter()->join(', ') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── LINE ITEMS ── --}}
                <div class="qf-card" style="margin-bottom:14px">
                    <div class="qf-section" style="border-bottom:none;padding-bottom:0">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:#E1F5EE">
                                <i class="ti ti-list-details" style="font-size:15px;color:#0F6E56"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Line Items <span class="qf-req">*</span></div>
                                <div class="qf-sec-sub">Items add, edit ya remove karo</div>
                            </div>
                        </div>
                        <div class="items-table-wrap">
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        @foreach($itemCols as $col)
                                        <th style="width:{{ $col['width'] }}">{{ $col['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="add-item-btn" onclick="addItemRow()">
                            <i class="ti ti-plus" style="font-size:14px"></i> Add Item
                        </button>
                    </div>

                    <div class="totals-wrap">
                        <table class="totals-table">
                            <tr>
                                <td>Subtotal</td>
                                <td id="displaySubtotal">₹{{ number_format($quotation->subtotal ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    Discount
                                    <input type="number" name="discount" id="discountInput"
                                           class="qf-input" style="width:80px;display:inline-block;margin-left:6px;padding:4px 8px;height:28px;font-size:12.5px"
                                           placeholder="0" min="0"
                                           value="{{ old('discount', $quotation->discount ?? 0) }}"
                                           oninput="recalcTotals()"/>
                                </td>
                                <td id="displayDiscount" style="color:#E24B4A">-₹{{ number_format($quotation->discount ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    Tax
                                    <select name="tax_percent" id="taxSelect"
                                            class="qf-input qf-sel"
                                            style="width:110px;display:inline-block;margin-left:6px;height:28px;padding:2px 8px;font-size:12.5px"
                                            onchange="recalcTotals()">
                                        @foreach($taxOptions as $rate => $label)
                                        <option value="{{ $rate }}" {{ old('tax_percent', $quotation->tax_percent ?? 18) == $rate ? 'selected':'' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td id="displayTax" style="color:#1D9E75">+₹{{ number_format($quotation->tax_amount ?? 0, 2) }}</td>
                            </tr>
                            <tr class="grand-total">
                                <td><strong>Total</strong></td>
                                <td id="displayTotal"><strong>₹{{ number_format($quotation->total ?? 0, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>

                    <input type="hidden" name="subtotal"   id="hiddenSubtotal"  value="{{ $quotation->subtotal ?? 0 }}">
                    <input type="hidden" name="tax_amount" id="hiddenTaxAmount" value="{{ $quotation->tax_amount ?? 0 }}">
                    <input type="hidden" name="total"      id="hiddenTotal"     value="{{ $quotation->total ?? 0 }}">
                </div>

                {{-- Notes & Terms --}}
                <div class="qf-card">
                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:#FAEEDA">
                                <i class="ti ti-notes" style="font-size:15px;color:#BA7517"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Notes & Terms</div>
                                <div class="qf-sec-sub">Quotation mein print hone wali details</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field span-full">
                                <label class="qf-label">Notes</label>
                                <textarea name="notes" class="qf-input qf-area" rows="3"
                                          placeholder="Thank you message ya special instructions...">{{ old('notes', $quotation->notes) }}</textarea>
                            </div>
                            <div class="qf-field span-full">
                                <label class="qf-label">Terms & Conditions</label>
                                <textarea name="terms" class="qf-input qf-area" rows="5"
                                          placeholder="Payment terms, delivery conditions...">{{ old('terms', $quotation->terms) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="qf-footer">
                        <div class="qf-footer-note">Fields marked <strong>*</strong> are required</div>
                        <div style="display:flex;gap:8px">
                            <a href="{{ route('tenant.quotations.show',$quotation->id) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="ti ti-device-floppy" id="submitIcon" style="font-size:14px"></i>
                                <span id="submitText">Save Changes</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SIDEBAR ── --}}
            <div class="qf-sidebar">

                {{-- Quotation Meta --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">Quotation Info</div>
                    <div style="font-size:18px;font-weight:600;color:var(--text-100);font-family:'DM Mono',monospace">{{ $quotation->number }}</div>
                    <div style="font-size:28px;font-weight:600;color:#185FA5;font-family:'DM Mono',monospace;letter-spacing:-1px;margin-top:6px" id="sidebarTotal">
                        ₹{{ number_format($quotation->total ?? 0, 2) }}
                    </div>
                    <div style="height:1px;background:var(--border-subtle);margin:12px 0"></div>
                    <div style="display:flex;flex-direction:column;gap:5px;font-size:12px;color:var(--text-300)">
                        <div>Created: <strong style="color:var(--text-200)">{{ $quotation->created_at->format('M d, Y') }}</strong></div>
                        <div>Updated: <strong style="color:var(--text-200)">{{ $quotation->updated_at->diffForHumans() }}</strong></div>
                        @if($quotation->invoice)
                        <div style="margin-top:4px">
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;background:#E1F5EE;color:#0F6E56;font-size:11.5px;font-weight:600">
                                <i class="ti ti-receipt" style="font-size:11px"></i>
                                Invoice Exists
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">Status</div>
                    <div class="status-pills" id="statusPills">
                        @foreach($statuses as $slug => $st)
                        <button type="button"
                                class="sp-opt {{ $currentStatus===$slug?'active':'' }}"
                                data-status="{{ $slug }}"
                                style="{{ $currentStatus===$slug ? 'background:'.$st['bg'].';border-color:'.$st['color'].';color:'.$st['text_color'] : '' }}"
                                onclick="setStatus('{{ $slug }}', this)">
                            <span class="sp-dot" style="background:{{ $st['color'] }}"></span>
                            {{ $st['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">Quick Actions</div>
                    <div style="display:flex;flex-direction:column;gap:7px">
                        <a href="{{ route('tenant.quotations.pdf',$quotation->id) }}"
                           target="_blank" class="qa-btn">
                            <div class="qa-icon" style="background:#FAEEDA">
                                <i class="ti ti-file-download" style="font-size:14px;color:#BA7517"></i>
                            </div>
                            Download PDF
                        </a>
                        @if($quotation->status !== 'sent')
                        {{-- <form method="POST" action="{{ route('tenant.quotations.send',$quotation->id) }}"> --}}
                            <form method="POST" action="#"></form>
                            @csrf
                            <button type="submit" class="qa-btn" style="width:100%">
                                <div class="qa-icon" style="background:#E6F1FB">
                                    <i class="ti ti-send" style="font-size:14px;color:#185FA5"></i>
                                </div>
                                Send to Client
                            </button>
                        </form>
                        @endif
                        @if($quotation->status === 'accepted' && !$quotation->invoice)
                        <form method="POST" action="{{ route('tenant.quotations.convert',$quotation->id) }}">
                            @csrf
                            <button type="submit" class="qa-btn" style="width:100%;background:#E1F5EE;border-color:#9FE1CB;color:#0F6E56">
                                <div class="qa-icon" style="background:#E1F5EE">
                                    <i class="ti ti-receipt" style="font-size:14px;color:#0F6E56"></i>
                                </div>
                                Convert to Invoice
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Danger Zone --}}
                <div class="qf-sc" style="border-color:#F09595">
                    <div class="qf-sc-title" style="color:#A32D2D">Danger Zone</div>
                    <div style="font-size:12px;color:var(--text-300);margin-bottom:12px;line-height:1.5">
                        Delete karne ke baad yeh quotation permanently remove ho jayega.
                    </div>
                    <form method="POST" action="{{ route('tenant.quotations.destroy',$quotation->id) }}"
                          onsubmit="return confirm('Delete quotation {{ $quotation->number }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn" style="width:100%;justify-content:center;background:#FCEBEB;border-color:#F09595;color:#A32D2D;font-size:12.5px">
                            <i class="ti ti-trash" style="font-size:14px"></i> Delete Quotation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){

const STATUSES = @json(config('quotation.statuses'));
const PRODUCTS = @json($products->keyBy('id'));
/* ── Item Row ── */
let rowIndex = 0;

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function productOptions() {
    let opts = '<option value="">— Select Product —</option>';
    Object.values(PRODUCTS).forEach(p => {
        opts += `<option value="${p.id}">${esc(p.name)}${p.unit ? ' ('+esc(p.unit)+')' : ''}</option>`;
    });
    return opts;
}

function fillFromProduct(selectEl, i) {
    const pid = selectEl.value;
    if (!pid || !PRODUCTS[pid]) return;
    const p = PRODUCTS[pid];
    const row = document.getElementById('row_' + i);
    if (!row) return;
    row.querySelector(`[name="items[${i}][name]"]`).value        = p.name;
    row.querySelector(`[name="items[${i}][description]"]`).value = p.description || '';
    row.querySelector(`[name="items[${i}][rate]"]`).value        = p.rate;
    const taxHid = row.querySelector(`[name="items[${i}][tax_percent]"]`);
    if (taxHid) taxHid.value = p.tax_percent;
    const taxSel = document.getElementById('taxSelect');
    if (taxSel) taxSel.value = p.tax_percent;
    calcRowAmount(i);
    markDirty();
}

function addItemRow(name='', desc='', qty=1, rate=0, taxPct=''){
    const i    = rowIndex++;
    const amt  = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const gst  = taxPct !== '' ? taxPct : (document.getElementById('taxSelect')?.value || 18);
    const tbody = document.getElementById('itemsBody');
    const tr   = document.createElement('tr');
    tr.id      = 'row_' + i;
    tr.innerHTML = `
        <td>
            <select class="item-input" style="margin-bottom:4px;font-size:12px;color:var(--text-300)" onchange="fillFromProduct(this,${i})">${productOptions()}</select>
            <input type="text" name="items[${i}][name]" class="item-input" placeholder="Item / Service name" value="${esc(name)}" required/>
        </td>
        <td><input type="text" name="items[${i}][description]" class="item-input" placeholder="Optional description" value="${esc(desc)}"/></td>
        <td><input type="number" name="items[${i}][quantity]" class="item-input" placeholder="1" value="${qty}" min="0.01" step="0.01" required oninput="calcRowAmount(${i})"/></td>
        <td><input type="number" name="items[${i}][rate]" class="item-input" placeholder="0.00" value="${rate}" min="0" step="0.01" required oninput="calcRowAmount(${i})"/></td>
        <td>
            <input type="hidden" name="items[${i}][tax_percent]" value="${gst}"/>
            <input type="number" name="items[${i}][amount]" class="item-input item-amount-input" id="amt_${i}" value="${amt.toFixed(2)}" readonly/>
        </td>
        <td><button type="button" class="del-row-btn" onclick="delRow(${i})" title="Remove"><i class="ti ti-trash" style="font-size:13px"></i></button></td>
    `;
    tbody.appendChild(tr);
    recalcTotals();
    markDirty();
}
window.addItemRow = addItemRow;

window.calcRowAmount = function(i){
    const qty  = parseFloat(document.querySelector(`[name="items[${i}][quantity]"]`)?.value)||0;
    const rate = parseFloat(document.querySelector(`[name="items[${i}][rate]"]`)?.value)||0;
    const el   = document.getElementById('amt_' + i);
    if(el){ el.value = (qty*rate).toFixed(2); document.querySelector(`[name="items[${i}][amount]"]`).value=(qty*rate).toFixed(2); }
    recalcTotals();
};

window.delRow = function(i){
    document.getElementById('row_'+i)?.remove();
    recalcTotals(); markDirty();
};

window.recalcTotals = function(){
    let sub = 0;
    document.querySelectorAll('[name$="[amount]"]').forEach(el => sub += parseFloat(el.value)||0);
    const disc    = parseFloat(document.getElementById('discountInput')?.value)||0;
    const taxPct  = parseFloat(document.getElementById('taxSelect')?.value)||0;
    const taxable = Math.max(0, sub-disc);
    const taxAmt  = (taxable*taxPct)/100;
    const total   = taxable + taxAmt;
    const fmt = n => '₹'+n.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
    document.getElementById('displaySubtotal').textContent = fmt(sub);
    document.getElementById('displayDiscount').textContent = '-'+fmt(disc);
    document.getElementById('displayTax').textContent      = '+'+fmt(taxAmt);
    document.getElementById('displayTotal').innerHTML      = '<strong>'+fmt(total)+'</strong>';
    document.getElementById('hiddenSubtotal').value   = sub.toFixed(2);
    document.getElementById('hiddenTaxAmount').value  = taxAmt.toFixed(2);
    document.getElementById('hiddenTotal').value      = total.toFixed(2);
    document.getElementById('sidebarTotal').textContent = fmt(total);
};

/* ── Contact change ── */
window.onContactChange = function(sel){
    const opt = sel.options[sel.selectedIndex];
    if(!opt.value){ document.getElementById('contactPreview').style.display='none'; return; }
    const init = (opt.dataset.name||'').split(' ').map(w=>w[0]?.toUpperCase()||'').join('').slice(0,2);
    document.getElementById('cpAvatar').textContent  = init;
    document.getElementById('cpName').textContent    = opt.dataset.name||'';
    document.getElementById('cpCompany').textContent = opt.dataset.company||'';
    let d=[];
    if(opt.dataset.phone)   d.push('<i class="ti ti-phone" style="font-size:11px"></i> '+opt.dataset.phone);
    if(opt.dataset.email)   d.push('<i class="ti ti-mail" style="font-size:11px"></i> '+opt.dataset.email);
    if(opt.dataset.city||opt.dataset.state) d.push('<i class="ti ti-map-pin" style="font-size:11px"></i> '+[opt.dataset.city,opt.dataset.state].filter(Boolean).join(', '));
    document.getElementById('cpDetails').innerHTML = d.join('<br>');
    document.getElementById('contactPreview').style.display = 'block';
};

/* ── Status Pills ── */
window.setStatus = function(slug, el){
    document.querySelectorAll('.sp-opt').forEach(b => { b.classList.remove('active'); b.style.background=b.style.borderColor=b.style.color=''; });
    el.classList.add('active');
    const st = STATUSES[slug];
    if(st){ el.style.background=st.bg; el.style.borderColor=st.color; el.style.color=st.text_color; }
    document.getElementById('statusHidden').value = slug;
    markDirty();
};

/* ── Unsaved changes ── */
const form   = document.getElementById('quotationForm');
const badge  = document.getElementById('changedBadge');
let isDirty  = false;
function markDirty(){ isDirty=true; badge.classList.add('show'); }

form.querySelectorAll('input:not([readonly]),select,textarea').forEach(el => {
    el.addEventListener('input',  markDirty);
    el.addEventListener('change', markDirty);
});
window.addEventListener('beforeunload', e => {
    if(isDirty && !form.dataset.submitting){ e.preventDefault(); e.returnValue=''; }
});
form.addEventListener('submit', function(){
    this.dataset.submitting='1';
    const icon=document.getElementById('submitIcon');
    const text=document.getElementById('submitText');
    icon.style.animation='qf-spin .7s linear infinite';
    text.textContent='Saving...';
    document.getElementById('submitBtn').disabled=true;
});

/* ── Load existing items ── */
const items = @json($existingItems);
if(items && items.length){
    items.forEach(item => {
        addItemRow(item.name||'', item.description||'', item.quantity||1, item.rate||0, item.tax_percent||'');
    });
} else {
    addItemRow();
}
isDirty = false; badge.classList.remove('show'); /* reset after load */

/* ── Trigger contact preview if pre-selected ── */
@if($quotation->contact_id)
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('q_contact');
    if(sel && sel.value) onContactChange(sel);
});
@endif

})();
</script>
@endpush
@extends('layouts.app')
@section('title', 'New Quotation — ' . $number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.qf { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Layout ── */
.qf-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 280px;
    gap: 16px;
    margin-top: 20px;
}
@media(max-width:960px){ .qf-layout { grid-template-columns:1fr; } }

/* ── Cards ── */
.qf-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px; overflow: hidden;
}

/* ── Section ── */
.qf-section { padding: 20px 22px; border-bottom: 1px solid var(--border-subtle); }
.qf-section:last-of-type { border-bottom: none; }
.qf-sec-head { display: flex; align-items: flex-start; gap: 11px; margin-bottom: 16px; }
.qf-sec-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.qf-sec-title { font-size: 13px; font-weight: 600; color: var(--text-100); }
.qf-sec-sub   { font-size: 12px; color: var(--text-300); margin-top: 1px; }

/* ── Grid ── */
.qf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.qf-grid .span-full { grid-column: 1/-1; }
@media(max-width:640px){ .qf-grid { grid-template-columns:1fr; } .qf-grid .span-full { grid-column:1; } }

/* ── Fields ── */
.qf-field { display: flex; flex-direction: column; gap: 5px; }
.qf-label { font-size: 11.5px; font-weight: 600; color: var(--text-200); text-transform: uppercase; letter-spacing: .5px; }
.qf-req   { color: var(--red,#E24B4A); margin-left: 2px; }
.qf-hint  { font-size: 12px; color: var(--text-400); }
.qf-err   { font-size: 12px; color: var(--red,#E24B4A); font-weight: 500; }

.qf-input {
    width: 100%; padding: 9px 12px;
    background: var(--bg-input); border: 1.5px solid var(--border-default);
    border-radius: 8px; color: var(--text-100);
    font-family: 'DM Sans', var(--font), sans-serif; font-size: 13.5px; outline: none;
    transition: border-color .15s, box-shadow .15s, background .15s; -webkit-appearance: none;
}
.qf-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: var(--bg-surface); }
.qf-input::placeholder { color: var(--text-400); font-size: 13px; }
.qf-input.is-err { border-color: var(--red,#E24B4A); }
.qf-sel  { cursor: pointer; }
.qf-area { resize: vertical; min-height: 80px; line-height: 1.55; }

/* ── Contact prefill banner ── */
.qf-prefill-bar {
    display: none; align-items: center; gap: 9px;
    padding: 10px 14px; border-radius: 8px; margin-bottom: 14px;
    background: #E1F5EE; border: 1px solid #9FE1CB;
    font-size: 12.5px; color: #0F6E56; font-weight: 500;
}
.qf-prefill-bar.show { display: flex; }

/* ── Items Table ── */
.items-table-wrap { overflow-x: auto; }
.items-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; min-width: 620px;
}
.items-table thead tr { background: var(--bg-elevated); }
.items-table th {
    padding: 8px 10px; text-align: left;
    font-size: 11px; font-weight: 600; color: var(--text-300);
    text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid var(--border-subtle);
}
.items-table td {
    padding: 7px 6px;
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: top;
}
.items-table tr:last-child td { border-bottom: none; }
.items-table tr:hover td { background: var(--bg-elevated); }

.item-input {
    width: 100%; padding: 7px 9px;
    background: var(--bg-input); border: 1.5px solid var(--border-default);
    border-radius: 7px; color: var(--text-100);
    font-family: 'DM Sans', var(--font), sans-serif; font-size: 13px; outline: none;
    transition: border-color .15s; -webkit-appearance: none;
}
.item-input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-dim); }
.item-input.is-err { border-color: var(--red,#E24B4A); }
.item-amount-input {
    font-family: 'DM Mono', monospace; font-weight: 600;
    background: var(--bg-elevated); color: var(--text-100);
    border-color: var(--border-subtle); cursor: default;
}

.del-row-btn {
    width: 28px; height: 28px; border-radius: 6px;
    background: transparent; border: 1px solid var(--border-subtle);
    cursor: pointer; color: var(--text-400);
    display: flex; align-items: center; justify-content: center;
    transition: all .15s; margin: 2px auto 0;
}
.del-row-btn:hover { background: #FCEBEB; border-color: #F09595; color: #A32D2D; }

.add-item-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 9px 16px; margin: 12px 0 0;
    background: transparent; border: 1.5px dashed var(--border-default);
    border-radius: 8px; font-size: 13px; color: var(--text-300);
    cursor: pointer; font-family: 'DM Sans', var(--font), sans-serif;
    transition: all .15s;
}
.add-item-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(55,138,221,.04); }

/* ── Totals ── */
.totals-wrap {
    display: flex; justify-content: flex-end;
    padding: 16px 22px; border-top: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}
.totals-table { width: 280px; }
.totals-table tr td { padding: 5px 0; font-size: 13px; color: var(--text-200); }
.totals-table tr td:last-child { text-align: right; font-family: 'DM Mono', monospace; font-weight: 500; color: var(--text-100); }
.totals-table .grand-total td { padding-top: 10px; font-size: 15px; font-weight: 600; color: var(--text-100); border-top: 1px solid var(--border-default); }
.totals-table .grand-total td:last-child { color: #185FA5; font-size: 16px; }

/* ── Footer ── */
.qf-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 15px 22px; background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
}
.qf-footer-note { font-size: 12px; color: var(--text-300); }
.qf-footer-note strong { color: var(--text-200); }

/* ── Sidebar ── */
.qf-sidebar { display: flex; flex-direction: column; gap: 13px; }
.qf-sc { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: 14px; padding: 17px; }
.qf-sc-title { font-size: 11px; font-weight: 600; color: var(--text-300); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 13px; }

/* Quote Preview */
.qp-number { font-size: 18px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; letter-spacing: -.5px; }
.qp-total  { font-size: 28px; font-weight: 600; color: #185FA5; font-family: 'DM Mono', monospace; letter-spacing: -1px; margin-top: 8px; }
.qp-items-count { font-size: 12px; color: var(--text-300); margin-top: 3px; }

/* Status Pills */
.status-pills { display: flex; flex-direction: column; gap: 6px; }
.sp-opt {
    display: flex; align-items: center; gap: 9px;
    padding: 8px 11px; border-radius: 8px;
    border: 1.5px solid var(--border-default);
    cursor: pointer; transition: all .15s; font-size: 12.5px; font-weight: 500;
    background: var(--bg-input); color: var(--text-200);
    font-family: 'DM Sans', var(--font), sans-serif;
}
.sp-opt:hover { border-color: var(--border-strong); color: var(--text-100); }
.sp-opt.active { font-weight: 600; }
.sp-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* Tips */
.tip-list { display: flex; flex-direction: column; gap: 9px; }
.tip-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: var(--text-300); line-height: 1.45; }
.tip-dot  { width: 5px; height: 5px; border-radius: 50%; background: var(--accent,#378ADD); margin-top: 5px; flex-shrink: 0; }

@keyframes qf-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $qConfig    = config('quotation');
    $statuses   = $qConfig['statuses'];
    $taxOptions = $qConfig['tax_options'];
    $itemCols   = $qConfig['item_columns'];
    $defTerms   = $qConfig['default_terms'];
    $defNotes   = $qConfig['default_notes'];

    $currentStatus = old('status', 'draft');

    $secColors = [
        'blue'   => ['bg'=>'#E6F1FB','ic'=>'#185FA5'],
        'purple' => ['bg'=>'#EEEDFE','ic'=>'#534AB7'],
        'teal'   => ['bg'=>'#E1F5EE','ic'=>'#0F6E56'],
        'amber'  => ['bg'=>'#FAEEDA','ic'=>'#BA7517'],
        'green'  => ['bg'=>'#EAF3DE','ic'=>'#3B6D11'],
    ];
@endphp

<div class="qf">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.quotations.index') }}" style="color:var(--text-300);text-decoration:none">Quotations</a>
                <span style="opacity:.4">›</span>
                <span>New Quotation</span>
            </div>
            <div class="page-title">New Quotation</div>
        </div>
        <a href="{{ route('tenant.quotations.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    {{-- Prefill Notice --}}
    @if($contact)
    <div class="qf-prefill-bar show">
        <i class="ti ti-bolt" style="font-size:15px"></i>
        Contact <strong>{{ $contact->name }}</strong> pre-linked — details auto-filled
    </div>
    @elseif($lead)
    <div class="qf-prefill-bar show">
        <i class="ti ti-target" style="font-size:15px"></i>
        Lead <strong>{{ $lead->name }}</strong> se quotation create ho raha hai
    </div>
    @endif

    <form method="POST" action="{{ route('tenant.quotations.store') }}"
          novalidate id="quotationForm">
        @csrf
        <input type="hidden" name="status" id="statusHidden" value="{{ $currentStatus }}">

        <div class="qf-layout">

            {{-- ── MAIN ── --}}
            <div>

                {{-- Quotation Info --}}
                <div class="qf-card" style="margin-bottom:14px">

                    {{-- Header Info --}}
                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:#E6F1FB">
                                <i class="ti ti-file-text" style="font-size:15px;color:#185FA5"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Quotation Details</div>
                                <div class="qf-sec-sub">Quotation number, date aur validity</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field">
                                <label class="qf-label">Quotation Number</label>
                                <input type="text" class="qf-input" value="{{ $number }}" readonly
                                       style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                                <span class="qf-hint">Auto-generated</span>
                            </div>
                            <div class="qf-field">
                                <label class="qf-label" for="q_date">Date <span class="qf-req">*</span></label>
                                <input type="date" name="date" id="q_date"
                                       class="qf-input {{ $errors->has('date')?'is-err':'' }}"
                                       value="{{ old('date', now()->format('Y-m-d')) }}" required/>
                                @error('date')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label" for="q_valid_until">Valid Until</label>
                                <input type="date" name="valid_until" id="q_valid_until"
                                       class="qf-input {{ $errors->has('valid_until')?'is-err':'' }}"
                                       value="{{ old('valid_until', now()->addDays(30)->format('Y-m-d')) }}"/>
                                @error('valid_until')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label">Tenant / Company</label>
                                <input type="text" class="qf-input" value="{{ $tenant->name ?? auth()->user()->name }}"
                                       readonly style="background:var(--bg-elevated);color:var(--text-300);cursor:default"/>
                            </div>
                        </div>
                    </div>

                    {{-- Contact / Lead --}}
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
                                <label class="qf-label" for="q_contact">Contact</label>
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
                                        {{ old('contact_id', $contact?->id) == $c->id ? 'selected':'' }}>
                                        {{ $c->name }}@if($c->company) — {{ $c->company }}@endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('contact_id')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label" for="q_lead">Linked Lead</label>
                                <select name="lead_id" id="q_lead"
                                        class="qf-input qf-sel {{ $errors->has('lead_id')?'is-err':'' }}">
                                    <option value="">— Select Lead (optional) —</option>
                                    @foreach($leads as $l)
                                    <option value="{{ $l->id }}" {{ old('lead_id', $lead?->id) == $l->id ? 'selected':'' }}>
                                        {{ $l->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Contact Address Preview --}}
                        <div id="contactPreview" style="display:none;margin-top:12px;padding:12px 14px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:8px">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                                <div id="cpAvatar" style="width:32px;height:32px;border-radius:50%;background:#E6F1FB;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0"></div>
                                <div>
                                    <div id="cpName" style="font-size:13.5px;font-weight:600;color:var(--text-100)"></div>
                                    <div id="cpCompany" style="font-size:12px;color:var(--text-300)"></div>
                                </div>
                            </div>
                            <div id="cpDetails" style="font-size:12px;color:var(--text-300);line-height:1.6"></div>
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
                                <div class="qf-sec-sub">Products ya services add karo</div>
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
                                <tbody id="itemsBody">
                                    {{-- JS se rows add hongi --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="add-item-btn" onclick="addItemRow()">
                            <i class="ti ti-plus" style="font-size:14px"></i>
                            Add Item
                        </button>
                    </div>

                    {{-- Totals --}}
                    <div class="totals-wrap">
                        <table class="totals-table">
                            <tr>
                                <td>Subtotal</td>
                                <td id="displaySubtotal">₹0.00</td>
                            </tr>
                            <tr>
                                <td>
                                    Discount
                                    <input type="number" name="discount" id="discountInput"
                                           class="qf-input" style="width:80px;display:inline-block;margin-left:6px;padding:4px 8px;height:28px;font-size:12.5px"
                                           placeholder="0" min="0" value="{{ old('discount', 0) }}"
                                           oninput="recalcTotals()"/>
                                    <input type="hidden" name="discount" id="discountHidden" value="{{ old('discount',0) }}">
                                </td>
                                <td id="displayDiscount" style="color:#E24B4A">-₹0.00</td>
                            </tr>
                            <tr>
                                <td>
                                    Tax
                                    <select name="tax_percent" id="taxSelect"
                                            class="qf-input qf-sel"
                                            style="width:110px;display:inline-block;margin-left:6px;height:28px;padding:2px 8px;font-size:12.5px"
                                            onchange="recalcTotals()">
                                        @foreach($taxOptions as $rate => $label)
                                        <option value="{{ $rate }}" {{ old('tax_percent', 18) == $rate ? 'selected':'' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td id="displayTax" style="color:#1D9E75">+₹0.00</td>
                            </tr>
                            <tr class="grand-total">
                                <td><strong>Total</strong></td>
                                <td id="displayTotal"><strong>₹0.00</strong></td>
                            </tr>
                        </table>
                    </div>

                    {{-- Hidden totals for submission --}}
                    <input type="hidden" name="subtotal"   id="hiddenSubtotal">
                    <input type="hidden" name="tax_amount" id="hiddenTaxAmount">
                    <input type="hidden" name="total"      id="hiddenTotal">
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
                                <div class="qf-sec-sub">Additional details jo quotation mein dikhenge</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field span-full">
                                <label class="qf-label">Notes</label>
                                <textarea name="notes" class="qf-input qf-area"
                                          placeholder="Thank you message ya special instructions..."
                                          rows="3">{{ old('notes', $defNotes) }}</textarea>
                            </div>
                            <div class="qf-field span-full">
                                <label class="qf-label">Terms & Conditions</label>
                                <textarea name="terms" class="qf-input qf-area"
                                          placeholder="Payment terms, delivery conditions..."
                                          rows="5">{{ old('terms', $defTerms) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="qf-footer">
                        <div class="qf-footer-note">Fields marked <strong>*</strong> are required</div>
                        <div style="display:flex;gap:8px">
                            <a href="{{ route('tenant.quotations.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="_action" value="draft" class="btn btn-secondary"
                                    onclick="document.getElementById('statusHidden').value='draft'">
                                <i class="ti ti-file" style="font-size:14px"></i>
                                Save Draft
                            </button>
                            <button type="submit" name="_action" value="sent" class="btn btn-primary" id="submitBtn"
                                    onclick="document.getElementById('statusHidden').value='sent'">
                                <i class="ti ti-send" id="submitIcon" style="font-size:14px"></i>
                                <span id="submitText">Create & Send</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SIDEBAR ── --}}
            <div class="qf-sidebar">

                {{-- Live Preview --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">Quotation Preview</div>
                    <div class="qp-number">{{ $number }}</div>
                    <div class="qp-total" id="sidebarTotal">₹0.00</div>
                    <div class="qp-items-count" id="sidebarItemsCount">0 items</div>
                    <div style="height:1px;background:var(--border-subtle);margin:12px 0"></div>
                    <div style="font-size:12px;color:var(--text-300);display:flex;flex-direction:column;gap:5px" id="sidebarDates">
                        <div>Date: <strong style="color:var(--text-200)">{{ now()->format('M d, Y') }}</strong></div>
                        <div>Valid: <strong style="color:var(--text-200)" id="sidebarValidUntil">{{ now()->addDays(30)->format('M d, Y') }}</strong></div>
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

                {{-- Company Info --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">From</div>
                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">{{ $tenant->name ?? auth()->user()->name }}</div>
                    @if(isset($tenant->phone))
                    <div style="font-size:12px;color:var(--text-300);margin-top:4px">{{ $tenant->phone }}</div>
                    @endif
                    @if(isset($tenant->email))
                    <div style="font-size:12px;color:var(--text-300)">{{ $tenant->email }}</div>
                    @endif
                </div>

                {{-- Tips --}}
                <div class="qf-sc">
                    <div class="qf-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Contact select karne par billing address auto-fill hoga</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Save Draft karo — baad mein edit kar sakte ho</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Accepted quotation ko Invoice mein convert kar sakte ho</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Tax aur discount totals mein automatically calculate hoge</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){

/* ── Config from PHP ── */
const STATUSES = @json(config('quotation.statuses'));
const PRODUCTS = @json($products->keyBy('id'));
/* ── Item Row Template ── */
let rowIndex = 0;

function productOptions() {
    let opts = '<option value="">— Select Product —</option>';
    Object.values(PRODUCTS).forEach(p => {
        opts += `<option value="${p.id}">${escHtml(p.name)}${p.unit ? ' ('+escHtml(p.unit)+')' : ''}</option>`;
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
    row.querySelector(`[name="items[${i}][tax_percent]"]`).value = p.tax_percent;
    // Also update the tax select to match product's GST
    const taxSel = document.getElementById('taxSelect');
    if (taxSel) { taxSel.value = p.tax_percent; }
    calcRowAmount(i);
}

function addItemRow(name='', desc='', qty=1, rate=0, taxPct=''){
    const i    = rowIndex++;
    const amt  = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const gst  = taxPct !== '' ? taxPct : (document.getElementById('taxSelect')?.value || 18);
    const tbody = document.getElementById('itemsBody');
    const tr   = document.createElement('tr');
    tr.id      = 'row_' + i;
    tr.innerHTML = `
        <td colspan="2" style="padding-bottom:0">
            <select class="item-input" style="margin-bottom:4px;font-size:12px;color:var(--text-300)"
                    onchange="fillFromProduct(this, ${i})">
                ${productOptions()}
            </select>
        </td>
        <td colspan="4" style="display:none"></td>
    `;
    tbody.appendChild(tr);

    // Replace with proper row
    tr.innerHTML = `
        <td>
            <select class="item-input" style="margin-bottom:4px;font-size:12px;color:var(--text-300)"
                    onchange="fillFromProduct(this, ${i})">
                ${productOptions()}
            </select>
            <input type="text" name="items[${i}][name]"
                   class="item-input {{ $errors->has("items.*.name")?"is-err":"" }}"
                   placeholder="Item / Service name" value="${escHtml(name)}" required/>
        </td>
        <td>
            <input type="text" name="items[${i}][description]"
                   class="item-input"
                   placeholder="Optional description" value="${escHtml(desc)}"/>
        </td>
        <td>
            <input type="number" name="items[${i}][quantity]"
                   class="item-input" placeholder="1"
                   value="${qty}" min="0.01" step="0.01" required
                   oninput="calcRowAmount(${i})"/>
        </td>
        <td>
            <input type="number" name="items[${i}][rate]"
                   class="item-input" placeholder="0.00"
                   value="${rate}" min="0" step="0.01" required
                   oninput="calcRowAmount(${i})"/>
        </td>
        <td>
            <input type="hidden" name="items[${i}][tax_percent]" value="${gst}"/>
            <input type="number" name="items[${i}][amount]"
                   class="item-input item-amount-input"
                   id="amt_${i}"
                   value="${amt.toFixed(2)}" readonly/>
        </td>
        <td>
            <button type="button" class="del-row-btn" onclick="delRow(${i})" title="Remove">
                <i class="ti ti-trash" style="font-size:13px"></i>
            </button>
        </td>
    `;
    recalcTotals();
}
window.addItemRow = addItemRow;

function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

window.calcRowAmount = function(i){
    const qty = parseFloat(document.querySelector(`[name="items[${i}][quantity]"]`)?.value) || 0;
    const rate= parseFloat(document.querySelector(`[name="items[${i}][rate]"]`)?.value)     || 0;
    const amt = qty * rate;
    const el  = document.getElementById('amt_' + i);
    if(el){
        el.value = amt.toFixed(2);
        document.querySelector(`[name="items[${i}][amount]"]`).value = amt.toFixed(2);
    }
    recalcTotals();
};

window.delRow = function(i){
    const row = document.getElementById('row_' + i);
    if(row) row.remove();
    recalcTotals();
};

/* ── Recalculate totals ── */
window.recalcTotals = function(){
    const amtInputs = document.querySelectorAll('[name$="[amount]"]');
    let subtotal = 0;
    amtInputs.forEach(el => subtotal += parseFloat(el.value)||0);

    const discount   = parseFloat(document.getElementById('discountInput')?.value) || 0;
    const taxPercent = parseFloat(document.getElementById('taxSelect')?.value)      || 0;
    const taxable    = Math.max(0, subtotal - discount);
    const taxAmt     = (taxable * taxPercent) / 100;
    const total      = taxable + taxAmt;

    const fmt = n => '₹' + n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});

    document.getElementById('displaySubtotal').textContent = fmt(subtotal);
    document.getElementById('displayDiscount').textContent = '-' + fmt(discount);
    document.getElementById('displayTax').textContent      = '+' + fmt(taxAmt);
    document.getElementById('displayTotal').innerHTML      = '<strong>' + fmt(total) + '</strong>';

    document.getElementById('hiddenSubtotal').value   = subtotal.toFixed(2);
    document.getElementById('hiddenTaxAmount').value  = taxAmt.toFixed(2);
    document.getElementById('hiddenTotal').value      = total.toFixed(2);
    document.getElementById('discountHidden').value   = discount.toFixed(2);

    /* Sidebar */
    document.getElementById('sidebarTotal').textContent     = fmt(total);
    const rows = document.querySelectorAll('#itemsBody tr');
    document.getElementById('sidebarItemsCount').textContent = rows.length + (rows.length===1?' item':' items');
};

/* ── Contact prefill ── */
window.onContactChange = function(sel){
    const opt = sel.options[sel.selectedIndex];
    if(!opt.value){ hideContactPreview(); return; }

    const name    = opt.dataset.name    || '';
    const company = opt.dataset.company || '';
    const phone   = opt.dataset.phone   || '';
    const email   = opt.dataset.email   || '';
    const address = opt.dataset.address || '';
    const city    = opt.dataset.city    || '';
    const state   = opt.dataset.state   || '';
    const gst     = opt.dataset.gst     || '';

    const init = name.split(' ').map(w=>w[0]?.toUpperCase()||'').join('').slice(0,2);
    document.getElementById('cpAvatar').textContent  = init;
    document.getElementById('cpName').textContent    = name;
    document.getElementById('cpCompany').textContent = company;

    let details = [];
    if(phone)   details.push('<i class="ti ti-phone" style="font-size:11px"></i> ' + phone);
    if(email)   details.push('<i class="ti ti-mail"  style="font-size:11px"></i> ' + email);
    if(address) details.push('<i class="ti ti-home"  style="font-size:11px"></i> ' + address);
    if(city||state) details.push('<i class="ti ti-map-pin" style="font-size:11px"></i> ' + [city,state].filter(Boolean).join(', '));
    if(gst)     details.push('<i class="ti ti-receipt-tax" style="font-size:11px"></i> GST: ' + gst);

    document.getElementById('cpDetails').innerHTML = details.join('<br>');
    document.getElementById('contactPreview').style.display = 'block';
};

function hideContactPreview(){
    document.getElementById('contactPreview').style.display = 'none';
}

/* ── Status pills ── */
window.setStatus = function(slug, el){
    document.querySelectorAll('.sp-opt').forEach(b => {
        b.classList.remove('active');
        b.style.background  = '';
        b.style.borderColor = '';
        b.style.color       = '';
    });
    el.classList.add('active');
    const st = STATUSES[slug];
    if(st){
        el.style.background  = st.bg;
        el.style.borderColor = st.color;
        el.style.color       = st.text_color;
    }
    document.getElementById('statusHidden').value = slug;
};

/* ── Valid until → sidebar ── */
const validUntilInput = document.getElementById('q_valid_until');
if(validUntilInput){
    validUntilInput.addEventListener('change', function(){
        const d = new Date(this.value);
        if(!isNaN(d)){
            document.getElementById('sidebarValidUntil').textContent =
                d.toLocaleDateString('en-IN',{month:'short',day:'numeric',year:'numeric'});
        }
    });
}

/* ── Submit loading ── */
document.getElementById('quotationForm').addEventListener('submit', function(){
    const icon = document.getElementById('submitIcon');
    const text = document.getElementById('submitText');
    if(icon) icon.style.animation = 'qf-spin .7s linear infinite';
    if(text) text.textContent = 'Saving...';
    document.getElementById('submitBtn').disabled = true;
});

/* ── Restore old items on validation failure ── */
@if(old('items'))
const oldItems = @json(old('items'));
if(oldItems && oldItems.length){
    oldItems.forEach(item => {
        addItemRow(item.name||'', item.description||'', item.quantity||1, item.rate||0, item.tax_percent||'');
    });
}
@else
/* Add one blank row on fresh load */
addItemRow();
@endif

/* ── Trigger contact prefill if pre-selected ── */
@if($contact)
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('q_contact');
    if(sel) onContactChange(sel);
});
@endif

recalcTotals();

})();
</script>
@endpush
@php
    $v = $vendor ?? null;
    $val = fn($key) => old($key, $v?->{$key});
@endphp

<div class="vf-main">
    <div class="vf-section">
        <div class="vf-section-header">
            <div class="vf-section-icon">
                <i class="ti ti-building-warehouse" style="font-size:16px;color:var(--accent)"></i>
            </div>
            <div>
                <div class="vf-section-title">Vendor Details</div>
                <div class="vf-section-sub">Basic identification and contact info</div>
            </div>
        </div>

        <div class="vf-grid">
            <div class="vf-field span-full">
                <label class="vf-label" for="v_name">Vendor Name <span class="vf-req">*</span></label>
                <input id="v_name" type="text" name="name" class="vf-input {{ $errors->has('name') ? 'is-error' : '' }}"
                       placeholder="e.g. Sharma Traders" value="{{ $val('name') }}" required/>
                @error('name')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_company">Company</label>
                <input id="v_company" type="text" name="company" class="vf-input {{ $errors->has('company') ? 'is-error' : '' }}"
                       placeholder="Company / firm name" value="{{ $val('company') }}"/>
                @error('company')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_gst">GST Number</label>
                <input id="v_gst" type="text" name="gst_number" class="vf-input {{ $errors->has('gst_number') ? 'is-error' : '' }}"
                       placeholder="22AAAAA0000A1Z5" value="{{ $val('gst_number') }}"/>
                @error('gst_number')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_phone">Phone</label>
                <input id="v_phone" type="text" name="phone" class="vf-input {{ $errors->has('phone') ? 'is-error' : '' }}"
                       placeholder="+91 98765 43210" value="{{ $val('phone') }}"/>
                @error('phone')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_email">Email</label>
                <input id="v_email" type="email" name="email" class="vf-input {{ $errors->has('email') ? 'is-error' : '' }}"
                       placeholder="vendor@example.com" value="{{ $val('email') }}"/>
                @error('email')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_terms">Payment Terms (days)</label>
                <input id="v_terms" type="number" name="payment_terms_days" min="0" max="365"
                       class="vf-input {{ $errors->has('payment_terms_days') ? 'is-error' : '' }}"
                       placeholder="e.g. 30" value="{{ $val('payment_terms_days') }}"/>
                <span style="font-size:11.5px;color:var(--text-400)">Default credit period — a new bill's due date is bill date + this many days</span>
                @error('payment_terms_days')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field span-full">
                <label class="vf-label" for="v_bank">Bank / Payment Details</label>
                <textarea id="v_bank" name="bank_details" class="vf-input vf-textarea {{ $errors->has('bank_details') ? 'is-error' : '' }}"
                          placeholder="A/C name, number, IFSC, UPI…" rows="2">{{ $val('bank_details') }}</textarea>
                @error('bank_details')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="vf-section">
        <div class="vf-section-header">
            <div class="vf-section-icon" style="background:var(--green-dim)">
                <i class="ti ti-map-pin" style="font-size:16px;color:var(--green)"></i>
            </div>
            <div>
                <div class="vf-section-title">Address</div>
                <div class="vf-section-sub">Where the vendor is located</div>
            </div>
        </div>

        <div class="vf-grid">
            <div class="vf-field span-full">
                <label class="vf-label" for="v_address">Address</label>
                <textarea id="v_address" name="address" class="vf-input vf-textarea {{ $errors->has('address') ? 'is-error' : '' }}"
                          placeholder="Street address" rows="2">{{ $val('address') }}</textarea>
                @error('address')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_city">City</label>
                <input id="v_city" type="text" name="city" class="vf-input {{ $errors->has('city') ? 'is-error' : '' }}"
                       value="{{ $val('city') }}"/>
                @error('city')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_state">State</label>
                <select id="v_state" name="state" class="vf-input {{ $errors->has('state') ? 'is-error' : '' }}">
                    <option value="">— Select state —</option>
                    @foreach(config('crm.states', []) as $code => $name)
                    <option value="{{ $code }}" {{ $val('state') === $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <span style="font-size:11.5px;color:var(--text-400)">Used for GST place-of-supply on purchase orders &amp; bills</span>
                @error('state')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>

            <div class="vf-field">
                <label class="vf-label" for="v_pincode">Pincode</label>
                <input id="v_pincode" type="text" name="pincode" class="vf-input {{ $errors->has('pincode') ? 'is-error' : '' }}"
                       value="{{ $val('pincode') }}"/>
                @error('pincode')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="vf-section">
        <div class="vf-section-header">
            <div class="vf-section-icon" style="background:var(--amber-dim)">
                <i class="ti ti-notes" style="font-size:16px;color:var(--amber)"></i>
            </div>
            <div>
                <div class="vf-section-title">Notes</div>
                <div class="vf-section-sub">Anything else worth remembering about this vendor</div>
            </div>
        </div>

        <div class="vf-grid">
            <div class="vf-field span-full">
                <textarea name="notes" class="vf-input vf-textarea {{ $errors->has('notes') ? 'is-error' : '' }}"
                          placeholder="Internal notes..." rows="3">{{ $val('notes') }}</textarea>
                @error('notes')<span class="vf-field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</div>

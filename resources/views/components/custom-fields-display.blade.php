{{--
    Custom Fields Display — Show pages ke liye
    ============================================
    Usage:
        @include('components.custom-fields-display', [
            'customFields' => $customFields,   // Collection from TenantFieldAssignment::getActiveFields()
            'customValues' => $customValues,   // Array from CustomFieldValue::getByKeyForModel($model)
        ])

    customValues = [ 'budget_range' => 'Under ₹50K', 'lead_score' => '75', ... ]
--}}

@if(isset($customFields) && $customFields->isNotEmpty())

@php
    // Sirf woh fields dikhao jinke values hain
    $fieldsWithValues = $customFields->filter(
        fn($f) => isset($customValues[$f['field_key']]) && $customValues[$f['field_key']] !== ''
    );
@endphp

@if($fieldsWithValues->isNotEmpty())
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;margin-top:16px">

    <div style="padding:14px 20px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:8px">
        <div style="width:28px;height:28px;border-radius:var(--r-sm);background:var(--purple-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24" style="width:14px;height:14px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
            </svg>
        </div>
        <span style="font-size:13.5px;font-weight:700;color:var(--text-100)">Additional Information</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr">
        @foreach($fieldsWithValues->values() as $i => $field)
        @php
            $fieldKey  = $field['field_key'];
            $fieldType = $field['field_type'] ?? 'text';
            $rawValue  = $customValues[$fieldKey] ?? '';

            // Format value for display
            $displayValue = match($fieldType) {
                'checkbox'     => $rawValue == '1' ? '✅ Yes' : '❌ No',
                'multi_select' => implode(', ', json_decode($rawValue, true) ?? []),
                'date'         => $rawValue ? \Carbon\Carbon::parse($rawValue)->format('d M Y') : '—',
                'datetime'     => $rawValue ? \Carbon\Carbon::parse($rawValue)->format('d M Y, h:i A') : '—',
                default        => $rawValue,
            };

            $isFullWidth = in_array($fieldType, ['textarea']);
            $isEven      = $i % 2 === 0;
        @endphp

        <div style="
            padding:14px 20px;
            border-bottom:1px solid var(--border-subtle);
            {{ $isFullWidth ? 'grid-column:1/-1' : '' }}
            {{ ($isEven && !$isFullWidth) ? 'border-right:1px solid var(--border-subtle)' : '' }}
        ">
            <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px">
                {{ $field['label'] }}
            </div>

            @if($fieldType === 'url' && $displayValue)
            <a href="{{ $displayValue }}" target="_blank" rel="noopener"
               style="font-size:13.5px;color:var(--accent);text-decoration:none;word-break:break-all">
                {{ $displayValue }}
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:12px;height:12px;display:inline;margin-left:3px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
            </a>
            @else
            <div style="font-size:13.5px;color:var(--text-100);font-weight:500;line-height:1.5;word-break:break-word">
                {{ $displayValue ?: '—' }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

</div>
@endif
@endif
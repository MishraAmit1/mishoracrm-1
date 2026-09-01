@php
    $sections = $contactFields['sections'];
    // Drop fields gated behind a module the tenant doesn't have.
    $fields   = collect($contactFields['fields'])
        ->reject(fn($f) => !empty($f['module']) && !auth()->user()->tenant?->hasModuleEnabled($f['module']))
        ->all();

    // Group fields by section
    $grouped = collect($fields)->groupBy('section');

    // Helper: get old/model value (dates rendered as Y-m-d for <input type=date>)
    $val = function (string $key) use ($model) {
        $v = $model->{$key} ?? '';
        if ($v instanceof \Carbon\CarbonInterface) {
            $v = $v->format('Y-m-d');
        }
        return old($key, $v);
    };

    // Section icon color map
    $sectionIconBg = [
        'blue'   => ['bg'=>'var(--accent-dim)','stroke'=>'var(--accent)'],
        'purple' => ['bg'=>'var(--purple-dim)','stroke'=>'var(--purple)'],
        'teal'   => ['bg'=>'var(--green-dim)','stroke'=>'var(--green)'],
        'amber'  => ['bg'=>'var(--amber-dim)','stroke'=>'var(--amber)'],
        'red'    => ['bg'=>'var(--red-dim)','stroke'=>'var(--red)'],
        'green'  => ['bg'=>'var(--green-dim)','stroke'=>'var(--green)'],
    ];
@endphp

@foreach($sections as $sectionKey => $section)
@if($grouped->has($sectionKey))
@php $sColor = $sectionIconBg[$section['color']] ?? $sectionIconBg['blue']; @endphp

<div class="cf-section">
    {{-- Section Header --}}
    <div class="cf-section-header">
        <div class="cf-section-icon" style="background:{{ $sColor['bg'] }}">
            <i class="{{ $section['icon'] }}" style="font-size:16px;color:{{ $sColor['stroke'] }}" aria-hidden="true"></i>
        </div>
        <div>
            <div class="cf-section-title">{{ $section['title'] }}</div>
            <div class="cf-section-sub">{{ $section['sub'] }}</div>
        </div>
    </div>

    {{-- Fields Grid --}}
    <div class="cf-grid">
        @foreach($grouped[$sectionKey] as $field)
        @php
            $fieldVal  = $val($field['key']);
            $hasError  = $errors->has($field['key']);
            $isFullSpan = ($field['span'] ?? 'half') === 'full';
        @endphp

        <div class="cf-field {{ $isFullSpan ? 'span-full' : '' }}">
            <label class="cf-label" for="field_{{ $field['key'] }}">
                {{ $field['label'] }}
                @if($field['required'] ?? false)
                <span class="cf-req">*</span>
                @endif
            </label>

            {{-- Textarea --}}
            @if($field['type'] === 'textarea')
            <textarea
                id="field_{{ $field['key'] }}"
                name="{{ $field['key'] }}"
                class="cf-input cf-textarea {{ $hasError ? 'is-error' : '' }}"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                {{ ($field['required'] ?? false) ? 'required' : '' }}
                rows="3"
            >{{ $fieldVal }}</textarea>

            {{-- Select --}}
            @elseif($field['type'] === 'select')
            <select
                id="field_{{ $field['key'] }}"
                name="{{ $field['key'] }}"
                class="cf-input cf-select {{ $hasError ? 'is-error' : '' }}"
                {{ ($field['required'] ?? false) ? 'required' : '' }}
            >
                <option value="">{{ $field['placeholder'] ?? '— Select —' }}</option>

                {{-- lead_id — dynamic from controller --}}
                @if($field['key'] === 'lead_id' && isset($leads))
                    @foreach($leads as $lead)
                    <option value="{{ $lead->id }}" {{ $fieldVal == $lead->id ? 'selected' : '' }}>
                        {{ $lead->name }} @if($lead->company)({{ $lead->company }})@endif
                    </option>
                    @endforeach
                {{-- Static options from config --}}
                @elseif(!empty($field['options']))
                    @foreach($field['options'] as $optVal => $optLabel)
                    <option value="{{ $optVal }}" {{ $fieldVal == $optVal ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                    @endforeach
                @endif
            </select>

            {{-- All other inputs --}}
            @else
            <input
                id="field_{{ $field['key'] }}"
                type="{{ $field['type'] }}"
                name="{{ $field['key'] }}"
                class="cf-input {{ $hasError ? 'is-error' : '' }}"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                value="{{ $fieldVal }}"
                {{ ($field['required'] ?? false) ? 'required' : '' }}
            />
            @endif

            @if($hasError)
            <span class="cf-field-error">{{ $errors->first($field['key']) }}</span>
            @elseif(!empty($field['hint']))
            <span class="cf-field-hint">{{ $field['hint'] }}</span>
            @endif
            @if($field['key'] === 'phone')
            <div id="dupWarning" style="display:none;align-items:center;gap:6px;font-size:12px;color:var(--amber);background:var(--amber-dim);border:1px solid #F0D9A8;border-radius:6px;padding:6px 10px;margin-top:6px"></div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endforeach
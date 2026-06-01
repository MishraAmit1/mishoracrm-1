{{--
    Reusable Custom Fields Form Component
    ======================================
    Usage in create.blade.php:
        @include('components.custom-fields-form', [
            'customFields' => $customFields,
            'customValues' => [],
        ])

    Usage in edit.blade.php:
        @include('components.custom-fields-form', [
            'customFields' => $customFields,
            'customValues' => $customValues,
        ])

    $customFields = TenantFieldAssignment::getActiveFields(tenantId, 'lead')
    $customValues = CustomFieldValue::getByIdForModel($model)  [assignmentId => value]
--}}

@if(isset($customFields) && $customFields->isNotEmpty())

<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;margin-top:16px">

    {{-- Section header --}}
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:10px">
        <div style="width:34px;height:34px;border-radius:var(--r-sm);background:var(--purple-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:var(--text-100)">Additional Information</div>
            <div style="font-size:12px;color:var(--text-400);margin-top:1px">
                {{ $customFields->count() }} custom field{{ $customFields->count() > 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    {{-- Fields grid --}}
    <div style="padding:20px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            @foreach($customFields as $field)
            @php
                $assignmentId = $field['id'];
                $fieldType    = $field['field_type'] ?? 'text';
                $label        = $field['label'] ?? '';
                $placeholder  = $field['placeholder'] ?? '';
                $isRequired   = $field['is_required'] ?? false;
                $options      = $field['options'] ?? [];
                $inputName    = "custom_fields[{$assignmentId}]";
                $inputId      = "cf_{$assignmentId}";

                // Get saved value
                $savedValue = $customValues[$assignmentId] ?? $field['default_value'] ?? '';
                $oldValue   = old($inputName, $savedValue);

                // Multi select
                $selectedOptions = [];
                if ($fieldType === 'multi_select' && $savedValue) {
                    $selectedOptions = is_array($savedValue)
                        ? $savedValue
                        : (json_decode($savedValue, true) ?? []);
                }
            @endphp

            {{-- Textarea spans full width --}}
            <div style="{{ $fieldType === 'textarea' ? 'grid-column:1/-1' : '' }}">
                <div style="display:flex;flex-direction:column;gap:7px">

                    {{-- Label --}}
                    <label for="{{ $inputId }}"
                           style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px;display:flex;align-items:center;gap:6px">
                        {{ $label }}
                        @if($isRequired)
                        <span style="color:var(--red)">*</span>
                        @endif
                        <span style="font-size:10px;color:var(--text-400);font-weight:400;text-transform:none;letter-spacing:0;background:var(--bg-elevated);padding:1px 5px;border-radius:3px;font-family:var(--mono)">
                            {{ $fieldType }}
                        </span>
                    </label>

                    {{-- Input --}}
                    @switch($fieldType)

                        @case('text')
                        @case('email')
                        @case('url')
                        @case('phone')
                            <input
                                type="{{ $fieldType === 'text' ? 'text' : $fieldType }}"
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                placeholder="{{ $placeholder }}"
                                value="{{ $oldValue }}"
                                {{ $isRequired ? 'required' : '' }}
                            />
                            @break

                        @case('number')
                            <input
                                type="number"
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                placeholder="{{ $placeholder ?: '0' }}"
                                value="{{ $oldValue }}"
                                {{ $isRequired ? 'required' : '' }}
                            />
                            @break

                        @case('date')
                            <input
                                type="date"
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                value="{{ $oldValue }}"
                                {{ $isRequired ? 'required' : '' }}
                            />
                            @break

                        @case('datetime')
                            <input
                                type="datetime-local"
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                value="{{ $oldValue }}"
                                {{ $isRequired ? 'required' : '' }}
                            />
                            @break

                        @case('textarea')
                            <textarea
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                placeholder="{{ $placeholder }}"
                                rows="3"
                                style="resize:vertical"
                                {{ $isRequired ? 'required' : '' }}
                            >{{ $oldValue }}</textarea>
                            @break

                        @case('dropdown')
                            <select
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input field-select"
                                {{ $isRequired ? 'required' : '' }}
                            >
                                <option value="">{{ $placeholder ?: '— Select —' }}</option>
                                @foreach($options as $opt)
                                <option value="{{ $opt }}"
                                    {{ $oldValue === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                                @endforeach
                            </select>
                            @break

                        @case('multi_select')
                            <div style="border:1.5px solid var(--border-default);border-radius:var(--r-sm);padding:10px 14px;background:var(--bg-input);display:flex;flex-wrap:wrap;gap:10px">
                                @foreach($options as $opt)
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-100);cursor:pointer;white-space:nowrap">
                                    <input
                                        type="checkbox"
                                        name="{{ $inputName }}[]"
                                        value="{{ $opt }}"
                                        {{ in_array($opt, old($inputName.'[]', $selectedOptions)) ? 'checked' : '' }}
                                        style="accent-color:var(--accent);width:14px;height:14px"
                                    />
                                    {{ $opt }}
                                </label>
                                @endforeach
                            </div>
                            @break

                        @case('checkbox')
                            <label style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);cursor:pointer">
                                <input type="hidden" name="{{ $inputName }}" value="0"/>
                                <input
                                    type="checkbox"
                                    name="{{ $inputName }}"
                                    id="{{ $inputId }}"
                                    value="1"
                                    {{ $oldValue == '1' ? 'checked' : '' }}
                                    style="accent-color:var(--accent);width:16px;height:16px"
                                />
                                <span style="font-size:13.5px;color:var(--text-100)">
                                    {{ $placeholder ?: 'Yes' }}
                                </span>
                            </label>
                            @break

                    @endswitch

                    {{-- Validation error --}}
                    @error($inputName)
                    <span style="font-size:12px;color:var(--red);font-weight:500">{{ $message }}</span>
                    @enderror

                </div>
            </div>

            @endforeach

        </div>
    </div>
</div>

@endif
{{--
    Custom Fields Form Component
    ==============================
    Usage in create.blade.php:
        @include('components.custom-fields-form', [
            'customFields' => $customFields,  // Collection — TenantFieldAssignment::getActiveFields()
            'customValues' => [],
        ])

    Usage in edit.blade.php:
        @include('components.custom-fields-form', [
            'customFields' => $customFields,
            'customValues' => $customValues,  // CustomFieldValue::getByAssignmentForModel($model)
                                              // Format: [assignment_id => value]
        ])

    ⚠️ Input name format: custom_fields[{assignment_id}]
       Controller mein: $request->input('custom_fields', [])
       = [assignment_id => value, ...]
--}}

@if(isset($customFields) && $customFields->isNotEmpty())

<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;margin-top:16px">

    {{-- Header --}}
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:10px">
        <div style="width:34px;height:34px;border-radius:var(--r-sm);background:var(--purple-dim);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:var(--text-100)">Additional Information</div>
            <div style="font-size:12px;color:var(--text-400);margin-top:1px">
                {{ $customFields->count() }} custom field{{ $customFields->count() > 1 ? 's' : '' }}
            </div>
        </div>
    </div>

    <div style="padding:20px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

            @foreach($customFields as $field)
            @php
                $assignmentId = $field['id'];           // TenantFieldAssignment id
                $fieldType    = $field['field_type'] ?? 'text';
                $fieldKey     = $field['field_key']  ?? '';
                $label        = $field['label']      ?? '';
                $placeholder  = $field['placeholder'] ?? '';
                $isRequired   = $field['is_required'] ?? false;
                $options      = $field['options']     ?? [];

                // Input name — controller mein custom_fields[assignment_id] milega
                $inputName = "custom_fields[{$assignmentId}]";
                $inputId   = "cf_{$assignmentId}";

                // Saved value — edit mein prefill ke liye
                // customValues format: [assignment_id => value]
                $savedValue = $customValues[$assignmentId] ?? $field['default_value'] ?? '';
                $oldValue   = old($inputName, $savedValue);

                // Multi select saved values
                $selectedOptions = [];
                if ($fieldType === 'multi_select' && $oldValue) {
                    $selectedOptions = is_array($oldValue)
                        ? $oldValue
                        : (json_decode($oldValue, true) ?? []);
                }
            @endphp

            {{-- Textarea spans full width --}}
            <div style="{{ in_array($fieldType, ['textarea']) ? 'grid-column:1/-1' : '' }}">
                <div style="display:flex;flex-direction:column;gap:7px">

                    {{-- Label --}}
                    <label for="{{ $inputId }}"
                           style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        {{ $label }}
                        @if($isRequired)
                            <span style="color:var(--red);margin-left:2px">*</span>
                        @endif
                    </label>

                    {{-- Input based on field_type --}}
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
                                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-100);cursor:pointer">
                                        <input
                                            type="checkbox"
                                            name="{{ $inputName }}[]"
                                            value="{{ $opt }}"
                                            {{ in_array($opt, $selectedOptions) ? 'checked' : '' }}
                                            style="accent-color:var(--accent);width:14px;height:14px"
                                        />
                                        {{ $opt }}
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('checkbox')
                            {{-- Hidden 0 value ensure karo agar unchecked ho --}}
                            <input type="hidden" name="{{ $inputName }}" value="0"/>
                            <label style="display:flex;align-items:center;gap:10px;padding:11px 14px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);cursor:pointer">
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

                        @default
                            <input
                                type="text"
                                name="{{ $inputName }}"
                                id="{{ $inputId }}"
                                class="field-input"
                                placeholder="{{ $placeholder }}"
                                value="{{ $oldValue }}"
                                {{ $isRequired ? 'required' : '' }}
                            />

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
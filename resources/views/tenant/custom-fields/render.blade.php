{{--
    Reusable Custom Fields Form Component
    
    Usage in create.blade.php:
        @include('components.custom-fields.render', [
            'fields' => $customFields,
            'values' => [],
        ])
    
    Usage in edit.blade.php:
        @include('components.custom-fields.render', [
            'fields' => $customFields,
            'values' => $customValues,  // CustomFieldValue::getByIdForModel($model)
        ])
--}}

@if($fields->isNotEmpty())
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;margin-top:16px">

    {{-- Section header --}}
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:10px">
        <div style="width:32px;height:32px;border-radius:var(--r-sm);background:var(--purple-dim);display:flex;align-items:center;justify-content:center">
            <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div style="font-size:13px;font-weight:700;color:var(--text-100)">Additional Information</div>
            <div style="font-size:12px;color:var(--text-400)">Custom fields for this module</div>
        </div>
    </div>

    <div style="padding:20px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            @foreach($fields as $field)
            @php
                $val         = $values[$field->id] ?? $field->default_value ?? '';
                $inputName   = "custom_fields[{$field->id}]";
                $inputId     = "cf_{$field->id}";
                $isRequired  = $field->is_required;
                $placeholder = $field->placeholder ?? '';
            @endphp

            {{-- Textarea takes full width --}}
            <div class="{{ $field->field_type === 'textarea' ? 'span-2' : '' }}"
                 style="{{ $field->field_type === 'textarea' ? 'grid-column:1/-1' : '' }}">

                <div style="display:flex;flex-direction:column;gap:7px">

                    {{-- Label --}}
                    <label for="{{ $inputId }}"
                           style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        {{ $field->label }}
                        @if($isRequired)<span style="color:var(--red);margin-left:2px">*</span>@endif
                        <span style="font-size:10px;color:var(--text-400);font-weight:400;text-transform:none;letter-spacing:0;margin-left:6px">
                            {{ \App\Models\CustomField::fieldTypes()[$field->field_type]['label'] ?? '' }}
                        </span>
                    </label>

                    {{-- Input based on type --}}
                    @switch($field->field_type)

                        @case('text')
                        @case('url')
                        @case('email')
                        @case('phone')
                            <input type="{{ $field->field_type === 'text' ? 'text' : $field->field_type }}"
                                   name="{{ $inputName }}"
                                   id="{{ $inputId }}"
                                   class="field-input"
                                   placeholder="{{ $placeholder }}"
                                   value="{{ old($inputName, $val) }}"
                                   {{ $isRequired ? 'required' : '' }}/>
                            @break

                        @case('number')
                            <input type="number"
                                   name="{{ $inputName }}"
                                   id="{{ $inputId }}"
                                   class="field-input"
                                   placeholder="{{ $placeholder ?: '0' }}"
                                   value="{{ old($inputName, $val) }}"
                                   {{ $isRequired ? 'required' : '' }}/>
                            @break

                        @case('date')
                            <input type="date"
                                   name="{{ $inputName }}"
                                   id="{{ $inputId }}"
                                   class="field-input"
                                   value="{{ old($inputName, $val) }}"
                                   {{ $isRequired ? 'required' : '' }}/>
                            @break

                        @case('datetime')
                            <input type="datetime-local"
                                   name="{{ $inputName }}"
                                   id="{{ $inputId }}"
                                   class="field-input"
                                   value="{{ old($inputName, $val) }}"
                                   {{ $isRequired ? 'required' : '' }}/>
                            @break

                        @case('textarea')
                            <textarea name="{{ $inputName }}"
                                      id="{{ $inputId }}"
                                      class="field-input"
                                      style="resize:vertical;min-height:80px"
                                      placeholder="{{ $placeholder }}"
                                      {{ $isRequired ? 'required' : '' }}>{{ old($inputName, $val) }}</textarea>
                            @break

                        @case('dropdown')
                            <select name="{{ $inputName }}"
                                    id="{{ $inputId }}"
                                    class="field-input field-select"
                                    {{ $isRequired ? 'required' : '' }}>
                                <option value="">{{ $placeholder ?: '— Select —' }}</option>
                                @foreach($field->options_array as $opt)
                                <option value="{{ $opt }}"
                                    {{ old($inputName, $val) === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                                @endforeach
                            </select>
                            @break

                        @case('multi_select')
                            @php
                                $selectedOpts = is_string($val) ? json_decode($val, true) ?? [] : [];
                            @endphp
                            <div style="border:1.5px solid var(--border-default);border-radius:var(--r-sm);padding:10px 14px;background:var(--bg-input);display:flex;flex-wrap:wrap;gap:8px">
                                @foreach($field->options_array as $opt)
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-100);cursor:pointer">
                                    <input type="checkbox"
                                           name="{{ $inputName }}[]"
                                           value="{{ $opt }}"
                                           {{ in_array($opt, old($inputName.'[]', $selectedOpts)) ? 'checked' : '' }}
                                           style="accent-color:var(--accent);width:14px;height:14px"/>
                                    {{ $opt }}
                                </label>
                                @endforeach
                            </div>
                            @break

                        @case('checkbox')
                            <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);cursor:pointer">
                                <input type="hidden"   name="{{ $inputName }}" value="0"/>
                                <input type="checkbox" name="{{ $inputName }}" value="1"
                                       id="{{ $inputId }}"
                                       {{ old($inputName, $val) == '1' ? 'checked' : '' }}
                                       style="accent-color:var(--accent);width:16px;height:16px"/>
                                <span style="font-size:13.5px;color:var(--text-100)">
                                    {{ $placeholder ?: 'Yes' }}
                                </span>
                            </label>
                            @break

                    @endswitch

                    {{-- Validation error --}}
                    @error($inputName)
                    <span style="font-size:12px;color:var(--red)">{{ $message }}</span>
                    @enderror

                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
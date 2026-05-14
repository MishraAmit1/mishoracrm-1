{{-- 
────────────────────────────────────────────────────────────
 Partial : resources/views/tenant/tasks/_form.blade.php
 Used by : create.blade.php & edit.blade.php

 Scope Variables:
    $taskConfig
    $staffList
    $model
    $isEdit
────────────────────────────────────────────────────────────
--}}

@php

$cfgStatuses = $taskConfig['stages'];

$cfgFields = collect($taskConfig['fields']);

$sections = $taskConfig['sections'];

$grouped = $cfgFields->groupBy('section');

/*
|--------------------------------------------------------------------------
| Current Value Helper
|--------------------------------------------------------------------------
*/
$val = fn(string $key) => old($key, $model->{$key} ?? '');

/*
|--------------------------------------------------------------------------
| Active Status
|--------------------------------------------------------------------------
*/
$activeStatus = old(
    'status',
    $model->status ?? request('status', 'pending')
);

/*
|--------------------------------------------------------------------------
| Section Colors
|--------------------------------------------------------------------------
*/
$secColors = [

    'blue' => [
        'bg' => '#E6F1FB',
        'ic' => '#185FA5',
    ],

    'purple' => [
        'bg' => '#EEEDFE',
        'ic' => '#534AB7',
    ],

    'teal' => [
        'bg' => '#E1F5EE',
        'ic' => '#0F6E56',
    ],

    'amber' => [
        'bg' => '#FAEEDA',
        'ic' => '#BA7517',
    ],

];

@endphp


{{-- ═══════════════════════════════════════
     SECTION LOOP
═══════════════════════════════════════ --}}
@foreach($sections as $secKey => $section)

@if($grouped->has($secKey))

@php
    $sc = $secColors[$section['color']] ?? $secColors['blue'];
@endphp

<div class="df-section"
     id="sec_{{ $secKey }}">

    {{-- SECTION HEADER --}}
    <div class="df-sec-head">

        <div class="df-sec-icon"
             style="background:{{ $sc['bg'] }}">

            <i class="ti {{ $section['icon'] }}"
               style="color:{{ $sc['ic'] }}"></i>

        </div>

        <div>

            <div class="df-sec-title">
                {{ $section['title'] }}
            </div>

            @if(!empty($section['sub']))
            <div class="df-sec-sub">
                {{ $section['sub'] }}
            </div>
            @endif

        </div>

    </div>

    {{-- GRID --}}
    <div class="df-grid">

        @foreach($grouped[$secKey] as $field)

        @php

            $fVal = $val($field['key']);

            $hasErr = $errors->has($field['key']);

            $isFull = ($field['span'] ?? 'half') === 'full';

            $isStatus = $field['key'] === 'status';

            $isPriority = $field['key'] === 'priority';

        @endphp

        <div class="df-field {{ $isFull ? 'span-full' : '' }}">

            {{-- LABEL --}}
            <label class="df-label"
                   for="df_{{ $field['key'] }}">

                {{ $field['label'] }}

                @if($field['required'] ?? false)
                <span class="df-req">*</span>
                @endif

            </label>


            {{-- ═══════════════════════════════════════
                 STATUS PICKER
            ═══════════════════════════════════════ --}}
            @if($isStatus)

            <input type="hidden"
                   name="status"
                   id="statusHidden"
                   value="{{ $activeStatus }}">

            <div class="stage-picker"
                 id="statusPicker">

                @foreach($cfgStatuses as $slug => $status)

                <button type="button"
                        class="sp-btn {{ $activeStatus === $slug ? 'sp-active' : '' }}"
                        data-status="{{ $slug }}"
                        style="
                            --sp-color:{{ $status['color'] }};
                            --sp-bg:{{ $status['bg'] }};
                            --sp-text:{{ $status['text_color'] }};
                            {{ $activeStatus === $slug
                                ? 'background:'.$status['bg'].';border-color:'.$status['color'].';color:'.$status['text_color']
                                : ''
                            }}
                        ">

                    <span class="sp-dot"
                          style="background:{{ $status['color'] }}"></span>

                    {{ $status['label'] }}

                </button>

                @endforeach

            </div>

            @if($hasErr)
            <span class="df-err">
                {{ $errors->first('status') }}
            </span>
            @endif


            {{-- ═══════════════════════════════════════
                 PRIORITY
            ═══════════════════════════════════════ --}}
            @elseif($isPriority)

            <select id="df_priority"
                    name="priority"
                    class="df-input df-sel {{ $hasErr ? 'is-err' : '' }}">

                @foreach(config('task_fields.priorities') as $key => $priority)

                <option value="{{ $key }}"
                        {{ $fVal == $key ? 'selected' : '' }}>

                    {{ $priority['label'] }}

                </option>

                @endforeach

            </select>


            {{-- ═══════════════════════════════════════
                 DESCRIPTION
            ═══════════════════════════════════════ --}}
            @elseif($field['type'] === 'textarea')

            <textarea id="df_{{ $field['key'] }}"
                      name="{{ $field['key'] }}"
                      rows="4"
                      class="df-input df-area {{ $hasErr ? 'is-err' : '' }}"
                      placeholder="{{ $field['placeholder'] ?? '' }}">{{ $fVal }}</textarea>


            {{-- ═══════════════════════════════════════
                 ASSIGNED TO
            ═══════════════════════════════════════ --}}
            @elseif($field['key'] === 'assigned_to')

            <select id="df_assigned_to"
                    name="assigned_to"
                    class="df-input df-sel {{ $hasErr ? 'is-err' : '' }}">

                <option value="">
                    — Unassigned —
                </option>

                @foreach($staffList as $id => $name)

                <option value="{{ $id }}"
                        {{ $fVal == $id ? 'selected' : '' }}>

                    {{ $name }}

                </option>

                @endforeach

            </select>


            {{-- ═══════════════════════════════════════
                 TASKABLE TYPE
            ═══════════════════════════════════════ --}}
            @elseif($field['key'] === 'taskable_type')

            <select id="df_taskable_type"
                    name="taskable_type"
                    class="df-input df-sel {{ $hasErr ? 'is-err' : '' }}">

                <option value="">
                    — Select Type —
                </option>

                @foreach($field['options'] as $key => $label)

                <option value="{{ $key }}"
                        {{ $fVal == $key ? 'selected' : '' }}>

                    {{ $label }}

                </option>

                @endforeach

            </select>


            {{-- ═══════════════════════════════════════
                 TASKABLE ID
            ═══════════════════════════════════════ --}}
            @elseif($field['key'] === 'taskable_id')

            <select id="df_taskable_id"
                    name="taskable_id"
                    class="df-input df-sel {{ $hasErr ? 'is-err' : '' }}">

                <option value="">
                    — Select Record —
                </option>

            </select>


            {{-- ═══════════════════════════════════════
                 DATE / TEXT INPUT
            ═══════════════════════════════════════ --}}
            @else

            <input id="df_{{ $field['key'] }}"
                   type="{{ $field['type'] }}"
                   name="{{ $field['key'] }}"
                   value="{{ $fVal }}"
                   placeholder="{{ $field['placeholder'] ?? '' }}"
                   class="df-input {{ $hasErr ? 'is-err' : '' }}"
                   {{ ($field['required'] ?? false) ? 'required' : '' }}>

            @endif


            {{-- ERROR --}}
            @if($hasErr)

            <span class="df-err">
                {{ $errors->first($field['key']) }}
            </span>

            @endif

            {{-- HINT --}}
            @if(!empty($field['hint']))

            <span class="df-hint">
                {{ $field['hint'] }}
            </span>

            @endif

        </div>

        @endforeach

    </div>

</div>

@endif
@endforeach


{{-- ═══════════════════════════════════════
     STATUS PICKER JS
═══════════════════════════════════════ --}}
<script>
(function(){

    const buttons = document.querySelectorAll('.sp-btn');

    const hiddenInput = document.getElementById('statusHidden');

    buttons.forEach(btn => {

        btn.addEventListener('click', function(){

            const status = this.dataset.status;

            /*
            |--------------------------------------------------------------------------
            | Update Hidden Input
            |--------------------------------------------------------------------------
            */
            hiddenInput.value = status;

            /*
            |--------------------------------------------------------------------------
            | Reset Buttons
            |--------------------------------------------------------------------------
            */
            buttons.forEach(b => {

                b.classList.remove('sp-active');

                b.style.background = '';

                b.style.borderColor = '';

                b.style.color = '';

            });

            /*
            |--------------------------------------------------------------------------
            | Active Button
            |--------------------------------------------------------------------------
            */
            this.classList.add('sp-active');

            this.style.background =
                getComputedStyle(this).getPropertyValue('--sp-bg');

            this.style.borderColor =
                getComputedStyle(this).getPropertyValue('--sp-color');

            this.style.color =
                getComputedStyle(this).getPropertyValue('--sp-text');

        });

    });

})();
</script>
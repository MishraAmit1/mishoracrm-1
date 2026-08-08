@php
    $emails = $employee->emails ?? [];
    $phones = $employee->phones ?? [];
    if (empty($emails)) $emails = [''];
    if (empty($phones)) $phones = [''];
@endphp
<div class="emp-row" data-emp-row>
    @if($employee)
    <input type="hidden" name="employees[{{ $index }}][id]" value="{{ $employee->id }}">
    @endif
    <div class="emp-row-head">
        <div class="emp-row-title">
            <i class="ti ti-user-circle" style="font-size:14px" aria-hidden="true"></i>
            {{ $employee->name ?? 'New Employee' }}
        </div>
        <div style="display:flex;align-items:center;gap:14px">
            <label class="emp-primary-label">
                <input type="radio" name="primary_employee_index" value="{{ $index }}"
                    {{ ($employee && $employee->is_primary) ? 'checked' : '' }}>
                Primary Contact
            </label>
            <button type="button" class="emp-remove-btn" data-remove-employee
                @if($employee) data-employee-id="{{ $employee->id }}" @endif title="Remove employee">
                <i class="ti ti-trash" style="font-size:14px" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="emp-grid">
        <div class="cf-field">
            <label class="cf-label">Employee Name</label>
            <input type="text" name="employees[{{ $index }}][name]" class="cf-input"
                placeholder="e.g. Priya Verma" value="{{ $employee->name ?? '' }}">
        </div>
        <div class="cf-field">
            <label class="cf-label">Designation</label>
            <input type="text" name="employees[{{ $index }}][designation]" class="cf-input"
                placeholder="e.g. Purchase Manager" value="{{ $employee->designation ?? '' }}">
        </div>
        <div class="cf-field span-full">
            <label class="cf-label">Email(s)</label>
            <div class="multi-input-list" data-multi="emails">
                @foreach($emails as $email)
                <div class="multi-input-row">
                    <input type="email" name="employees[{{ $index }}][emails][]" class="cf-input"
                        placeholder="employee@company.com" value="{{ $email }}">
                    <button type="button" class="multi-remove-btn" data-multi-remove title="Remove">&times;</button>
                </div>
                @endforeach
            </div>
            <button type="button" class="multi-add-btn" data-multi-add="emails">
                <i class="ti ti-plus" style="font-size:11px" aria-hidden="true"></i> Add another email
            </button>
        </div>
        <div class="cf-field span-full">
            <label class="cf-label">Contact No(s)</label>
            <div class="multi-input-list" data-multi="phones">
                @foreach($phones as $phone)
                <div class="multi-input-row">
                    <input type="tel" name="employees[{{ $index }}][phones][]" class="cf-input"
                        placeholder="+91 98765 43210" value="{{ $phone }}">
                    <button type="button" class="multi-remove-btn" data-multi-remove title="Remove">&times;</button>
                </div>
                @endforeach
            </div>
            <button type="button" class="multi-add-btn" data-multi-add="phones">
                <i class="ti ti-plus" style="font-size:11px" aria-hidden="true"></i> Add another number
            </button>
        </div>
        <div class="cf-field span-full">
            <label class="cf-label">Visiting Card / File</label>
            <input type="file" name="employees[{{ $index }}][attachments][]" class="cf-input"
                multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
            <span class="cf-field-hint">Images, PDFs, Word or Excel — up to 10 MB each, max 5 files</span>
            @if($employee && $employee->attachments->isNotEmpty())
            <div class="emp-attach-list">
                @foreach($employee->attachments as $att)
                <div class="emp-attach-item">
                    <a href="{{ $att->url }}" target="_blank" rel="noopener">
                        <i class="ti ti-paperclip" style="font-size:12px" aria-hidden="true"></i>
                        {{ $att->original_name }}
                    </a>
                    <button type="submit" form="del-emp-attach-{{ $att->id }}" class="attach-del" title="Delete">&times;</button>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

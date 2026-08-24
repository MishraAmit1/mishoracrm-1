@php
    $companyVal   = old('company', $contact->company ?? '');
    $existingEmps = isset($contact) ? $contact->employees : collect();
@endphp
<div class="cf-section" id="employeesSection" style="{{ ($companyVal || $existingEmps->isNotEmpty()) ? '' : 'display:none' }}">
    <div class="cf-section-header">
        <div class="cf-section-icon" style="background:var(--purple-dim)">
            <i class="ti ti-users" style="font-size:16px;color:var(--purple)" aria-hidden="true"></i>
        </div>
        <div>
            <div class="cf-section-title">Company Employees</div>
            <div class="cf-section-sub">Is company ke employees add karo — name, designation, email, contact no.</div>
        </div>
    </div>

    <div id="employeeRows">
        @foreach($existingEmps as $i => $employee)
            @include('tenant.contacts._employee_row', ['index' => $i, 'employee' => $employee])
        @endforeach
    </div>

    <button type="button" id="addEmployeeBtn" class="btn btn-secondary" style="margin-top:4px">
        <i class="ti ti-plus" style="font-size:13px" aria-hidden="true"></i>
        Add Employee
    </button>

    <div id="removedEmployeeIds"></div>
</div>

<template id="employeeRowTemplate">
@include('tenant.contacts._employee_row', ['index' => '__IDX__', 'employee' => null])
</template>

@push('styles')
<style>
.emp-row {
    border: 1px solid var(--border-default); border-radius: 10px;
    padding: 14px 16px; margin-bottom: 12px; background: var(--bg-elevated);
}
.emp-row:last-child { margin-bottom: 0; }
.emp-row-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
}
.emp-row-title {
    font-size: 12.5px; font-weight: 600; color: var(--text-100);
    display: flex; align-items: center; gap: 6px;
}
.emp-primary-label {
    display: flex; align-items: center; gap: 5px; cursor: pointer;
    font-size: 11.5px; font-weight: 600; color: var(--text-300);
    text-transform: uppercase; letter-spacing: .4px; white-space: nowrap;
}
.emp-primary-label input[type="radio"] { accent-color: #1D9E75; cursor: pointer; margin: 0; }
.emp-remove-btn {
    background: transparent; border: 1px solid var(--border-default); border-radius: 6px;
    color: #A32D2D; cursor: pointer; padding: 5px 8px;
    display: flex; align-items: center; justify-content: center;
}
.emp-remove-btn:hover { background: #FCEBEB; border-color: #F09595; }
.emp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.emp-grid .span-full { grid-column: 1/-1; }
@media(max-width:640px) { .emp-grid { grid-template-columns: 1fr; } .emp-grid .span-full { grid-column: 1; } }

.multi-input-list { display: flex; flex-direction: column; gap: 8px; }
.multi-input-row { display: flex; align-items: center; gap: 6px; }
.multi-input-row .cf-input { flex: 1; }
.multi-remove-btn {
    background: transparent; border: 1px solid var(--border-default); border-radius: 6px;
    color: var(--text-300); cursor: pointer; width: 30px; height: 30px; flex-shrink: 0;
    font-size: 15px; line-height: 1; display: flex; align-items: center; justify-content: center;
}
.multi-remove-btn:hover { background: #FCEBEB; border-color: #F09595; color: #A32D2D; }
.multi-add-btn {
    margin-top: 8px; background: transparent; border: none; cursor: pointer;
    color: var(--accent, #185FA5); font-size: 12px; font-weight: 600;
    font-family: 'DM Sans', var(--font), sans-serif; padding: 0;
    display: inline-flex; align-items: center; gap: 4px;
}
.multi-add-btn:hover { text-decoration: underline; }

.emp-attach-list { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
.emp-attach-item {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 7px 10px; background: var(--bg-surface); border: 1px solid var(--border-subtle);
    border-radius: 7px; font-size: 12.5px;
}
.emp-attach-item a {
    color: var(--text-100); text-decoration: none; display: flex; align-items: center; gap: 6px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.emp-attach-item a:hover { color: var(--accent, #185FA5); }
.attach-del {
    background: transparent; border: none; color: var(--text-300); cursor: pointer;
    font-size: 15px; line-height: 1; flex-shrink: 0; padding: 2px 4px;
}
.attach-del:hover { color: #A32D2D; }
</style>
@endpush

@push('scripts')
<script>
(function(){
    let empIdx = {{ isset($contact) ? $contact->employees->count() : 0 }};

    const employeeRows    = document.getElementById('employeeRows');
    const addEmployeeBtn  = document.getElementById('addEmployeeBtn');
    const template         = document.getElementById('employeeRowTemplate');
    const removedContainer = document.getElementById('removedEmployeeIds');
    const companyInput    = document.getElementById('field_company');
    const employeesSection = document.getElementById('employeesSection');

    function addEmployeeRow(){
        const html = template.innerHTML.trim().replaceAll('__IDX__', empIdx++);
        const wrap = document.createElement('div');
        wrap.innerHTML = html;
        employeeRows.appendChild(wrap.firstElementChild);
    }

    addEmployeeBtn?.addEventListener('click', addEmployeeRow);

    function syncEmployeesVisibility(){
        if (companyInput && companyInput.value.trim() && employeesSection) {
            employeesSection.style.display = '';
        }
    }
    companyInput?.addEventListener('input', syncEmployeesVisibility);

    document.addEventListener('click', function(e){

        // Remove an employee row
        const removeBtn = e.target.closest('[data-remove-employee]');
        if (removeBtn) {
            const row   = removeBtn.closest('.emp-row');
            const empId = removeBtn.getAttribute('data-employee-id');
            if (empId) {
                const hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'removed_employee_ids[]';
                hidden.value = empId;
                removedContainer?.appendChild(hidden);
            }
            row?.remove();
            return;
        }

        // Add an email/phone row
        const addBtn = e.target.closest('[data-multi-add]');
        if (addBtn) {
            const list = addBtn.previousElementSibling;
            const firstInput = list?.querySelector('input');
            if (!list || !firstInput) return;

            const row = document.createElement('div');
            row.className = 'multi-input-row';

            const input = firstInput.cloneNode();
            input.value = '';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'multi-remove-btn';
            remove.setAttribute('data-multi-remove', '');
            remove.innerHTML = '&times;';

            row.appendChild(input);
            row.appendChild(remove);
            list.appendChild(row);
            input.focus();
            return;
        }

        // Remove an email/phone row
        const removeMultiBtn = e.target.closest('[data-multi-remove]');
        if (removeMultiBtn) {
            const list = removeMultiBtn.closest('.multi-input-list');
            const row  = removeMultiBtn.closest('.multi-input-row');
            if (list && list.querySelectorAll('.multi-input-row').length > 1) {
                row.remove();
            } else if (row) {
                row.querySelector('input').value = '';
            }
            return;
        }
    });
})();
</script>
@endpush

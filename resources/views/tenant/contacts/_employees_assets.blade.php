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

/* ── File dropzone ── */
.dz { display: flex; flex-direction: column; gap: 10px; }
.dz-input {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
.dz-drop {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 5px; text-align: center; padding: 24px 18px; border-radius: 12px;
    border: 1.5px dashed var(--border-default); background: var(--bg-elevated);
    cursor: pointer; transition: border-color .15s, background .15s;
}
.dz-drop:hover { border-color: var(--accent, #185FA5); background: var(--bg-surface); }
.dz.dragover .dz-drop { border-color: var(--accent, #185FA5); background: var(--accent-dim, #E6F1FB); }
.dz-icon {
    width: 36px; height: 36px; border-radius: 10px; background: #E6F1FB; color: #185FA5;
    display: flex; align-items: center; justify-content: center; font-size: 17px; margin-bottom: 2px;
}
.dz-text { font-size: 12.5px; color: var(--text-200); font-weight: 500; }
.dz-text strong { color: var(--accent, #185FA5); font-weight: 600; }
.dz-hint { font-size: 11px; color: var(--text-400); }

.dz-preview, .dz-existing { display: flex; flex-direction: column; gap: 7px; }
.dz-existing { padding-top: 9px; border-top: 1px dashed var(--border-subtle); }
.dz-preview-label, .dz-existing-label {
    font-size: 10.5px; font-weight: 600; color: var(--text-400);
    text-transform: uppercase; letter-spacing: .5px;
}
.dz-item {
    display: flex; align-items: center; gap: 10px; padding: 7px 9px;
    background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 9px;
    animation: dz-item-in .15s ease;
}
@keyframes dz-item-in { from { opacity:0; transform:translateY(-3px); } to { opacity:1; transform:translateY(0); } }
.dz-item-icon {
    width: 32px; height: 32px; border-radius: 7px; background: var(--bg-elevated); color: var(--text-300);
    display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; overflow: hidden;
}
.dz-item-icon.dz-item-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.dz-item-meta { flex: 1; min-width: 0; }
.dz-item-name {
    font-size: 12.5px; font-weight: 500; color: var(--text-100); text-decoration: none; display: block;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
a.dz-item-name:hover { color: var(--accent, #185FA5); }
.dz-item-size { font-size: 11px; color: var(--text-400); margin-top: 1px; }
.dz-item-remove {
    background: transparent; border: none; color: var(--text-300); cursor: pointer; font-size: 16px;
    line-height: 1; flex-shrink: 0; padding: 4px 6px; border-radius: 6px;
}
.dz-item-remove:hover { background: #FCEBEB; color: #A32D2D; }
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

    // ── File dropzone helpers ───────────────────────────────────────
    const EXT_ICON = {
        pdf: 'ti-file-type-pdf',
        doc: 'ti-file-type-doc', docx: 'ti-file-type-doc',
        xls: 'ti-file-type-xls', xlsx: 'ti-file-type-xls',
    };
    const IMG_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    function humanFileSize(bytes){
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function renderDropzonePreview(dz){
        const input   = dz.querySelector('.dz-input');
        const preview = dz.querySelector('[data-dropzone-preview]');
        if (!input || !preview) return;

        const files = Array.from(input.files || []);
        if (!files.length) { preview.innerHTML = ''; return; }

        let html = `<div class="dz-preview-label">Selected (${files.length})</div>`;
        files.forEach((file, i) => {
            const ext   = (file.name.split('.').pop() || '').toLowerCase();
            const isImg = IMG_EXT.includes(ext);
            const icon  = EXT_ICON[ext] || 'ti-file';
            html += `
                <div class="dz-item">
                    <div class="dz-item-icon ${isImg ? 'dz-item-thumb' : ''}">
                        ${isImg ? `<img src="${URL.createObjectURL(file)}" alt="">` : `<i class="ti ${icon}" aria-hidden="true"></i>`}
                    </div>
                    <div class="dz-item-meta">
                        <span class="dz-item-name">${file.name}</span>
                        <div class="dz-item-size">${humanFileSize(file.size)}</div>
                    </div>
                    <button type="button" class="dz-item-remove" data-dz-remove="${i}" title="Remove">&times;</button>
                </div>`;
        });
        preview.innerHTML = html;
    }

    function removeFileAt(input, index){
        const dt = new DataTransfer();
        Array.from(input.files).forEach((file, i) => { if (i !== index) dt.items.add(file); });
        input.files = dt.files;
    }

    function addFilesToInput(input, fileList){
        const dt = new DataTransfer();
        Array.from(input.files || []).forEach(f => dt.items.add(f));
        Array.from(fileList).forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    document.addEventListener('change', function(e){
        if (e.target.matches('.dz-input')) {
            renderDropzonePreview(e.target.closest('[data-dropzone]'));
        }
    });

    document.addEventListener('dragover', function(e){
        const dz = e.target.closest?.('[data-dropzone]');
        if (!dz) return;
        e.preventDefault();
        dz.classList.add('dragover');
    });

    document.addEventListener('dragleave', function(e){
        const dz = e.target.closest?.('[data-dropzone]');
        if (!dz) return;
        dz.classList.remove('dragover');
    });

    document.addEventListener('drop', function(e){
        const dz = e.target.closest?.('[data-dropzone]');
        if (!dz) return;
        e.preventDefault();
        dz.classList.remove('dragover');
        const input = dz.querySelector('.dz-input');
        if (input && e.dataTransfer?.files?.length) {
            addFilesToInput(input, e.dataTransfer.files);
            renderDropzonePreview(dz);
        }
    });

    document.addEventListener('click', function(e){

        // Open file picker
        const trigger = e.target.closest('[data-dropzone-trigger]');
        if (trigger) {
            trigger.closest('[data-dropzone]')?.querySelector('.dz-input')?.click();
            return;
        }

        // Remove a pending (not-yet-uploaded) file
        const dzRemove = e.target.closest('[data-dz-remove]');
        if (dzRemove) {
            const dz    = dzRemove.closest('[data-dropzone]');
            const input = dz?.querySelector('.dz-input');
            if (input) {
                removeFileAt(input, parseInt(dzRemove.getAttribute('data-dz-remove'), 10));
                renderDropzonePreview(dz);
            }
            return;
        }

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

@php
    $existingStages = old('stages');
    if ($existingStages === null && isset($workOrder) && $workOrder->exists) {
        $existingStages = $workOrder->stages->map(fn ($s) => [
            'name' => $s->name, 'assigned_to' => $s->assigned_to,
        ])->values()->all();
    }
    $existingStages = $existingStages ?: [];
@endphp

<div class="pf-card" style="margin-bottom:14px">
    <div class="pf-section" style="border-bottom:none">
        <div class="pf-sec-head">
            <div class="pf-sec-icon" style="background:var(--purple-dim)">
                <i class="ti ti-route" style="font-size:15px;color:var(--purple)"></i>
            </div>
            <div>
                <div class="pf-sec-title">Production Stages <span style="font-weight:400;color:var(--text-400)">(optional)</span></div>
                <div class="pf-sec-sub">e.g. Cutting → Welding → QC → Packing. Leave empty for a single-step Work Order.</div>
            </div>
        </div>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px" id="stagesTable">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--text-400);border-bottom:1px solid var(--border-subtle);width:40px">#</th>
                        <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--text-400);border-bottom:1px solid var(--border-subtle)">Stage</th>
                        <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--text-400);border-bottom:1px solid var(--border-subtle);width:200px">Assigned To</th>
                        <th style="width:36px;border-bottom:1px solid var(--border-subtle)"></th>
                    </tr>
                </thead>
                <tbody id="stagesBody"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" style="margin-top:10px" onclick="addStageRow()">+ Add Stage</button>
    </div>
</div>

@push('scripts')
<script>
(function(){
const STAGE_STAFF = @json($staff->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values());
const EXISTING_STAGES = @json(collect($existingStages)->values());
let stageIdx = 0;

function staffOptions(sel){
    let o = '<option value="">— Unassigned —</option>';
    STAGE_STAFF.forEach(u => { o += `<option value="${u.id}" ${String(sel)===String(u.id)?'selected':''}>${u.name}</option>`; });
    return o;
}

function renumber(){
    document.querySelectorAll('#stagesBody tr').forEach((tr, i) => {
        tr.querySelector('.stage-num').textContent = i + 1;
    });
}

window.addStageRow = function(name = '', assignedTo = ''){
    const i = stageIdx++;
    const tr = document.createElement('tr');
    tr.id = 'stage_row_' + i;
    tr.innerHTML = `
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle);color:var(--text-400)" class="stage-num"></td>
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle)">
            <input type="text" name="stages[${i}][name]" class="pf-input" style="padding:7px 10px" value="${String(name).replace(/"/g,'&quot;')}" placeholder="Stage name"/>
        </td>
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle)">
            <select name="stages[${i}][assigned_to]" class="pf-input" style="padding:7px 10px">${staffOptions(assignedTo)}</select>
        </td>
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle);text-align:center">
            <button type="button" onclick="document.getElementById('stage_row_${i}').remove(); (window.renumberStages||function(){})();"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:15px" title="Remove">✕</button>
        </td>
    `;
    document.getElementById('stagesBody').appendChild(tr);
    renumber();
};
window.renumberStages = renumber;

EXISTING_STAGES.forEach(s => addStageRow(s.name || '', s.assigned_to || ''));
})();
</script>
@endpush

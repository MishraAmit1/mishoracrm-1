{{--
    Product Search Dropdown — reusable partial
    Include karo: @include('tenant.partials.product-search-js')
    Requires: PRODUCTS constant already defined in the page (keyed by id)
--}}
<style>
.ps-wrap       { position:relative; }
.ps-input      { width:100%; padding:7px 10px 7px 30px; background:var(--bg-input);
                 border:1.5px solid var(--border-default); border-radius:var(--r-sm,7px);
                 color:var(--text-100); font-size:12.5px; outline:none;
                 transition:border-color .15s; font-family:var(--font); }
.ps-input:focus{ border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-dim); }
.ps-icon       { position:absolute; left:8px; top:50%; transform:translateY(-50%);
                 color:var(--text-400); pointer-events:none; font-size:13px; }
.ps-clear      { position:absolute; right:8px; top:50%; transform:translateY(-50%);
                 background:none; border:none; cursor:pointer; color:var(--text-400);
                 font-size:14px; line-height:1; padding:0; display:none; }
.ps-dropdown   { position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:9999;
                 background:var(--bg-surface); border:1.5px solid var(--border-default);
                 border-radius:var(--r-sm,7px); box-shadow:0 8px 24px rgba(0,0,0,.12);
                 max-height:260px; overflow-y:auto; display:none; }
.ps-item       { padding:9px 12px; cursor:pointer; border-bottom:1px solid var(--border-subtle);
                 transition:background .1s; }
.ps-item:last-child { border-bottom:none; }
.ps-item:hover, .ps-item.ps-focused { background:var(--accent-dim); }
.ps-code       { font-family:var(--mono,monospace); font-size:11px; font-weight:700;
                 color:var(--accent); background:var(--accent-dim);
                 padding:1px 6px; border-radius:4px; margin-right:6px; }
.ps-name       { font-size:13px; font-weight:600; color:var(--text-100); }
.ps-meta       { font-size:11.5px; color:var(--text-400); margin-top:2px; }
.ps-empty      { padding:14px 12px; text-align:center; color:var(--text-400); font-size:13px; }
.ps-selected-badge {
    display:none; align-items:center; gap:6px; margin-top:4px;
    padding:4px 8px; background:var(--accent-dim); border-radius:5px;
    font-size:12px; color:var(--accent); font-weight:600;
}
.ps-selected-badge.show { display:flex; }
.ps-remove-sel { background:none; border:none; cursor:pointer; color:var(--accent);
                 font-size:14px; padding:0; line-height:1; margin-left:auto; }
</style>
<script>
(function(){
/* ── Build the searchable product picker for a row ──────────────── */
window.buildProductSearch = function(rowIndex, containerEl) {
    const wrap = document.createElement('div');
    wrap.className = 'ps-wrap';
    wrap.style.cssText = 'margin-bottom:5px';
    wrap.innerHTML = `
        <svg class="ps-icon" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24" style="width:13px;height:13px">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
        </svg>
        <input class="ps-input" type="text"
               placeholder="Search by code or name..."
               autocomplete="off"
               id="ps_input_${rowIndex}"/>
        <button type="button" class="ps-clear" id="ps_clear_${rowIndex}" title="Clear">✕</button>
        <div class="ps-dropdown" id="ps_drop_${rowIndex}"></div>
        <div class="ps-selected-badge" id="ps_badge_${rowIndex}">
            <span id="ps_badge_text_${rowIndex}"></span>
            <button type="button" class="ps-remove-sel"
                    onclick="clearProductSearch(${rowIndex})">✕</button>
        </div>
    `;
    containerEl.prepend(wrap);

    const input    = document.getElementById('ps_input_'+ rowIndex);
    const dropdown = document.getElementById('ps_drop_'+ rowIndex);
    const clearBtn = document.getElementById('ps_clear_'+ rowIndex);
    const badge    = document.getElementById('ps_badge_'+ rowIndex);
    const badgeTxt = document.getElementById('ps_badge_text_'+ rowIndex);

    let focusedIdx = -1;

    function render(q) {
        q = (q || '').toLowerCase().trim();
        const all = Object.values(PRODUCTS);
        const results = q
            ? all.filter(p =>
                (p.product_code || '').toLowerCase().includes(q) ||
                p.name.toLowerCase().includes(q) ||
                (p.description || '').toLowerCase().includes(q)
              )
            : all;

        dropdown.innerHTML = '';
        if (!results.length) {
            dropdown.innerHTML = '<div class="ps-empty">No products found</div>';
        } else {
            results.forEach((p, idx) => {
                const div = document.createElement('div');
                div.className = 'ps-item';
                div.dataset.id = p.id;
                div.dataset.idx = idx;
                div.innerHTML = `
                    <div>
                        ${p.product_code ? `<span class="ps-code">${esc(p.product_code)}</span>` : ''}
                        <span class="ps-name">${esc(p.name)}</span>
                    </div>
                    <div class="ps-meta">
                        ₹${fmt(p.rate)}
                        &nbsp;·&nbsp; GST ${p.tax_percent}%
                        ${p.unit ? '&nbsp;·&nbsp; ' + esc(p.unit) : ''}
                        ${p.hsn ? '&nbsp;·&nbsp; HSN: ' + esc(p.hsn) : ''}
                    </div>
                `;
                div.addEventListener('mousedown', e => {
                    e.preventDefault();
                    selectProduct(p);
                });
                dropdown.appendChild(div);
            });
        }
        focusedIdx = -1;
        dropdown.style.display = 'block';
    }

    function selectProduct(p) {
        /* fill the row fields */
        fillRowFromProduct(rowIndex, p);

        /* show badge, hide input */
        const label = (p.product_code ? '[' + p.product_code + '] ' : '') + p.name;
        badgeTxt.textContent = label;
        badge.classList.add('show');
        input.value = '';
        clearBtn.style.display = 'none';
        dropdown.style.display = 'none';
        wrap.querySelector('.ps-input').style.display = 'none';
    }

    function openDropdown() {
        render(input.value);
    }

    input.addEventListener('input', function() {
        clearBtn.style.display = this.value ? 'block' : 'none';
        render(this.value);
    });

    input.addEventListener('focus', openDropdown);

    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.ps-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusedIdx = Math.min(focusedIdx + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            focusedIdx = Math.max(focusedIdx - 1, 0);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (focusedIdx >= 0) items[focusedIdx].dispatchEvent(new Event('mousedown'));
            return;
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            return;
        }
        items.forEach((el, i) => el.classList.toggle('ps-focused', i === focusedIdx));
        if (focusedIdx >= 0) items[focusedIdx].scrollIntoView({ block:'nearest' });
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        this.style.display = 'none';
        render('');
    });

    document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) dropdown.style.display = 'none';
    }, true);
};

/* ── Clear selection and show input again ───────────────────────── */
window.clearProductSearch = function(i) {
    const badge = document.getElementById('ps_badge_' + i);
    const input = document.getElementById('ps_input_' + i);
    if (badge) badge.classList.remove('show');
    if (input) {
        input.closest('.ps-wrap').querySelector('.ps-input').style.display = '';
        input.focus();
    }
};

/* ── Helpers ─────────────────────────────────────────────────────── */
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmt(n){ return parseFloat(n||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }

})();
</script>

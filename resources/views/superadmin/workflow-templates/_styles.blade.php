<style>
.form-wrap  { max-width: 700px; margin: 0 auto; }
.form-card  { background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--r-lg); padding: 28px; }
.form-card h2 { font-size: 18px; font-weight: 700; color: var(--text-100); margin-bottom: 4px; }

.section-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: var(--text-400); margin-bottom: 14px; padding-bottom: 8px;
    border-bottom: 1px solid var(--border-subtle);
}

/* Feature bullet rows */
.feat-row-wrap  { display: flex; flex-direction: column; gap: 8px; }
.feat-input-row { display: flex; align-items: center; gap: 8px; }
.feat-input-row .form-control { flex: 1; }
.feat-input-row .feat-del {
    width: 32px; height: 32px; border: 1px solid var(--border-subtle);
    border-radius: var(--r-md); background: var(--bg-input);
    color: var(--text-400); cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.feat-input-row .feat-del:hover { border-color: var(--red); color: var(--red); }

/* Toggle switch */
.toggle-wrap { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.toggle-wrap input[type=checkbox] { display: none; }
.toggle-track {
    width: 40px; height: 22px; background: var(--border-subtle);
    border-radius: 100px; position: relative; transition: .2s; flex-shrink: 0;
}
.toggle-track::after {
    content: ''; position: absolute; top: 3px; left: 3px;
    width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: .2s;
}
.toggle-wrap input:checked + .toggle-track { background: var(--accent); }
.toggle-wrap input:checked + .toggle-track::after { transform: translateX(18px); }
.toggle-lbl { font-size: 13px; color: var(--text-300); font-weight: 500; }

/* Color swatches */
.color-pick { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.color-swatch {
    width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
    border: 3px solid transparent; transition: transform .15s, border-color .15s;
}
.color-swatch:hover { transform: scale(1.1); }
.color-swatch.selected { border-color: var(--accent); transform: scale(1.1); }
.color-swatch.blue   { background: #3b82f6; }
.color-swatch.purple { background: #8b5cf6; }
.color-swatch.green  { background: #22c55e; }
.color-swatch.orange { background: #f97316; }
.color-swatch.pink   { background: #ec4899; }
.color-swatch.teal   { background: #14b8a6; }
.color-swatch.yellow { background: #eab308; }

/* Icon picker */
.icon-pick { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px; }
.icon-opt {
    width: 44px; height: 44px; border-radius: var(--r-md);
    border: 2px solid var(--border-subtle);
    background: var(--bg-input); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: border-color .15s, background .15s;
    color: var(--text-300);
}
.icon-opt:hover { border-color: var(--accent); color: var(--accent); }
.icon-opt.selected { border-color: var(--accent); background: var(--accent-dim); color: var(--accent); }
.icon-opt svg { width: 20px; height: 20px; }

/* Add bullet btn */
.btn-add-feat {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; font-size: 13px; font-weight: 600;
    color: var(--text-300); background: var(--bg-input);
    border: 1px dashed var(--border-subtle); border-radius: var(--r-md);
    cursor: pointer; margin-top: 8px;
}
.btn-add-feat:hover { border-color: var(--accent); color: var(--accent); }

/* Form footer */
.form-footer { display: flex; gap: 10px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-subtle); }

/* Back link */
.back-link { display: flex; align-items: center; gap: 6px; color: var(--text-400); font-size: 13px; text-decoration: none; margin-bottom: 18px; }
.back-link:hover { color: var(--text-100); }

/* Form row */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media(max-width: 600px) { .form-row { grid-template-columns: 1fr; } }

/* Form hint */
.form-hint { font-size: 12px; color: var(--text-400); margin-top: 4px; }
</style>

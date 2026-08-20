<style>
.form-wrap  { max-width:700px; margin:0 auto; }
.form-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:28px; }
.form-card h2 { font-size:18px; font-weight:700; color:var(--text-100); margin-bottom:4px; }

.section-title {
    font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:var(--text-400); margin-bottom:14px; padding-bottom:8px;
    border-bottom:1px solid var(--border-subtle);
}

.form-group   { margin-bottom:16px; }
.form-label   { display:block; font-size:13px; font-weight:600; color:var(--text-300); margin-bottom:6px; }
.form-label .req { color:var(--red); margin-left:2px; }
.form-hint    { font-size:12px; color:var(--text-400); margin-top:4px; }
.form-control {
    width:100%; padding:9px 12px; box-sizing:border-box;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100); font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.form-control:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.form-control.is-invalid { border-color:var(--red); }
.invalid-feedback { font-size:12px; color:var(--red); margin-top:4px; }

.form-row   { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:600px) { .form-row,.form-row-3 { grid-template-columns:1fr; } }

/* Price input with prefix */
.input-prefix-wrap { position:relative; display:flex; align-items:center; }
.input-prefix  { position:absolute; left:11px; color:var(--text-400); font-size:14px; font-weight:600; pointer-events:none; }
.input-suffix  { position:absolute; right:11px; color:var(--text-400); font-size:14px; pointer-events:none; }
.with-prefix   { padding-left:26px !important; }

/* Feature rows */
.feat-group { background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:var(--r-sm); padding:12px 16px; margin-bottom:8px; }
.feat-row   { display:flex; align-items:center; justify-content:space-between; gap:16px; }
.feat-label-col .feat-name { font-size:13.5px; font-weight:600; color:var(--text-100); }
.feat-label-col .feat-sub  { font-size:12px; color:var(--text-400); margin-top:2px; }
.feat-control-col { display:flex; align-items:center; gap:12px; flex-shrink:0; }
.feat-num { width:110px !important; }

/* Toggle switch */
.toggle-wrap { display:flex; align-items:center; gap:8px; cursor:pointer; }
.toggle-wrap input[type=checkbox] { display:none; }
.toggle-track {
    width:40px; height:22px; background:var(--border-subtle);
    border-radius:100px; position:relative; transition:.2s; flex-shrink:0;
}
.toggle-track::after {
    content:''; position:absolute; top:3px; left:3px;
    width:16px; height:16px; background:#fff; border-radius:50%; transition:.2s;
}
.toggle-wrap input:checked + .toggle-track { background:var(--accent); }
.toggle-wrap input:checked + .toggle-track::after { transform:translateX(18px); }
.toggle-lbl { font-size:13px; color:var(--text-300); font-weight:500; }

/* Footer */
.form-footer { display:flex; gap:10px; margin-top:28px; padding-top:20px; border-top:1px solid var(--border-subtle); }

.back-link { display:flex; align-items:center; gap:6px; color:var(--text-400); font-size:13px; text-decoration:none; margin-bottom:18px; }
.back-link:hover { color:var(--text-100); }
</style>

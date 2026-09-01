{{--
    ═══════════════════════════════════════════════════════════════════
    Panel UI — shared dashboard design system
    ───────────────────────────────────────────────────────────────────
    Included by the tenant dashboard, the super-admin platform overview
    and the tenant-detail page. Cards sit directly on the page (no wrapper
    frame) over a soft indigo/violet aurora; surfaces stay neutral and each
    card carries its accent through local custom properties (--k / --ps /
    --qc / --a / --c) so one rule-set paints every hue. Everything resolves
    from the app theme tokens, so it re-skins itself in light and dark.
    ═══════════════════════════════════════════════════════════════════
--}}
<style>
    /* The panel owns the full page width. The shell's 28px gutter is
       tightened so the content grid gets that room back. */
    .page-body:has(.dsh) { padding: 20px 24px 48px; }

    .dsh { position: relative; }

    .dsh::before {
        content: '';
        position: absolute;
        top: -20px; left: -24px; right: -24px;
        height: 300px;
        background:
            radial-gradient(760px 260px at 12% 0%, rgba(99, 120, 255, 0.10), transparent 70%),
            radial-gradient(680px 240px at 92% 0%, rgba(167, 139, 250, 0.08), transparent 70%);
        pointer-events: none;
        z-index: 0;
    }

    .dsh > * { position: relative; z-index: 1; }

    @media(max-width:768px) {
        .page-body:has(.dsh) { padding: 14px 14px 40px; }
    }

    /* ── Page header ────────────────────────────────────────────── */
    .dsh-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding-bottom: 16px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border-subtle);
    }

    .dsh-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: var(--e, var(--accent));
        margin-bottom: 9px;
    }

    .dsh-eyebrow::before {
        content: '';
        width: 6px; height: 6px; border-radius: 50%;
        background: var(--e, var(--accent));
        box-shadow: 0 0 0 3px var(--ew, var(--accent-dim));
    }

    .dsh-title {
        font-size: 26px;
        font-weight: 700;
        letter-spacing: -0.8px;
        color: var(--text-100);
        line-height: 1.12;
    }

    .dsh-sub { font-size: 13.5px; color: var(--text-300); margin-top: 6px; }
    .dsh-acts { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

    .dsh-back {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 600; color: var(--text-400);
        text-decoration: none; margin-bottom: 10px;
    }
    .dsh-back:hover { color: var(--text-200); }
    .dsh-back svg { width: 13px; height: 13px; }

    /* ── Buttons ───────────────────────────────────────────────── */
    .dbtn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-family: var(--font);
        font-size: 13px;
        font-weight: 600;
        padding: 10px 16px;
        border-radius: 11px;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid var(--border-default);
        background: var(--bg-surface);
        color: var(--text-100);
        transition: all 0.18s var(--ease);
        white-space: nowrap;
    }

    .dbtn:hover { border-color: var(--border-strong); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .dbtn svg { width: 16px; height: 16px; }
    .dbtn-sm { padding: 7px 12px; font-size: 12px; border-radius: 9px; }
    .dbtn-sm svg { width: 14px; height: 14px; }

    .dbtn-accent {
        background: var(--accent);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 14px var(--accent-glow);
    }
    .dbtn-accent:hover { background: var(--accent-hover); border-color: transparent; box-shadow: 0 7px 22px var(--accent-glow); }

    .dbtn-danger { color: var(--red); }
    .dbtn-danger:hover { background: var(--red-dim); border-color: var(--red); }

    /* ── Generic card ──────────────────────────────────────────── */
    .dcard {
        background: var(--bg-surface);
        border: 1px solid var(--border-default);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        transition: box-shadow 0.22s var(--ease);
    }
    .dcard:hover { box-shadow: var(--shadow-md); }

    .dcard-h {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px 14px;
    }

    .dcard-ht { display: flex; align-items: center; gap: 10px; min-width: 0; }

    .dcard-ico {
        width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--cw, var(--accent-dim));
        color: var(--c, var(--accent));
    }
    .dcard-ico svg { width: 15px; height: 15px; }

    .dcard-t { font-size: 15px; font-weight: 700; color: var(--text-100); letter-spacing: -0.25px; }
    .dcard-s { font-size: 12.5px; color: var(--text-300); margin-top: 3px; }
    .dcard-b { padding: 0 18px 18px; }
    .dcard-b.tight { padding: 0; }

    .icobtn {
        width: 29px;
        height: 29px;
        border-radius: 9px;
        border: 1px solid var(--border-default);
        background: var(--bg-elevated);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--text-300);
        cursor: pointer;
        text-decoration: none;
        flex-shrink: 0;
        transition: all 0.15s var(--ease);
    }
    .icobtn:hover { color: var(--accent); border-color: var(--accent); background: var(--accent-dim); }
    .icobtn svg { width: 15px; height: 15px; }

    /* ── Grid helpers ──────────────────────────────────────────── */
    .dgrid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .dgrid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .dgrid-side { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 12px; align-items: start; }
    .dgrid-side-l { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 12px; align-items: start; }
    .dstack { display: flex; flex-direction: column; gap: 12px; }
    @media(max-width:1150px) {
        .dgrid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dgrid-side, .dgrid-side-l { grid-template-columns: minmax(0, 1fr); }
    }
    @media(max-width:640px) {
        .dgrid-2, .dgrid-3 { grid-template-columns: minmax(0, 1fr); }
    }

    /* ── Alert chips ───────────────────────────────────────────── */
    .dalerts { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }

    .dalert {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 13px 7px 11px;
        border-radius: 9px;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 500;
        border: 1px solid transparent;
        background: var(--a-bg, var(--bg-surface));
        color: var(--text-200);
        transition: transform 0.15s var(--ease), box-shadow 0.15s var(--ease);
    }
    .dalert:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .dalert svg { width: 15px; height: 15px; flex-shrink: 0; color: var(--a, var(--accent)); }
    .dalert b { font-weight: 700; color: var(--text-100); }

    /* ── KPI cards ─────────────────────────────────────────────── */
    .kgrid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    @media(max-width:1150px) { .kgrid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:600px)  { .kgrid { grid-template-columns: 1fr; } }

    .kcard {
        position: relative;
        background: var(--bg-surface);
        border: 1px solid var(--border-default);
        border-radius: 14px;
        padding: 15px 16px;
        transition: box-shadow 0.2s var(--ease), border-color 0.2s var(--ease);
    }
    .kcard:hover { box-shadow: var(--shadow-sm); border-color: var(--kb, var(--border-strong)); }

    .khead { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }

    .kico {
        width: 27px; height: 27px; border-radius: 8px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--kw, var(--accent-dim));
        color: var(--k, var(--accent));
    }
    .kico svg { width: 15px; height: 15px; }

    .klabel {
        font-size: 11px; font-weight: 700; letter-spacing: 0.7px;
        text-transform: uppercase; color: var(--text-300);
    }
    .kdots { margin-left: auto; color: var(--text-400); display: flex; text-decoration: none; }
    .kdots:hover { color: var(--k, var(--accent)); }
    .kdots svg { width: 17px; height: 17px; }

    .kmid { display: flex; align-items: center; justify-content: space-between; gap: 10px; }

    .knum {
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -1.3px;
        color: var(--text-100);
        line-height: 1;
        font-family: var(--font);
        font-variant-numeric: tabular-nums;
    }
    .knum-sm { font-size: 22px; letter-spacing: -0.6px; }

    .kspark { width: 82px; height: 40px; flex-shrink: 0; }
    .kspark canvas { width: 100% !important; height: 100% !important; }

    .kfoot { display: flex; align-items: center; gap: 8px; margin-top: 12px; }

    .kdelta {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 11.5px; font-weight: 700;
        padding: 3px 9px; border-radius: 20px;
    }
    .kdelta svg { width: 12px; height: 12px; }
    .kdelta.up { color: var(--green); background: var(--green-dim); }
    .kdelta.down { color: var(--red); background: var(--red-dim); }
    .kdelta.flat { color: var(--text-300); background: var(--bg-elevated); }
    .knote { font-size: 12px; color: var(--text-300); }

    /* ── Quick-action / filter chips ───────────────────────────── */
    .qrow { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }

    .qchip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 11px;
        border: 1px solid var(--border-default);
        background: var(--bg-surface);
        color: var(--text-200);
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.18s var(--ease);
    }
    .qchip:hover {
        border-color: var(--qc, var(--accent));
        background: var(--qcw, var(--accent-dim));
        color: var(--text-100);
        transform: translateY(-2px);
    }
    .qchip svg { width: 15px; height: 15px; flex-shrink: 0; color: var(--qc, var(--accent)); }

    .rfil { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rpill {
        font-size: 12px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 10px;
        border: 1px solid var(--border-default);
        background: var(--bg-surface);
        color: var(--text-300);
        cursor: pointer;
        font-family: var(--font);
        transition: all 0.15s var(--ease);
    }
    .rpill:hover { color: var(--text-100); border-color: var(--border-strong); }
    .rpill.on {
        background: var(--accent);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 3px 11px var(--accent-glow);
    }

    /* ── Pipeline stage cards ──────────────────────────────────── */
    .pgrid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 12px; margin-bottom: 12px; align-items: start; }
    @media(max-width:1150px) { .pgrid { grid-template-columns: minmax(0, 1fr); } }

    .pscroll { overflow-x: auto; }

    .prow {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(0, 1fr);
        gap: 10px;
    }
    @media(max-width:1100px) {
        .prow { grid-auto-columns: minmax(150px, 1fr); min-width: min-content; }
    }

    .pcard {
        border: 1px solid var(--border-default);
        border-radius: 13px;
        background: var(--bg-surface);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.18s var(--ease), box-shadow 0.18s var(--ease), border-color 0.18s var(--ease);
    }
    .pcard:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--ps, var(--border-strong)); }

    .phead {
        display: flex; align-items: center; gap: 7px; padding: 10px 11px;
        background: var(--psw, transparent);
        border-bottom: 1px solid var(--border-subtle);
    }
    .pbar { width: 3px; height: 13px; border-radius: 2px; background: var(--ps, var(--accent)); flex-shrink: 0; }
    .pname { font-size: 12.5px; font-weight: 700; color: var(--text-100); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pcount {
        margin-left: auto;
        font-size: 11px;
        font-weight: 700;
        color: var(--ps, var(--accent));
        background: var(--bg-surface);
        border-radius: 7px;
        padding: 2px 8px;
        flex-shrink: 0;
    }

    .pbody { padding: 12px 11px; flex: 1; }
    .pco { font-size: 12px; color: var(--text-300); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pval { font-size: 18px; font-weight: 700; color: var(--text-100); letter-spacing: -0.6px; margin-top: 4px; font-variant-numeric: tabular-nums; }

    .pfoot {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 8px;
        padding: 9px 11px 10px;
        border-top: 1px solid var(--border-subtle);
    }
    .prep-l { font-size: 9.5px; color: var(--text-400); line-height: 1.35; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; white-space: nowrap; }
    .prep-n { font-size: 11.5px; font-weight: 600; color: var(--text-200); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pspark { width: 58px; height: 26px; flex-shrink: 0; }
    .pspark canvas { width: 100% !important; height: 100% !important; }

    .drev { height: 172px; position: relative; }
    .drev canvas { width: 100% !important; height: 100% !important; }
    .dchart { position: relative; height: 210px; }
    .dchart canvas { width: 100% !important; height: 100% !important; }

    /* ── Data table ────────────────────────────────────────────── */
    .rtable { width: 100%; border-collapse: collapse; }

    .rtable th {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-400);
        text-align: left;
        padding: 11px 12px;
        border-bottom: 1px solid var(--border-subtle);
        white-space: nowrap;
    }

    .rtable td {
        padding: 12px;
        font-size: 13px;
        color: var(--text-200);
        border-bottom: 1px solid var(--border-subtle);
        vertical-align: middle;
    }
    .rtable tbody tr:last-child td { border-bottom: none; }
    .rtable tbody tr td { transition: background 0.15s var(--ease); }
    .rtable tbody tr:hover td { background: var(--accent-dim); }
    .rtable tfoot td {
        padding: 11px 12px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-100);
        border-top: 1px solid var(--border-default);
        background: var(--bg-elevated);
    }
    .rtable .num { text-align: right; font-family: var(--mono); font-variant-numeric: tabular-nums; }
    .rtable.compact th, .rtable.compact td { padding: 9px 12px; }

    .rchk { width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer; display: block; }
    .rname { display: flex; align-items: center; gap: 10px; }
    .rn { font-weight: 600; color: var(--text-100); font-size: 13px; }
    .rsub { font-size: 11.5px; color: var(--text-400); margin-top: 1px; }
    .rmono { font-family: var(--mono); font-size: 12px; color: var(--text-300); }

    .rav {
        width: 28px; height: 28px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 700; color: #fff; flex-shrink: 0;
        box-shadow: 0 2px 7px rgba(0, 0, 0, 0.18);
    }
    .rav-sq { border-radius: 9px; }
    .rav-sm { width: 22px; height: 22px; font-size: 9px; box-shadow: none; }

    .rtag {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px 4px 7px; border-radius: 7px;
        font-size: 11.5px; font-weight: 600; white-space: nowrap;
    }
    .rtag i { width: 3px; height: 11px; border-radius: 2px; background: currentColor; flex-shrink: 0; display: block; }

    @media(max-width:900px) {
        .rtable thead { display: none; }
        .rtable, .rtable tbody, .rtable tr { display: block; width: 100%; }
        .rtable tr { border: 1px solid var(--border-default); border-radius: 10px; margin-bottom: 10px; }
        .rtable td { display: flex; align-items: center; justify-content: space-between; gap: 12px; text-align: right; }
        .rtable td::before {
            content: attr(data-label);
            font-size: 11.5px; color: var(--text-400); font-weight: 500;
            text-align: left; flex-shrink: 0;
        }
        .rtable td.rtd-chk { display: none; }
    }

    /* ── Status pill ───────────────────────────────────────────── */
    .dpill {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 700;
        padding: 3px 9px; border-radius: 20px;
        background: var(--pw, var(--bg-elevated));
        color: var(--pc, var(--text-300));
        white-space: nowrap;
    }
    .dpill::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
    .dpill.on     { --pc: var(--green);  --pw: var(--green-dim); }
    .dpill.warn   { --pc: var(--amber);  --pw: var(--amber-dim); }
    .dpill.off    { --pc: var(--red);    --pw: var(--red-dim); }
    .dpill.info   { --pc: var(--accent); --pw: var(--accent-dim); }
    .dpill.purple { --pc: var(--purple); --pw: var(--purple-dim); }
    .dpill.muted  { --pc: var(--text-400); --pw: var(--border-subtle); }

    /* ── Key / value rows ──────────────────────────────────────── */
    .dkv { display: flex; flex-direction: column; }
    .dkv-row {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
        padding: 10px 0; border-bottom: 1px solid var(--border-subtle); font-size: 13px;
    }
    .dkv-row:last-child { border-bottom: none; }
    .dkv-row:first-child { padding-top: 0; }
    .dkv-k { color: var(--text-300); flex-shrink: 0; }
    .dkv-v { color: var(--text-100); font-weight: 600; text-align: right; word-break: break-word; }
    .dkv-v.mono { font-family: var(--mono); font-weight: 500; }

    /* ── List rows (payments, alerts, feed) ────────────────────── */
    .dlist { display: flex; flex-direction: column; }
    .dlist-row {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 18px; border-bottom: 1px solid var(--border-subtle);
        transition: background 0.15s var(--ease); text-decoration: none;
    }
    .dlist-row:last-child { border-bottom: none; }
    .dlist-row:hover { background: var(--bg-elevated); }
    .dlist-ico {
        width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--liw, var(--accent-dim));
        color: var(--li, var(--accent));
    }
    .dlist-ico svg { width: 15px; height: 15px; }
    .dlist-main { flex: 1; min-width: 0; }
    .dlist-t { font-size: 13px; font-weight: 600; color: var(--text-100); }
    .dlist-s { font-size: 11.5px; color: var(--text-300); margin-top: 2px; font-family: var(--mono); }
    .dlist-amt { font-size: 14px; font-weight: 700; font-family: var(--mono); color: var(--green); flex-shrink: 0; text-align: right; }

    .dempty { padding: 34px 20px; text-align: center; color: var(--text-300); font-size: 13px; }
    .dempty-ico { font-size: 26px; margin-bottom: 8px; }

    /* ── Form fields ───────────────────────────────────────────── */
    .dfield { display: flex; flex-direction: column; gap: 6px; }
    .dfield > span { font-size: 12px; font-weight: 600; color: var(--text-200); }
    .dinput {
        width: 100%;
        font-family: var(--font);
        font-size: 13px;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-default);
        background: var(--bg-elevated);
        color: var(--text-100);
        transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
    }
    .dinput:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .dfield-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .dfield-row > .dfield { flex: 1; min-width: 150px; }

    /* ── Feature / module chips ────────────────────────────────── */
    .fchip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 600;
        padding: 3px 10px; border-radius: 20px;
        border: 1px solid var(--border-default);
        color: var(--text-300);
    }
    .fchip.on  { background: var(--green-dim); color: var(--green); border-color: transparent; }
    .fchip.num { background: var(--accent-dim); color: var(--accent); border-color: transparent; }

    .mgrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    @media(max-width:1000px) { .mgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media(max-width:560px)  { .mgrid { grid-template-columns: minmax(0, 1fr); } }

    .mcard {
        background: var(--bg-elevated);
        border: 1px solid var(--border-subtle);
        border-radius: 13px;
        padding: 15px;
        display: flex; flex-direction: column; gap: 10px;
        min-width: 0;
        transition: border-color 0.18s var(--ease), box-shadow 0.18s var(--ease);
    }
    .mcard:hover { border-color: var(--border-default); box-shadow: var(--shadow-sm); }
    .mcard-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .mcard-head { display: flex; align-items: flex-start; gap: 10px; min-width: 0; flex: 1; }
    .mcard-ico {
        width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--miw, var(--bg-hover)); color: var(--mi, var(--text-400));
    }
    .mcard-ico svg { width: 16px; height: 16px; }
    .mcard-name { font-size: 13px; font-weight: 700; color: var(--text-100); }
    .mcard-desc { font-size: 11px; color: var(--text-300); margin-top: 2px; line-height: 1.4; }
    .mcard-note { font-size: 11px; color: var(--text-300); line-height: 1.5; }
    .mcard-note strong { color: var(--text-100); }
    .mcard-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: auto; }

    /* ── Quota bar ─────────────────────────────────────────────── */
    .dquota-bar { height: 8px; border-radius: 5px; background: var(--border-subtle); overflow: hidden; }
    .dquota-fill { height: 100%; border-radius: 5px; background: var(--green); transition: width 0.4s var(--ease); }
    .dquota-fill.warn { background: var(--amber); }
    .dquota-fill.full { background: var(--red); }

    /* ── Donut legend ──────────────────────────────────────────── */
    .ddonut { display: flex; align-items: center; gap: 18px; }
    .dlegend { display: flex; flex-direction: column; gap: 9px; flex: 1; }
    .dlegend-item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; }
    .dlegend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dlegend-l { color: var(--text-300); flex: 1; }
    .dlegend-v { font-family: var(--mono); font-size: 12px; color: var(--text-100); font-weight: 600; }
</style>

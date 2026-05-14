import './bootstrap';

/**
 * CRM SaaS — Core JS
 * No framework dependencies. Vanilla JS only.
 */

(function () {
    'use strict';

    // ── Sidebar ────────────────────────────────────────────────────
    const sidebar        = document.getElementById('sidebar');
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const mobileMenuBtn  = document.getElementById('mobileMenuBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    const SIDEBAR_COLLAPSED_KEY = 'crm_sidebar_collapsed';

    function initSidebar() {
        if (!sidebar) return;

        // Restore collapsed state
        if (localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1') {
            sidebar.classList.add('is-collapsed');
        }

        // Desktop toggle
        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('is-collapsed');
            const collapsed = sidebar.classList.contains('is-collapsed');
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? '1' : '0');
            feather.replace({ width: 16, height: 16 });
        });

        // Mobile toggle
        mobileMenuBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            sidebarOverlay?.classList.toggle('is-visible');
        });

        // Overlay closes sidebar
        sidebarOverlay?.addEventListener('click', closeMobileSidebar);
    }

    function closeMobileSidebar() {
        sidebar?.classList.remove('is-open');
        sidebarOverlay?.classList.remove('is-visible');
    }

    // ── Dropdowns ──────────────────────────────────────────────────
    function initDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');

        dropdowns.forEach(dropdown => {
            const trigger = dropdown.querySelector('[id$="Btn"], .navbar__profile');
            if (!trigger) return;

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();

                // Close all other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) d.classList.remove('is-open');
                });

                dropdown.classList.toggle('is-open');
            });
        });

        // Close on outside click
        document.addEventListener('click', () => {
            dropdowns.forEach(d => d.classList.remove('is-open'));
        });

        // Escape closes dropdowns
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dropdowns.forEach(d => d.classList.remove('is-open'));
                closeAllModals();
            }
        });
    }

    // ── Modal System ───────────────────────────────────────────────
    const openModals = new Set();

    function openModal(modalId) {
        const backdrop = document.getElementById(modalId);
        if (!backdrop) return;

        backdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        openModals.add(modalId);

        // Re-init feather icons inside modal
        feather.replace({ width: 16, height: 16 });

        // Focus first focusable element
        const focusable = backdrop.querySelector('input, select, textarea, button:not(.modal__close)');
        setTimeout(() => focusable?.focus(), 150);
    }

    function closeModal(modalId) {
        const backdrop = document.getElementById(modalId);
        if (!backdrop) return;

        backdrop.classList.remove('is-open');
        openModals.delete(modalId);

        if (openModals.size === 0) {
            document.body.style.overflow = '';
        }
    }

    function closeAllModals() {
        openModals.forEach(id => closeModal(id));
    }

    function initModals() {
        // Open triggers: data-modal-open="modalId"
        document.addEventListener('click', (e) => {
            const opener = e.target.closest('[data-modal-open]');
            if (opener) {
                e.preventDefault();
                openModal(opener.dataset.modalOpen);
            }

            const closer = e.target.closest('[data-modal-close]');
            if (closer) {
                closeModal(closer.dataset.modalClose);
            }

            // Close on backdrop click
            if (e.target.classList.contains('modal-backdrop')) {
                const backdrop = e.target;
                backdrop.classList.remove('is-open');
                const id = backdrop.id;
                if (id) {
                    openModals.delete(id);
                    if (openModals.size === 0) document.body.style.overflow = '';
                }
            }
        });
    }

    // ── Toast System ───────────────────────────────────────────────
    const toastContainer = document.getElementById('toastContainer');

    function toast(message, type = 'default', duration = 3500) {
        if (!toastContainer) return;

        const el = document.createElement('div');
        el.className = `toast ${type !== 'default' ? `toast--${type}` : ''}`;

        const icons = {
            success: '✓',
            danger:  '✕',
            warning: '⚠',
            default: 'ℹ',
        };

        el.innerHTML = `
            <span style="font-size: 15px; flex-shrink:0;">${icons[type] ?? icons.default}</span>
            <span style="flex:1;">${message}</span>
            <button onclick="this.parentElement.remove()" style="color:rgba(255,255,255,0.7); font-size:18px; line-height:1; flex-shrink:0;">&times;</button>
        `;

        toastContainer.appendChild(el);

        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(100%)';
            el.style.transition = 'opacity 0.3s, transform 0.3s';
            setTimeout(() => el.remove(), 300);
        }, duration);
    }

    // Expose globally
    window.toast = toast;
    window.openModal = openModal;
    window.closeModal = closeModal;

    // ── Table — Select All ─────────────────────────────────────────
    function initTableCheckboxes() {
        document.querySelectorAll('[data-select-all]').forEach(selectAll => {
            const tableId = selectAll.dataset.selectAll;
            const table   = document.getElementById(tableId);
            if (!table) return;

            selectAll.addEventListener('change', () => {
                table.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkActions(table);
            });

            table.addEventListener('change', (e) => {
                if (e.target.type === 'checkbox' && !e.target.dataset.selectAll) {
                    updateBulkActions(table);
                    const all  = table.querySelectorAll('tbody input[type="checkbox"]');
                    const checked = table.querySelectorAll('tbody input[type="checkbox"]:checked');
                    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
                    selectAll.checked = checked.length === all.length;
                }
            });
        });
    }

    function updateBulkActions(table) {
        const checked = table.querySelectorAll('tbody input[type="checkbox"]:checked');
        const bar = document.getElementById('bulkActionsBar');
        const countEl = document.getElementById('selectedCount');
        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (countEl) {
            countEl.textContent = checked.length;
        }
    }

    function getSelectedIds(tableId) {
        const table = document.getElementById(tableId);
        return Array.from(
            table?.querySelectorAll('tbody input[type="checkbox"]:checked') ?? []
        ).map(cb => cb.value).filter(Boolean);
    }

    window.getSelectedIds = getSelectedIds;

    // ── Global Search (keyboard shortcut: Ctrl+K) ──────────────────
    function initGlobalSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }

    // ── Alert auto-dismiss ─────────────────────────────────────────
    function initAlerts() {
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-4px)';
                alert.style.transition = 'opacity 0.4s, transform 0.4s';
                setTimeout(() => alert.remove(), 400);
            }, 5000);
        });
    }

    // ── AJAX Form Submission helper ────────────────────────────────
    window.submitForm = function (formEl, options = {}) {
        const {
            onSuccess = () => {},
            onError   = () => {},
            button    = null,
        } = options;

        const form = typeof formEl === 'string' ? document.getElementById(formEl) : formEl;
        if (!form) return;

        const submitBtn = button || form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML;

        if (submitBtn) {
            submitBtn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin 0.6s linear infinite;display:inline-block;"></span> Saving...</span>';
            submitBtn.disabled = true;
        }

        const data = new FormData(form);

        fetch(form.action, {
            method: form.method.toUpperCase() === 'GET' ? 'POST' : form.method,
            body: data,
            headers: {
                'X-CSRF-TOKEN': APP.csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(async r => {
            const json = await r.json().catch(() => ({}));
            if (r.ok) {
                onSuccess(json);
            } else {
                onError(json);
                if (json.errors) {
                    Object.entries(json.errors).forEach(([field, messages]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-error');
                            const err = input.parentElement.querySelector('.form-error')
                                     || Object.assign(document.createElement('div'), { className: 'form-error' });
                            err.textContent = messages[0];
                            input.parentElement.appendChild(err);
                        }
                    });
                }
            }
        })
        .catch(() => {
            toast('Something went wrong. Please try again.', 'danger');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled  = false;
            }
        });
    };

    // ── Pipeline Stage Bar Animation ───────────────────────────────
    function animatePipelineBars() {
        const fills = document.querySelectorAll('.pipeline-stage__bar-fill');
        fills.forEach(fill => {
            const target = fill.style.width;
            fill.style.width = '0%';
            setTimeout(() => { fill.style.width = target; }, 300);
        });
    }

    // ── Init ───────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initDropdowns();
        initModals();
        initTableCheckboxes();
        initGlobalSearch();
        initAlerts();
        animatePipelineBars();
    });

})();
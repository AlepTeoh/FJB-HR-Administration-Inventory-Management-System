// assets/js/app.js

// ===================== MODAL SYSTEM =====================
function openModal(id) {
    document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    const modal = document.getElementById(id);
    const overlay = document.getElementById('modalOverlay');
    if (modal)   { modal.classList.add('active'); }
    if (overlay) { overlay.classList.add('active'); }

    // Dispatch event for any page-specific handlers
    document.dispatchEvent(new CustomEvent('openModal', { detail: id }));
}

function closeModal() {
    document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    const overlay = document.getElementById('modalOverlay');
    if (overlay) overlay.classList.remove('active');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

// ===================== SIDEBAR TOGGLE (mobile) =====================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// Close sidebar when clicking outside (mobile)
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.menu-toggle');
    if (sidebar && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    }
});

// ===================== TOAST AUTO-DISMISS =====================
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById('mainToast');
    if (toast) {
        setTimeout(() => {
            const container = document.getElementById('toastContainer');
            if (container) {
                container.style.opacity = '0';
                container.style.transition = 'opacity .4s';
                setTimeout(() => container.remove(), 400);
            }
        }, 4000);
    }
});

// ===================== TABLE SEARCH (client-side) =====================
function tableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// ===================== FORM VALIDATION =====================
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        let valid = true;
        this.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#dc2626';
                valid = false;
            } else {
                field.style.borderColor = '';
            }
        });
        if (!valid) {
            e.preventDefault();
            // Show first invalid field
            const first = this.querySelector('[required]:not([value])');
            if (first) first.focus();
        }
    });
});


// ── Nav group dropdown ──────────────────────────────────────────────────────
function toggleNavGroup(groupId) {
    const group    = document.getElementById(groupId);
    const toggle   = group.querySelector('.nav-group-toggle');
    const children = group.querySelector('.nav-group-children');
    const isOpen   = toggle.classList.contains('open');

    toggle.classList.toggle('open', !isOpen);
    children.classList.toggle('open', !isOpen);
}

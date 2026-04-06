const STATUS_CLASS_MAP = {
    pending: 'status-pending',
    under_review: 'status-under-review',
    resolved: 'status-resolved',
    escalated: 'status-escalated',
    closed: 'status-closed',
    submitted: 'status-submitted',
    rejected: 'status-rejected',
};

const PRIORITY_CLASS_MAP = {
    low: 'priority-low',
    medium: 'priority-medium',
    high: 'priority-high',
};

export function getCurrentUser() {
    const cfg = window.GDS_CONFIG?.user ?? null;
    if (cfg?.identifier) {
        return cfg;
    }

    const storedIdentifier = window.localStorage.getItem('gds_user_identifier');
    const storedRole = window.localStorage.getItem('gds_user_role');

    return storedIdentifier
        ? { identifier: storedIdentifier, role: storedRole ?? '' }
        : null;
}

export function pageId() {
    return document.body?.dataset?.page ?? '';
}

export function disputeId() {
    const id = document.body?.dataset?.disputeId;
    if (!id) {
        return null;
    }

    const parsed = Number.parseInt(id, 10);
    return Number.isNaN(parsed) ? null : parsed;
}

export function formatDate(value) {
    if (!value) {
        return '-';
    }

    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) {
        return value;
    }

    return dt.toLocaleString();
}

export function escapeHtml(input) {
    return String(input ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export function statusBadge(status) {
    const text = String(status ?? 'unknown').replaceAll('_', ' ');
    const key = String(status ?? '').toLowerCase();
    const cls = STATUS_CLASS_MAP[key] ?? 'status-submitted';
    return `<span class="status-pill ${cls}">${escapeHtml(text)}</span>`;
}

export function priorityBadge(priority) {
    const key = String(priority ?? 'medium').toLowerCase();
    const cls = PRIORITY_CLASS_MAP[key] ?? 'priority-medium';
    return `<span class="priority-pill ${cls}">${escapeHtml(key)}</span>`;
}

export function showAlert(message, type = 'danger') {
    const el = document.getElementById('globalAlert');
    if (!el) {
        return;
    }

    el.className = `alert alert-${type}`;
    el.textContent = message;
    el.classList.remove('d-none');

    window.setTimeout(() => {
        el.classList.add('d-none');
    }, 4500);
}

export function showToast(message, tone = 'primary') {
    const region = document.getElementById('toastRegion');
    if (!region) {
        return;
    }

    const id = `toast-${Date.now()}`;
    const html = `
        <div id="${id}" class="toast align-items-center text-bg-light border-${tone} mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${escapeHtml(message)}</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    region.insertAdjacentHTML('beforeend', html);
    const toastNode = document.getElementById(id);
    const toast = new window.bootstrap.Toast(toastNode, { delay: 3000 });
    toast.show();

    toastNode.addEventListener('hidden.bs.toast', () => {
        toastNode.remove();
    });
}

export function feedbackText(selector, message, ok = false) {
    const el = document.querySelector(selector);
    if (!el) {
        return;
    }

    el.textContent = message;
    el.classList.toggle('text-success', ok);
    el.classList.toggle('text-danger', !ok);
}

export function emptyState(message, colSpan = 5) {
    return `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">${escapeHtml(message)}</td></tr>`;
}

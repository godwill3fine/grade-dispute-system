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

// Reads the current user from server-rendered config, then falls back to local storage.
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

// Identifies which page module should run for the current Blade view.
export function pageId() {
    return document.body?.dataset?.page ?? '';
}

// Reads the active dispute ID from the body dataset.
export function disputeId() {
    const id = document.body?.dataset?.disputeId;
    if (!id) {
        return null;
    }

    const parsed = Number.parseInt(id, 10);
    return Number.isNaN(parsed) ? null : parsed;
}

// Formats API timestamps for table and timeline display.
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

// Escapes dynamic strings before inserting them into template literals.
export function escapeHtml(input) {
    return String(input ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Builds the reusable status pill HTML.
export function statusBadge(status) {
    const key = String(status ?? '').toLowerCase();
    const text = key === ''
        ? 'Unknown'
        : key.replaceAll('_', ' ').replace(/\b\w/g, (character) => character.toUpperCase());
    const cls = STATUS_CLASS_MAP[key] ?? 'status-submitted';
    return `<span class="status-pill ${cls}">${escapeHtml(text)}</span>`;
}

// Builds the reusable priority pill HTML.
export function priorityBadge(priority) {
    const key = String(priority ?? 'medium').toLowerCase();
    const cls = PRIORITY_CLASS_MAP[key] ?? 'priority-medium';
    return `<span class="priority-pill ${cls}">${escapeHtml(key)}</span>`;
}

// Shows a short inline alert near the top of the app shell.
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

// Shows a Bootstrap toast for non-blocking feedback.
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

// Writes form feedback into a known helper element.
export function feedbackText(selector, message, ok = false) {
    const el = document.querySelector(selector);
    if (!el) {
        return;
    }

    el.textContent = message;
    el.classList.toggle('text-success', ok);
    el.classList.toggle('text-danger', !ok);
}

// Creates a table row for empty states.
export function emptyState(message, colSpan = 5) {
    return `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">${escapeHtml(message)}</td></tr>`;
}

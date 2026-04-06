import { apiGet, apiPost } from './api.js';
import {
    disputeId,
    emptyState,
    escapeHtml,
    feedbackText,
    formatDate,
    getCurrentUser,
    priorityBadge,
    showAlert,
    showToast,
    statusBadge,
} from './dom.js';

let studentDisputeCache = [];
let unitLookupTimer = null;
let studentNotificationCache = [];

function getStudentIdentifier() {
    const user = getCurrentUser();
    return user?.identifier ?? '';
}

async function fetchStudentDisputes() {
    const identifier = getStudentIdentifier();
    if (!identifier) {
        return [];
    }

    const data = await apiGet(`/student/disputes/${identifier}`);
    return Array.isArray(data) ? data : [];
}

function normalizeSearchableText(item) {
    return [
        item.ticket_number,
        item.title,
        item.unit_code,
        item.assigned_to,
        item.status,
    ]
        .map((value) => String(value ?? '').toLowerCase())
        .join(' ');
}

function filterByStatusAndQuery(items, statusValue, queryValue) {
    const normalizedQuery = String(queryValue ?? '').trim().toLowerCase();

    return items.filter((item) => {
        const matchesStatus = statusValue === 'all' || item.status === statusValue;
        if (!matchesStatus) {
            return false;
        }

        if (!normalizedQuery) {
            return true;
        }

        return normalizeSearchableText(item).includes(normalizedQuery);
    });
}

function renderStudentMetricCards(disputes) {
    const metrics = {
        total: disputes.length,
        pending: disputes.filter((d) => d.status === 'pending').length,
        under_review: disputes.filter((d) => d.status === 'under_review').length,
        resolved: disputes.filter((d) => d.status === 'resolved').length,
    };

    Object.entries(metrics).forEach(([key, value]) => {
        const el = document.querySelector(`[data-metric="${key}"]`);
        if (el) {
            el.textContent = String(value);
        }
    });
}

function renderStudentDashboardRows(disputes) {
    const body = document.getElementById('studentRecentDisputes');
    if (!body) {
        return;
    }

    if (!disputes.length) {
        body.innerHTML = emptyState('No disputes found for this filter.', 6);
        return;
    }

    body.innerHTML = disputes
        .slice(0, 8)
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.title ?? '-')}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${priorityBadge(item.priority)}</td>
                    <td>${escapeHtml(formatDate(item.updated_at ?? item.created_at))}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-dark" href="/portal/student/disputes/${item.id}">View</a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function applyStudentDashboardFilter() {
    const statusFilter = document.getElementById('studentDashboardStatusFilter');
    const searchInput = document.getElementById('studentDashboardSearch');

    if (!statusFilter) {
        return;
    }

    const filtered = filterByStatusAndQuery(
        studentDisputeCache,
        statusFilter.value,
        searchInput?.value ?? '',
    );

    renderStudentDashboardRows(filtered);
}

function renderStudentDisputeTable(disputes) {
    const body = document.getElementById('studentDisputesTableBody');
    if (!body) {
        return;
    }

    if (!disputes.length) {
        body.innerHTML = emptyState('No disputes match the selected filter.', 6);
        return;
    }

    body.innerHTML = disputes
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.title ?? '-')}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${escapeHtml(item.assigned_to ?? '-')}</td>
                    <td>${escapeHtml(formatDate(item.updated_at ?? item.created_at))}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-dark" href="/portal/student/disputes/${item.id}">Open</a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function applyStudentQueueFilter() {
    const filter = document.getElementById('studentStatusFilter');
    const search = document.getElementById('studentTicketsSearch');

    if (!filter) {
        return;
    }

    const filtered = filterByStatusAndQuery(
        studentDisputeCache,
        filter.value,
        search?.value ?? '',
    );

    renderStudentDisputeTable(filtered);
}

function toggleStudentDisputeType() {
    const typeSelect = document.getElementById('studentDisputeType');
    const unitFields = document.getElementById('unitFields');
    const generalFields = document.getElementById('generalFields');

    if (!typeSelect || !unitFields || !generalFields) {
        return;
    }

    const setMode = () => {
        const type = typeSelect.value;
        const isUnit = type === 'unit';

        unitFields.classList.toggle('d-none', !isUnit);
        generalFields.classList.toggle('d-none', isUnit);
    };

    setMode();
    typeSelect.addEventListener('change', setMode);
}

async function loadUnitLookup(query) {
    const datalist = document.getElementById('unitCodeList');
    if (!datalist) {
        return;
    }

    if (!query || query.length < 2) {
        datalist.innerHTML = '';
        return;
    }

    try {
        const data = await apiGet(`/units/search?q=${encodeURIComponent(query)}`);
        const items = Array.isArray(data) ? data : [];

        datalist.innerHTML = items
            .map((unit) => `<option value="${escapeHtml(unit.unit_code)}">${escapeHtml(unit.unit_name ?? '')}</option>`)
            .join('');
    } catch {
        datalist.innerHTML = '';
    }
}

function initUnitLookupField() {
    const unitInput = document.getElementById('unitCodeInput');
    if (!unitInput) {
        return;
    }

    unitInput.addEventListener('input', () => {
        if (unitLookupTimer) {
            window.clearTimeout(unitLookupTimer);
        }

        unitLookupTimer = window.setTimeout(() => {
            loadUnitLookup(unitInput.value.trim());
        }, 260);
    });
}

async function submitStudentDisputeForm(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);
    const type = String(formData.get('type') ?? 'unit');

    formData.set('student_id', getStudentIdentifier());

    if (type === 'unit') {
        formData.delete('assigned_role');
    } else {
        formData.delete('unit_code');
        formData.delete('course');
    }

    try {
        await apiPost('/disputes', formData);
        feedbackText('#studentDisputeFormFeedback', 'Dispute submitted successfully.', true);
        form.reset();
        toggleStudentDisputeType();
        showToast('Dispute submitted', 'success');
    } catch (error) {
        feedbackText('#studentDisputeFormFeedback', error.message ?? 'Unable to submit dispute.');
    }
}

function renderStudentDetail(dispute) {
    const overview = document.getElementById('studentDisputeOverview');
    if (!overview) {
        return;
    }

    if (!dispute) {
        overview.innerHTML = '<div class="gd-empty">Dispute not found for this user.</div>';
        return;
    }

    overview.innerHTML = `
        <dl class="row mb-0">
            <dt class="col-sm-5">Ticket</dt><dd class="col-sm-7">${escapeHtml(dispute.ticket_number ?? '-')}</dd>
            <dt class="col-sm-5">Status</dt><dd class="col-sm-7">${statusBadge(dispute.status)}</dd>
            <dt class="col-sm-5">Priority</dt><dd class="col-sm-7">${priorityBadge(dispute.priority)}</dd>
            <dt class="col-sm-5">Assigned To</dt><dd class="col-sm-7">${escapeHtml(dispute.assigned_to ?? '-')}</dd>
            <dt class="col-sm-5">Type</dt><dd class="col-sm-7">${escapeHtml(dispute.type ?? '-')}</dd>
            <dt class="col-sm-5">Title</dt><dd class="col-sm-7">${escapeHtml(dispute.title ?? '-')}</dd>
            <dt class="col-sm-5">Description</dt><dd class="col-sm-7">${escapeHtml(dispute.description ?? '-')}</dd>
            <dt class="col-sm-5">Created</dt><dd class="col-sm-7">${escapeHtml(formatDate(dispute.created_at))}</dd>
        </dl>
    `;
}

function renderTimeline(logs, targetId) {
    const timeline = document.getElementById(targetId);
    if (!timeline) {
        return;
    }

    if (!Array.isArray(logs) || !logs.length) {
        timeline.innerHTML = '<div class="gd-empty">No timeline activity available.</div>';
        return;
    }

    timeline.innerHTML = logs
        .map((log) => {
            return `
                <article class="gd-timeline-item">
                    <span class="gd-timeline-dot"></span>
                    <div class="gd-timeline-card">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <strong>${escapeHtml(log.action ?? 'Activity')}</strong>
                            <small class="text-muted">${escapeHtml(formatDate(log.created_at))}</small>
                        </div>
                        <p class="mb-1 text-muted small">By: ${escapeHtml(log.performed_by ?? '-')}</p>
                        <p class="mb-0">${escapeHtml(log.comment ?? '')}</p>
                    </div>
                </article>
            `;
        })
        .join('');
}

function renderAttachments(items, targetId) {
    const list = document.getElementById(targetId);
    if (!list) {
        return;
    }

    if (!Array.isArray(items) || !items.length) {
        list.innerHTML = '<div class="list-group-item text-muted">No attachments uploaded.</div>';
        return;
    }

    list.innerHTML = items
        .map((item) => {
            return `
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="/attachment/download/${item.id}">
                    <span>Attachment #${escapeHtml(item.id)}</span>
                    <i class="bi bi-download"></i>
                </a>
            `;
        })
        .join('');
}

function renderStudentNotifications(items) {
    const feed = document.getElementById('studentNotificationFeed');
    if (!feed) {
        return;
    }

    if (!Array.isArray(items) || !items.length) {
        feed.innerHTML = '<div class="gd-notification-row text-muted">No notifications yet.</div>';
        return;
    }

    feed.innerHTML = items
        .map((item) => {
            const rowClass = item.is_read ? '' : 'unread';
            return `
                <button
                    type="button"
                    class="gd-notification-row ${rowClass}"
                    data-student-notification-id="${escapeHtml(item.id)}"
                >
                    <span class="gd-notification-icon"><i class="bi bi-bell"></i></span>
                    <span class="gd-notification-content">
                        <strong>${escapeHtml(item.title ?? 'Notification')}</strong>
                        <span>${escapeHtml(item.message ?? '')}</span>
                    </span>
                    <span class="gd-notification-meta">${escapeHtml(formatDate(item.created_at))}</span>
                </button>
            `;
        })
        .join('');
}

async function fetchStudentNotifications() {
    const identifier = getStudentIdentifier();
    if (!identifier) {
        return [];
    }

    const data = await apiGet(`/notifications/${identifier}`);
    return Array.isArray(data) ? data : [];
}

async function markNotificationAsRead(id) {
    await apiPost(`/notifications/read/${id}`);
}

function registerStudentNotificationClicks() {
    const feed = document.getElementById('studentNotificationFeed');
    if (!feed) {
        return;
    }

    feed.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-student-notification-id]');
        if (!button) {
            return;
        }

        const id = button.getAttribute('data-student-notification-id');
        if (!id) {
            return;
        }

        try {
            await markNotificationAsRead(id);
            studentNotificationCache = studentNotificationCache.map((item) => {
                if (String(item.id) === String(id)) {
                    return { ...item, is_read: true };
                }

                return item;
            });
            renderStudentNotifications(studentNotificationCache);
            showToast('Notification marked as read', 'success');
        } catch {
            showToast('Unable to mark notification as read', 'danger');
        }
    });
}

function registerMarkAllAsRead() {
    const button = document.getElementById('studentMarkAllRead');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        const unread = studentNotificationCache.filter((item) => !item.is_read);
        if (!unread.length) {
            showToast('All notifications are already read.', 'success');
            return;
        }

        button.disabled = true;

        try {
            await Promise.all(unread.map((item) => markNotificationAsRead(item.id)));
            studentNotificationCache = studentNotificationCache.map((item) => ({ ...item, is_read: true }));
            renderStudentNotifications(studentNotificationCache);
            showToast('All notifications marked as read', 'success');
        } catch {
            showToast('Unable to mark all notifications as read', 'danger');
        } finally {
            button.disabled = false;
        }
    });
}

export async function initStudentDashboard() {
    const statusFilter = document.getElementById('studentDashboardStatusFilter');
    const searchInput = document.getElementById('studentDashboardSearch');

    statusFilter?.addEventListener('change', applyStudentDashboardFilter);
    searchInput?.addEventListener('input', applyStudentDashboardFilter);

    try {
        studentDisputeCache = await fetchStudentDisputes();
        renderStudentMetricCards(studentDisputeCache);
        applyStudentDashboardFilter();
    } catch {
        showAlert('Unable to load student dashboard data.');
    }
}

export function initStudentSubmitDispute() {
    toggleStudentDisputeType();
    initUnitLookupField();

    const form = document.getElementById('studentDisputeForm');
    form?.addEventListener('submit', submitStudentDisputeForm);
}

export async function initStudentTickets() {
    const filter = document.getElementById('studentStatusFilter');
    const search = document.getElementById('studentTicketsSearch');

    filter?.addEventListener('change', applyStudentQueueFilter);
    search?.addEventListener('input', applyStudentQueueFilter);

    try {
        studentDisputeCache = await fetchStudentDisputes();
        applyStudentQueueFilter();
    } catch {
        showAlert('Unable to load dispute tickets.');
    }
}

export async function initStudentNotifications() {
    registerStudentNotificationClicks();
    registerMarkAllAsRead();

    try {
        studentNotificationCache = await fetchStudentNotifications();
        renderStudentNotifications(studentNotificationCache);
    } catch {
        showAlert('Unable to load notifications.');
        renderStudentNotifications([]);
    }
}

export async function initStudentDisputeDetail() {
    const id = disputeId();
    const identifier = getStudentIdentifier();

    if (!id || !identifier) {
        showAlert('Invalid dispute context.');
        return;
    }

    try {
        const allDisputes = await apiGet(`/student/disputes/${identifier}`);
        const dispute = Array.isArray(allDisputes)
            ? allDisputes.find((item) => Number(item.id) === id)
            : null;

        renderStudentDetail(dispute);

        const [timeline, attachments] = await Promise.all([
            apiGet(`/dispute/${id}/timeline`),
            apiGet(`/dispute/attachments/${id}`),
        ]);

        renderTimeline(timeline, 'studentTimeline');
        renderAttachments(attachments, 'studentAttachmentList');
    } catch {
        showAlert('Unable to load dispute details.');
    }
}

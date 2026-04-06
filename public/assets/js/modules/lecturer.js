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

let lecturerDisputeCache = [];

function lecturerIdentifier() {
    return getCurrentUser()?.identifier ?? '';
}

async function fetchLecturerDisputes() {
    const identifier = lecturerIdentifier();
    if (!identifier) {
        return [];
    }

    const data = await apiGet(`/lecturer/disputes/${identifier}`);
    return Array.isArray(data) ? data : [];
}

function renderLecturerMetrics(disputes, alerts) {
    const metrics = {
        assigned: disputes.length,
        pending: disputes.filter((d) => d.status === 'pending').length,
        under_review: disputes.filter((d) => d.status === 'under_review').length,
        overdue: alerts?.overdue_disputes ?? 0,
    };

    Object.entries(metrics).forEach(([key, value]) => {
        const el = document.querySelector(`[data-metric="${key}"]`);
        if (el) {
            el.textContent = String(value);
        }
    });
}

function renderLecturerRecent(disputes) {
    const body = document.getElementById('lecturerRecentDisputes');
    if (!body) {
        return;
    }

    if (!disputes.length) {
        body.innerHTML = emptyState('No assigned disputes yet.', 5);
        return;
    }

    body.innerHTML = disputes
        .slice(0, 6)
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.student_id ?? '-')}</td>
                    <td>${escapeHtml(item.title ?? '-')}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${escapeHtml(formatDate(item.updated_at))}</td>
                </tr>
            `;
        })
        .join('');
}

function renderLecturerQueue(disputes) {
    const body = document.getElementById('lecturerDisputesTableBody');
    if (!body) {
        return;
    }

    if (!disputes.length) {
        body.innerHTML = emptyState('No disputes available.', 7);
        return;
    }

    body.innerHTML = disputes
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.student_id ?? '-')}</td>
                    <td>${escapeHtml(item.title ?? '-')}</td>
                    <td>${priorityBadge(item.priority)}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${escapeHtml(formatDate(item.updated_at))}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="/portal/lecturer/disputes/${item.id}">Review</a></td>
                </tr>
            `;
        })
        .join('');
}

function applyLecturerFilter() {
    const filter = document.getElementById('lecturerStatusFilter');
    if (!filter) {
        return;
    }

    if (filter.value === 'all') {
        renderLecturerQueue(lecturerDisputeCache);
        return;
    }

    renderLecturerQueue(lecturerDisputeCache.filter((item) => item.status === filter.value));
}

function renderOverview(dispute, targetId) {
    const el = document.getElementById(targetId);
    if (!el) {
        return;
    }

    if (!dispute) {
        el.innerHTML = '<div class="gd-empty">Dispute not found for this lecturer.</div>';
        return;
    }

    el.innerHTML = `
        <dl class="row mb-0">
            <dt class="col-sm-5">Ticket</dt><dd class="col-sm-7">${escapeHtml(dispute.ticket_number ?? '-')}</dd>
            <dt class="col-sm-5">Student</dt><dd class="col-sm-7">${escapeHtml(dispute.student_id ?? '-')}</dd>
            <dt class="col-sm-5">Status</dt><dd class="col-sm-7">${statusBadge(dispute.status)}</dd>
            <dt class="col-sm-5">Priority</dt><dd class="col-sm-7">${priorityBadge(dispute.priority)}</dd>
            <dt class="col-sm-5">Title</dt><dd class="col-sm-7">${escapeHtml(dispute.title ?? '-')}</dd>
            <dt class="col-sm-5">Description</dt><dd class="col-sm-7">${escapeHtml(dispute.description ?? '-')}</dd>
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
        list.innerHTML = '<div class="list-group-item text-muted">No attachments available.</div>';
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

async function reloadLecturerDetail(id, dispute) {
    const [timeline, attachments] = await Promise.all([
        apiGet(`/dispute/${id}/timeline`),
        apiGet(`/dispute/attachments/${id}`),
    ]);

    renderOverview(dispute, 'lecturerDisputeOverview');
    renderTimeline(timeline, 'lecturerTimeline');
    renderAttachments(attachments, 'lecturerAttachmentList');
}

async function submitResponse(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const id = disputeId();

    if (!id) {
        return;
    }

    const formData = new FormData(form);
    const payload = {
        dispute_id: id,
        comment: String(formData.get('comment') ?? '').trim(),
    };

    try {
        await apiPost('/dispute/respond', payload);
        feedbackText('#lecturerResponseFeedback', 'Response submitted.', true);
        showToast('Response added', 'success');
        form.reset();

        const all = await fetchLecturerDisputes();
        const dispute = all.find((item) => Number(item.id) === id);
        await reloadLecturerDetail(id, dispute);
    } catch (error) {
        feedbackText('#lecturerResponseFeedback', error.message ?? 'Unable to submit response.');
    }
}

async function submitStatus(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const id = disputeId();

    if (!id) {
        return;
    }

    const formData = new FormData(form);
    const payload = {
        dispute_id: id,
        status: String(formData.get('status') ?? '').trim(),
        comment: String(formData.get('comment') ?? '').trim(),
    };

    try {
        await apiPost('/dispute/status', payload);
        feedbackText('#lecturerStatusFeedback', 'Status updated successfully.', true);
        showToast('Status updated', 'success');

        const all = await fetchLecturerDisputes();
        const dispute = all.find((item) => Number(item.id) === id);
        await reloadLecturerDetail(id, dispute);
    } catch (error) {
        feedbackText('#lecturerStatusFeedback', error.message ?? 'Unable to update status.');
    }
}

export async function initLecturerDashboard() {
    try {
        const identifier = lecturerIdentifier();
        const [disputes, alerts] = await Promise.all([
            fetchLecturerDisputes(),
            apiGet(`/lecturer/alerts/${identifier}`),
        ]);

        renderLecturerMetrics(disputes, alerts);
        renderLecturerRecent(disputes);
    } catch {
        showAlert('Unable to load lecturer dashboard data.');
    }
}

export async function initLecturerDisputes() {
    const filter = document.getElementById('lecturerStatusFilter');
    filter?.addEventListener('change', applyLecturerFilter);

    try {
        lecturerDisputeCache = await fetchLecturerDisputes();
        applyLecturerFilter();
    } catch {
        showAlert('Unable to load lecturer disputes.');
    }
}

export async function initLecturerDisputeDetail() {
    const id = disputeId();
    if (!id) {
        showAlert('Invalid dispute detail context.');
        return;
    }

    const responseForm = document.getElementById('lecturerResponseForm');
    const statusForm = document.getElementById('lecturerStatusForm');
    responseForm?.addEventListener('submit', submitResponse);
    statusForm?.addEventListener('submit', submitStatus);

    try {
        const disputes = await fetchLecturerDisputes();
        const dispute = disputes.find((item) => Number(item.id) === id);
        await reloadLecturerDetail(id, dispute);
    } catch {
        showAlert('Unable to load dispute details.');
    }
}

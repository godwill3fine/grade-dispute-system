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
let lecturerDashboardCourseFilter = 'all';

const ACTIVE_LECTURER_STATUSES = ['pending', 'under_review'];
const LECTURER_HISTORY_STATUSES = ['resolved', 'rejected', 'escalated', 'closed'];

function encodeRouteParam(value) {
    return encodeURIComponent(String(value ?? '').trim());
}

function lecturerIdentifier() {
    return getCurrentUser()?.identifier ?? '';
}

async function fetchLecturerDisputes() {
    const identifier = lecturerIdentifier();
    if (!identifier) {
        return [];
    }

    const data = await apiGet(`/lecturer/disputes/${encodeRouteParam(identifier)}`);
    return Array.isArray(data) ? data : [];
}

function humanizeText(value) {
    return String(value ?? '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function lecturerCourseCode(item) {
    const studentCourse = String(item?.student_course ?? '').trim();
    return studentCourse === '' ? 'profile-missing' : studentCourse;
}

function lecturerCourseLabel(courseCode) {
    return courseCode === 'profile-missing' ? 'Profile Missing' : courseCode;
}

function activeLecturerDisputes(items) {
    return items.filter((item) => ACTIVE_LECTURER_STATUSES.includes(String(item.status ?? '').toLowerCase()));
}

function lecturerHistoryDisputes(items) {
    return items.filter((item) => LECTURER_HISTORY_STATUSES.includes(String(item.status ?? '').toLowerCase()));
}

function filterByCourse(items, courseCode) {
    if (courseCode === 'all') {
        return items;
    }

    return items.filter((item) => lecturerCourseCode(item) === courseCode);
}

function filterByQuery(items, query) {
    const normalizedQuery = String(query ?? '').trim().toLowerCase();
    if (normalizedQuery === '') {
        return items;
    }

    return items.filter((item) => {
        return [
            item.ticket_number,
            item.student_id,
            item.student_name,
            item.student_course,
            item.title,
            item.unit_code,
            item.status,
        ]
            .map((value) => String(value ?? '').toLowerCase())
            .join(' ')
            .includes(normalizedQuery);
    });
}

function updateLecturerHistoryMetrics(disputes) {
    const metrics = {
        total: disputes.length,
        resolved: disputes.filter((item) => item.status === 'resolved').length,
        rejected: disputes.filter((item) => item.status === 'rejected').length,
        escalated: disputes.filter((item) => item.status === 'escalated').length,
    };

    Object.entries(metrics).forEach(([key, value]) => {
        const el = document.querySelector(`[data-history-metric="${key}"]`);
        if (el) {
            el.textContent = String(value);
        }
    });
}

function renderCourseFilters(targetId, disputes, activeCourse) {
    const container = document.getElementById(targetId);
    if (!container) {
        return;
    }

    const courseCodes = [...new Set(disputes.map((item) => lecturerCourseCode(item)).filter((code) => code !== 'general'))]
        .sort((left, right) => left.localeCompare(right));

    const filters = [{ value: 'all', label: 'All Courses' }, ...courseCodes.map((code) => ({
        value: code,
        label: lecturerCourseLabel(code),
    }))];

    container.innerHTML = filters
        .map((filter) => {
            const activeClass = filter.value === activeCourse ? ' active' : '';
            return `<button type="button" class="filter-btn${activeClass}" data-lecturer-course-filter="${escapeHtml(filter.value)}">${escapeHtml(filter.label)}</button>`;
        })
        .join('');
}

function populateHistoryCourseFilter(disputes) {
    const select = document.getElementById('lecturerHistoryCourseFilter');
    if (!select) {
        return;
    }

    const courseCodes = [...new Set(disputes.map((item) => lecturerCourseCode(item)).filter((code) => code !== 'general'))]
        .sort((left, right) => left.localeCompare(right));

    select.innerHTML = [
        '<option value="all">All courses</option>',
        ...courseCodes.map((courseCode) => `<option value="${escapeHtml(courseCode)}">${escapeHtml(lecturerCourseLabel(courseCode))}</option>`),
    ].join('');
}

function csvValue(value) {
    return `"${String(value ?? '').replaceAll('"', '""')}"`;
}

function exportCsv(filename, headers, rows) {
    const csv = [
        headers.map(csvValue).join(','),
        ...rows.map((row) => row.map(csvValue).join(',')),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}

function exportLecturerDashboardData(disputes) {
    if (!disputes.length) {
        showToast('No lecturer queue records to export.', 'warning');
        return;
    }

    exportCsv(
        'lecturer-open-queue.csv',
        ['Ticket', 'Student', 'Title', 'Course', 'Status', 'Updated'],
        disputes.map((item) => [
            item.ticket_number ?? '',
            item.student_id ?? '',
            item.title ?? '',
            lecturerCourseLabel(lecturerCourseCode(item)),
            humanizeText(item.status ?? ''),
            formatDate(item.updated_at),
        ]),
    );
}

function exportLecturerHistoryData(disputes) {
    if (!disputes.length) {
        showToast('No lecturer history records to export.', 'warning');
        return;
    }

    exportCsv(
        'lecturer-review-history.csv',
        ['Ticket', 'Student', 'Course', 'Issue', 'Outcome', 'Date Closed'],
        disputes.map((item) => [
            item.ticket_number ?? '',
            item.student_id ?? '',
            lecturerCourseLabel(lecturerCourseCode(item)),
            item.title ?? '',
            humanizeText(item.status ?? ''),
            formatDate(item.resolved_at ?? item.updated_at),
        ]),
    );
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
        body.innerHTML = emptyState('No assigned disputes yet.', 6);
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
                    <td class="table-action-cell">
                        <a class="btn btn-primary btn-sm" href="/portal/lecturer/disputes/${item.id}">Review</a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function lecturerDashboardItems() {
    const statusFilter = document.getElementById('lecturerDashboardStatusFilter');
    const selectedStatus = statusFilter?.value ?? 'all';

    const filteredByStatus = activeLecturerDisputes(lecturerDisputeCache).filter((item) => {
        return selectedStatus === 'all' || item.status === selectedStatus;
    });

    return filterByCourse(filteredByStatus, lecturerDashboardCourseFilter);
}

function applyLecturerDashboardFilters() {
    renderLecturerRecent(lecturerDashboardItems());
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
                    <td class="table-action-cell">
                        <a class="btn btn-primary btn-sm" href="/portal/lecturer/disputes/${item.id}">Review</a>
                    </td>
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

function renderLecturerHistoryRows(disputes) {
    const body = document.getElementById('lecturerHistoryTableBody');
    if (!body) {
        return;
    }

    if (!disputes.length) {
        body.innerHTML = emptyState('No review history records match this filter.', 6);
        return;
    }

    body.innerHTML = disputes
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.student_id ?? '-')}</td>
                    <td>
                        <div class="stacked-text">
                            <span class="primary-text">${escapeHtml(lecturerCourseLabel(lecturerCourseCode(item)))}</span>
                            <span class="sub-text">${escapeHtml(item.title ?? '-')}</span>
                        </div>
                    </td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${escapeHtml(formatDate(item.resolved_at ?? item.updated_at))}</td>
                    <td class="table-action-cell">
                        <a class="btn btn-primary btn-sm btn-neutral-dark" href="/portal/lecturer/disputes/${item.id}">View Record</a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function updateLecturerHistoryFooter(visibleCount, totalCount) {
    const footer = document.getElementById('lecturerHistoryFooter');
    if (!footer) {
        return;
    }

    footer.textContent = `Showing ${visibleCount} of ${totalCount} records`;
}

function lecturerHistoryItems() {
    const search = document.getElementById('lecturerHistorySearch');
    const outcomeFilter = document.getElementById('lecturerHistoryOutcomeFilter');
    const courseFilter = document.getElementById('lecturerHistoryCourseFilter');

    const outcomeValue = outcomeFilter?.value ?? 'all';
    const courseValue = courseFilter?.value ?? 'all';

    const filteredByOutcome = lecturerHistoryDisputes(lecturerDisputeCache).filter((item) => {
        return outcomeValue === 'all' || item.status === outcomeValue;
    });

    return filterByQuery(
        filterByCourse(filteredByOutcome, courseValue),
        search?.value ?? '',
    );
}

function applyLecturerHistoryFilters() {
    const filtered = lecturerHistoryItems();
    const total = lecturerHistoryDisputes(lecturerDisputeCache).length;

    renderLecturerHistoryRows(filtered);
    updateLecturerHistoryFooter(filtered.length, total);
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
        <div class="overview-grid">
            <div class="overview-label">Ticket</div><div class="overview-value">${escapeHtml(dispute.ticket_number ?? '-')}</div>
            <div class="overview-label">Student</div><div class="overview-value">${escapeHtml(dispute.student_name || dispute.student_id || '-')}</div>
            <div class="overview-label">Registration No.</div><div class="overview-value">${escapeHtml(dispute.student_id ?? '-')}</div>
            <div class="overview-label">Course</div><div class="overview-value">${escapeHtml(lecturerCourseLabel(lecturerCourseCode(dispute)))}</div>
            <div class="overview-label">Unit</div><div class="overview-value">${escapeHtml(dispute.unit_name ? `${dispute.unit_code ?? ''} - ${dispute.unit_name}` : (dispute.unit_code ?? '-'))}</div>
            <div class="overview-label">Status</div><div class="overview-value">${statusBadge(dispute.status)}</div>
            <div class="overview-label">Priority</div><div class="overview-value">${priorityBadge(dispute.priority)}</div>
            <div class="overview-label">Title</div><div class="overview-value">${escapeHtml(dispute.title ?? '-')}</div>
            <div class="overview-label">Description</div><div class="overview-value">${escapeHtml(dispute.description ?? '-')}</div>
        </div>
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

    timeline.innerHTML = `
        <div class="timeline-container">
            ${logs
                .map((log) => {
                    return `
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-box">
                                <div class="timeline-header">
                                    <h4>${escapeHtml(log.action ?? 'Activity')}</h4>
                                    <span class="timeline-date">${escapeHtml(formatDate(log.created_at))}</span>
                                </div>
                                <span class="timeline-author">By: ${escapeHtml(log.performed_by ?? '-')}</span>
                                <p>${escapeHtml(log.comment ?? '')}</p>
                            </div>
                        </div>
                    `;
                })
                .join('')}
        </div>
    `;
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
                <a class="attachment-link" href="/attachment/download/${item.id}">
                    <span>Attachment #${escapeHtml(item.id)}</span>
                    <span class="material-symbols-outlined icon-xs">download</span>
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
    const statusFilter = document.getElementById('lecturerDashboardStatusFilter');
    const courseFilters = document.getElementById('lecturerDashboardCourseFilters');
    const exportButton = document.getElementById('lecturerDashboardExport');

    statusFilter?.addEventListener('change', applyLecturerDashboardFilters);
    courseFilters?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-lecturer-course-filter]');
        if (!button) {
            return;
        }

        lecturerDashboardCourseFilter = button.getAttribute('data-lecturer-course-filter') ?? 'all';
        renderCourseFilters('lecturerDashboardCourseFilters', activeLecturerDisputes(lecturerDisputeCache), lecturerDashboardCourseFilter);
        applyLecturerDashboardFilters();
    });
    exportButton?.addEventListener('click', () => {
        exportLecturerDashboardData(lecturerDashboardItems());
    });

    try {
        const identifier = lecturerIdentifier();
        const [disputes, alerts] = await Promise.all([
            fetchLecturerDisputes(),
            apiGet(`/lecturer/alerts/${encodeRouteParam(identifier)}`),
        ]);

        renderLecturerMetrics(disputes, alerts);
        lecturerDisputeCache = disputes;
        renderCourseFilters('lecturerDashboardCourseFilters', activeLecturerDisputes(disputes), lecturerDashboardCourseFilter);
        applyLecturerDashboardFilters();
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

export async function initLecturerHistory() {
    const search = document.getElementById('lecturerHistorySearch');
    const outcomeFilter = document.getElementById('lecturerHistoryOutcomeFilter');
    const courseFilter = document.getElementById('lecturerHistoryCourseFilter');
    const exportButton = document.getElementById('lecturerHistoryExport');

    search?.addEventListener('input', applyLecturerHistoryFilters);
    outcomeFilter?.addEventListener('change', applyLecturerHistoryFilters);
    courseFilter?.addEventListener('change', applyLecturerHistoryFilters);
    exportButton?.addEventListener('click', () => {
        exportLecturerHistoryData(lecturerHistoryItems());
    });

    try {
        lecturerDisputeCache = await fetchLecturerDisputes();
        const historyItems = lecturerHistoryDisputes(lecturerDisputeCache);
        updateLecturerHistoryMetrics(historyItems);
        populateHistoryCourseFilter(historyItems);
        applyLecturerHistoryFilters();
    } catch {
        showAlert('Unable to load lecturer review history.');
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

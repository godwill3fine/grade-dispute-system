import { apiGet, apiPost } from './api.js';
import {
    emptyState,
    escapeHtml,
    formatDate,
    priorityBadge,
    showAlert,
    showToast,
    statusBadge,
} from './dom.js';

let adminDisputePage = 1;
let adminLastPage = 1;
let adminActivityPage = 1;
let adminActivityLastPage = 1;
let adminUserPage = 1;
let adminUserLastPage = 1;
let adminEscalationCache = [];
let adminActivityCache = [];
let adminDisputeCache = [];
let adminUserCache = [];
let adminDisputeMeta = { departments: [], units: [] };
const ADMIN_DISPUTE_PAGE_SIZE = 7;
const ADMIN_ACTIVITY_PAGE_SIZE = 7;
const ADMIN_USER_PAGE_SIZE = 7;
const MS_PER_DAY = 1000 * 60 * 60 * 24;
const DEFAULT_ADMIN_SETTINGS = {
    standard_submission_window_days: '14',
    final_exam_dispute_window_days: '7',
    auto_close_overdue_pending_tickets: '1',
    escalation_days_hod: '5',
    escalation_days_admin: '6',
    require_grade_adjustment_justification: '1',
};

function maybeRenderChart(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas || !window.Chart) {
        return;
    }

    const existing = window.Chart.getChart(canvas);
    if (existing) {
        existing.destroy();
    }

    // eslint-disable-next-line no-new
    new window.Chart(canvas, config);
}

function setDashboardMetrics(data) {
    Object.entries(data ?? {}).forEach(([key, value]) => {
        const target = document.querySelector(`[data-metric="${key}"]`);
        if (target) {
            target.textContent = String(value ?? 0);
        }
    });
}

function setAlertMetrics(data) {
    Object.entries(data ?? {}).forEach(([key, value]) => {
        const target = document.querySelector(`[data-alert="${key}"]`);
        if (target) {
            target.textContent = String(value ?? 0);
        }
    });
}

function readAdminDisputeMeta() {
    const source = document.getElementById('adminDisputeMeta');
    if (!source) {
        return { departments: [], units: [] };
    }

    try {
        const parsed = JSON.parse(source.textContent ?? '{}');
        return {
            departments: Array.isArray(parsed?.departments) ? parsed.departments : [],
            units: Array.isArray(parsed?.units) ? parsed.units : [],
        };
    } catch {
        return { departments: [], units: [] };
    }
}

function unitMetaMap() {
    return new Map(
        (adminDisputeMeta.units ?? []).map((unit) => [
            String(unit.unit_code ?? '').trim(),
            {
                unitName: String(unit.unit_name ?? '').trim(),
                departmentName: String(unit.department_name ?? '').trim(),
            },
        ]),
    );
}

function formatDateOnly(value) {
    if (!value) {
        return '-';
    }

    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) {
        return value;
    }

    return [
        dt.getFullYear(),
        String(dt.getMonth() + 1).padStart(2, '0'),
        String(dt.getDate()).padStart(2, '0'),
    ].join('-');
}

function issueTypeLabel(item) {
    const raw = String(item?.title ?? item?.type ?? '').trim();
    if (!raw) {
        return 'General Dispute';
    }

    return raw;
}

function priorityRank(priority) {
    return {
        critical: 4,
        high: 3,
        medium: 2,
        low: 1,
    }[String(priority ?? '').toLowerCase()] ?? 0;
}

function priorityBadgeClassic(priority) {
    const key = String(priority ?? 'medium').toLowerCase();
    const label = key ? `${key.charAt(0).toUpperCase()}${key.slice(1)}` : 'Medium';
    return `<span class="badge badge-${escapeHtml(key || 'medium')}">${escapeHtml(label)}</span>`;
}

function statusBadgeClassic(status) {
    const key = String(status ?? 'pending').toLowerCase();
    const classMap = {
        pending: 'badge-pending',
        under_review: 'badge-review',
        resolved: 'badge-resolved',
        escalated: 'badge-escalated',
        escalated_admin: 'badge-escalated',
        rejected: 'badge-rejected',
        closed: 'badge-resolved',
    };
    const labelMap = {
        under_review: 'Under Review',
        escalated_admin: 'Escalated',
    };
    const label = labelMap[key] ?? key.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
    const cls = classMap[key] ?? 'badge-pending';
    return `<span class="badge ${cls}">${escapeHtml(label)}</span>`;
}

function filteredAdminDisputes() {
    const searchValue = String(document.getElementById('adminDisputeSearch')?.value ?? '').trim().toLowerCase();
    const statusValue = String(document.getElementById('adminDisputeStatusFilter')?.value ?? '').trim().toLowerCase();
    const departmentValue = String(document.getElementById('adminDisputeDepartmentFilter')?.value ?? '').trim().toLowerCase();
    const sortValue = String(document.getElementById('adminDisputeSortFilter')?.value ?? 'newest').trim().toLowerCase();
    const units = unitMetaMap();

    const filtered = adminDisputeCache.filter((item) => {
        const unit = units.get(String(item.unit_code ?? '').trim()) ?? { unitName: '', departmentName: '' };
        const status = String(item.status ?? '').toLowerCase();
        const department = String(unit.departmentName ?? '').toLowerCase();

        if (statusValue && status !== statusValue) {
            return false;
        }

        if (departmentValue && department !== departmentValue) {
            return false;
        }

        if (!searchValue) {
            return true;
        }

        return [
            item.ticket_number,
            item.student_id,
            item.unit_code,
            unit.unitName,
            issueTypeLabel(item),
            item.type,
        ]
            .map((value) => String(value ?? '').toLowerCase())
            .join(' ')
            .includes(searchValue);
    });

    const sorted = [...filtered].sort((left, right) => {
        if (sortValue === 'oldest') {
            return new Date(left.created_at ?? 0).getTime() - new Date(right.created_at ?? 0).getTime();
        }

        if (sortValue === 'priority') {
            return priorityRank(right.priority) - priorityRank(left.priority);
        }

        if (sortValue === 'status') {
            return String(left.status ?? '').localeCompare(String(right.status ?? ''));
        }

        return new Date(right.created_at ?? 0).getTime() - new Date(left.created_at ?? 0).getTime();
    });

    return sorted;
}

function renderAdminDisputePagination(totalRows) {
    const container = document.getElementById('adminDisputePaginationControls');
    if (!container) {
        return;
    }

    const totalPages = Math.max(1, Math.ceil(totalRows / ADMIN_DISPUTE_PAGE_SIZE));
    adminLastPage = totalPages;
    adminDisputePage = Math.min(Math.max(adminDisputePage, 1), totalPages);

    const pages = [];
    if (totalPages <= 5) {
        for (let page = 1; page <= totalPages; page += 1) {
            pages.push(page);
        }
    } else if (adminDisputePage <= 3) {
        pages.push(1, 2, 3, 'dots', totalPages);
    } else if (adminDisputePage >= totalPages - 2) {
        pages.push(1, 'dots', totalPages - 2, totalPages - 1, totalPages);
    } else {
        pages.push(1, 'dots', adminDisputePage - 1, adminDisputePage, adminDisputePage + 1, 'dots', totalPages);
    }

    container.innerHTML = [
        `<button class="page-btn" data-page-nav="prev" type="button" ${adminDisputePage === 1 ? 'disabled' : ''}>Previous</button>`,
        ...pages.map((page) => {
            if (page === 'dots') {
                return '<span class="page-dots">...</span>';
            }

            return `
                <button class="page-btn ${page === adminDisputePage ? 'active' : ''}" data-page-number="${page}" type="button">
                    ${page}
                </button>
            `;
        }),
        `<button class="page-btn" data-page-nav="next" type="button" ${adminDisputePage === totalPages ? 'disabled' : ''}>Next</button>`,
    ].join('');
}

function renderAdminDisputePaginationInfo(totalRows) {
    const info = document.getElementById('adminDisputePaginationInfo');
    if (!info) {
        return;
    }

    if (!totalRows) {
        info.textContent = 'Showing 0 to 0 of 0 total tickets';
        return;
    }

    const start = (adminDisputePage - 1) * ADMIN_DISPUTE_PAGE_SIZE + 1;
    const end = Math.min(start + ADMIN_DISPUTE_PAGE_SIZE - 1, totalRows);
    info.textContent = `Showing ${start} to ${end} of ${totalRows} total tickets`;
}

function formatRelativeTime(value) {
    if (!value) {
        return '-';
    }

    const timestamp = new Date(value).getTime();
    if (Number.isNaN(timestamp)) {
        return formatDate(value);
    }

    const elapsedMs = Date.now() - timestamp;
    const elapsedMinutes = Math.max(1, Math.round(elapsedMs / (1000 * 60)));

    if (elapsedMinutes < 60) {
        return `${elapsedMinutes} min ago`;
    }

    const elapsedHours = Math.round(elapsedMinutes / 60);
    if (elapsedHours < 24) {
        return `${elapsedHours} hour${elapsedHours === 1 ? '' : 's'} ago`;
    }

    const elapsedDays = Math.round(elapsedHours / 24);
    if (elapsedDays < 7) {
        return `${elapsedDays} day${elapsedDays === 1 ? '' : 's'} ago`;
    }

    return formatDate(value);
}

function resolutionDaysFor(item) {
    const created = new Date(item?.created_at ?? '').getTime();
    const resolved = new Date(item?.resolved_at ?? item?.updated_at ?? '').getTime();

    if (Number.isNaN(created) || Number.isNaN(resolved) || resolved < created) {
        return null;
    }

    return (resolved - created) / MS_PER_DAY;
}

function dateKey(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function buildTrendSeries(rows, range = 'week') {
    const disputes = Array.isArray(rows) ? rows : [];
    const now = new Date();
    const counts = new Map();
    let labels = [];

    if (range === 'year') {
        labels = Array.from({ length: 12 }, (_, index) => {
            const date = new Date(now.getFullYear(), now.getMonth() - (11 - index), 1);
            const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            counts.set(key, 0);
            return {
                key,
                label: date.toLocaleDateString(undefined, { month: 'short' }),
            };
        });

        disputes.forEach((item) => {
            const created = new Date(item?.created_at ?? '');
            if (Number.isNaN(created.getTime())) {
                return;
            }

            const key = `${created.getFullYear()}-${String(created.getMonth() + 1).padStart(2, '0')}`;
            if (counts.has(key)) {
                counts.set(key, Number(counts.get(key)) + 1);
            }
        });
    } else {
        const days = range === 'month' ? 30 : 7;
        const start = new Date(now);
        start.setHours(0, 0, 0, 0);
        start.setDate(start.getDate() - (days - 1));

        labels = Array.from({ length: days }, (_, index) => {
            const date = new Date(start);
            date.setDate(start.getDate() + index);
            const key = dateKey(date);
            counts.set(key, 0);

            return {
                key,
                label: range === 'week'
                    ? date.toLocaleDateString(undefined, { weekday: 'short' })
                    : date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }),
            };
        });

        disputes.forEach((item) => {
            const created = new Date(item?.created_at ?? '');
            if (Number.isNaN(created.getTime())) {
                return;
            }

            created.setHours(0, 0, 0, 0);
            const key = dateKey(created);
            if (counts.has(key)) {
                counts.set(key, Number(counts.get(key)) + 1);
            }
        });
    }

    return {
        labels: labels.map((entry) => entry.label),
        values: labels.map((entry) => Number(counts.get(entry.key) ?? 0)),
    };
}

function renderAdminTrendChart(rows, range = 'week') {
    const canvas = document.getElementById('adminTrendChart');
    if (!canvas || !window.Chart) {
        return;
    }

    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(45, 98, 221, 0.24)');
    gradient.addColorStop(1, 'rgba(45, 98, 221, 0.02)');

    const series = buildTrendSeries(rows, range);

    maybeRenderChart('adminTrendChart', {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [{
                label: 'Disputes',
                data: series.values,
                borderColor: '#2d62dd',
                backgroundColor: gradient,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2d62dd',
                pointBorderWidth: 2,
                pointHoverRadius: 5,
                pointRadius: range === 'month' ? 2.5 : 4,
                fill: true,
                tension: 0.35,
                borderWidth: 2.5,
            }],
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {
                legend: { display: false },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#7a8799',
                        maxTicksLimit: range === 'month' ? 6 : 12,
                    },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#7a8799',
                    },
                    grid: {
                        color: 'rgba(95, 111, 130, 0.12)',
                    },
                    border: { display: false },
                },
            },
        },
    });
}

function bindAdminTrendTabs(rows) {
    const buttons = document.querySelectorAll('[data-trend-range]');
    if (!buttons.length) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const range = button.dataset.trendRange ?? 'week';
            buttons.forEach((candidate) => {
                candidate.classList.toggle('active', candidate === button);
            });
            renderAdminTrendChart(rows, range);
        });
    });
}

function renderAdminUnitSummary(rows, unitReports) {
    const body = document.getElementById('adminUnitSummaryTableBody');
    if (!body) {
        return;
    }

    const disputes = Array.isArray(rows) ? rows : [];
    const units = Array.isArray(unitReports) ? unitReports : [];

    if (!disputes.length) {
        body.innerHTML = emptyState('No unit summary data available.', 6);
        return;
    }

    const unitNameMap = new Map(
        units.map((item) => [String(item.unit_code ?? '').trim(), String(item.unit_name ?? '').trim()]),
    );
    const summaries = new Map();

    disputes.forEach((item) => {
        const unitCode = String(item.unit_code ?? 'GENERAL').trim() || 'GENERAL';
        const unitName = unitNameMap.get(unitCode) || unitCode || 'General';
        const summary = summaries.get(unitCode) ?? {
            unitCode,
            unitName,
            total: 0,
            pending: 0,
            resolved: 0,
            resolutionDaysTotal: 0,
            resolutionSamples: 0,
        };

        summary.total += 1;

        const status = String(item.status ?? '').toLowerCase();
        if (status === 'resolved') {
            summary.resolved += 1;
            const resolutionDays = resolutionDaysFor(item);
            if (resolutionDays !== null) {
                summary.resolutionDaysTotal += resolutionDays;
                summary.resolutionSamples += 1;
            }
        } else if (!['rejected', 'closed'].includes(status)) {
            summary.pending += 1;
        }

        summaries.set(unitCode, summary);
    });

    const ordered = [...summaries.values()]
        .sort((left, right) => right.total - left.total)
        .slice(0, 4);

    body.innerHTML = ordered
        .map((item) => {
            const avgResolution = item.resolutionSamples
                ? `${(item.resolutionDaysTotal / item.resolutionSamples).toFixed(1)} days`
                : '-';

            return `
                <tr>
                    <td>
                        <div class="stacked-text">
                            <span class="primary-text">${escapeHtml(item.unitName)}</span>
                            <span class="sub-text">${escapeHtml(item.unitCode)}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(item.total)}</td>
                    <td><span class="highlight-box">${escapeHtml(item.pending)}</span></td>
                    <td>${escapeHtml(item.resolved)}</td>
                    <td>${escapeHtml(avgResolution)}</td>
                    <td>
                        <a class="btn btn-outline btn-sm admin-summary-action" href="/portal/admin/disputes">
                            View Details
                        </a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function renderAdminDisputesRows(rows) {
    const body = document.getElementById('adminDisputesTableBody');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No disputes found.', 7);
        return;
    }

    const units = unitMetaMap();

    body.innerHTML = rows
        .map((item) => {
            const unit = units.get(String(item.unit_code ?? '').trim()) ?? { unitName: '', departmentName: '' };
            const courseLabel = String(item.unit_code ?? '').trim() || unit.unitName || 'General';

            return `
                <tr>
                    <td class="cell-strong">${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>
                        <div class="stacked-text">
                            <span class="primary-text">${escapeHtml(item.student_id ?? '-')}</span>
                            <span class="sub-text">${escapeHtml(courseLabel)}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(issueTypeLabel(item))}</td>
                    <td>${priorityBadgeClassic(item.priority)}</td>
                    <td>${statusBadgeClassic(item.status)}</td>
                    <td>${escapeHtml(formatDateOnly(item.created_at))}</td>
                    <td>
                        <button class="btn btn-outline btn-sm admin-manage-ticket-btn" data-dispute-id="${escapeHtml(item.id ?? '')}" data-ticket-id="${escapeHtml(item.ticket_number ?? '')}" type="button">
                            Manage
                        </button>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function setEscalationMetrics(rows) {
    const safeRows = Array.isArray(rows) ? rows : [];
    const metrics = {
        total: safeRows.length,
        high_priority: safeRows.filter((item) => String(item.priority ?? '').toLowerCase() === 'high').length,
        staff_involved: new Set(
            safeRows
                .map((item) => String(item.assigned_to ?? item.assigned_role ?? '').trim())
                .filter(Boolean),
        ).size,
    };

    Object.entries(metrics).forEach(([key, value]) => {
        const target = document.querySelector(`[data-escalation-metric="${key}"]`);
        if (target) {
            target.textContent = String(value);
        }
    });
}

function populateEscalationRoleFilter(rows) {
    const select = document.getElementById('adminEscalationRoleFilter');
    if (!select) {
        return;
    }

    const roles = [...new Set(rows.map((item) => String(item.assigned_role ?? '').trim()).filter(Boolean))]
        .sort((left, right) => left.localeCompare(right));

    select.innerHTML = [
        '<option value="all">All Assignees</option>',
        ...roles.map((role) => {
            const label = role.replaceAll('_', ' ');
            return `<option value="${escapeHtml(role)}">${escapeHtml(label)}</option>`;
        }),
    ].join('');
}

function escalationReasonText(item) {
    const explicitReason = String(item.escalation_reason ?? '').trim();
    if (explicitReason) {
        const autoEscalationMatch = explicitReason.match(/after\s+(\d+\s+day\(s\))/i);
        if (autoEscalationMatch) {
            return `Unresolved after ${autoEscalationMatch[1]}`;
        }

        return explicitReason;
    }

    const status = String(item.status ?? '').toLowerCase();
    if (status === 'escalated_admin' || String(item.assigned_role ?? '').toLowerCase() === 'admin') {
        return 'Pending admin action';
    }

    if (status === 'under_review') {
        return 'Further review required';
    }

    const title = String(item.title ?? '').trim();
    if (title) {
        return title;
    }

    return 'Escalated dispute';
}

function escalationStatusTokens(item) {
    const status = String(item.status ?? '').toLowerCase();
    const assignedRole = String(item.assigned_role ?? '').toLowerCase();

    if (status === 'escalated_admin' || assignedRole === 'admin') {
        return ['PENDING ADMIN', 'ACTION'];
    }

    if (status === 'under_review') {
        return ['UNDER', 'INVESTIGATION'];
    }

    if (assignedRole === 'hod') {
        return ['REQUIRES HOD', 'REVIEW'];
    }

    if (assignedRole === 'exam_officer') {
        return ['EXAM OFFICE', 'REVIEW'];
    }

    if (String(item.priority ?? '').toLowerCase() === 'high') {
        return ['HIGH PRIORITY', 'CASE'];
    }

    return ['ESCALATED', 'REVIEW'];
}

function renderAdminEscalationRows(rows) {
    const body = document.getElementById('adminEscalationsTableBody');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No escalated disputes found.', 6);
        return;
    }

        body.innerHTML = rows
        .map((item) => {
            const assignedTo = String(item.assigned_lecturer_name ?? item.assigned_name ?? item.assigned_to ?? 'Unassigned').trim() || 'Unassigned';
            const reason = escalationReasonText(item);
            const escalatedOn = formatDateOnly(item.escalated_at ?? item.updated_at ?? item.created_at);
            const statusTokens = escalationStatusTokens(item);

            return `
                <tr>
                    <td class="cell-strong">${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>
                        <div class="stacked-text">
                            <span class="primary-text">${escapeHtml(item.student_id ?? '-')}</span>
                            <span class="sub-text">${escapeHtml(item.unit_code ?? 'General')}</span>
                        </div>
                    </td>
                    <td>${escapeHtml(assignedTo)}</td>
                    <td>
                        <div class="stacked-text">
                            <div class="inline-row">
                                <span class="status-dot danger"></span>
                                <span class="primary-text text-medium">${escapeHtml(reason)}</span>
                            </div>
                            <span class="sub-text">Escalated on ${escapeHtml(escalatedOn)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="stacked-badges">
                            ${statusTokens.map((token) => `<span class="badge-box-gold">${escapeHtml(token)}</span>`).join('')}
                        </div>
                    </td>
                    <td>
                        <a class="btn btn-dark" href="/portal/admin/disputes/${encodeURIComponent(item.id ?? '')}">
                            Review <span class="material-symbols-outlined icon-xs">chevron_right</span>
                        </a>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function filterAdminEscalations() {
    const searchInput = document.getElementById('adminEscalationSearch');
    const roleFilter = document.getElementById('adminEscalationRoleFilter');

    const query = String(searchInput?.value ?? '').trim().toLowerCase();
    const role = String(roleFilter?.value ?? 'all').trim().toLowerCase();

    const filtered = adminEscalationCache.filter((item) => {
        const matchesRole = role === 'all' || String(item.assigned_role ?? '').toLowerCase() === role;
        if (!matchesRole) {
            return false;
        }

        if (!query) {
            return true;
        }

        return [
            item.ticket_number,
            item.student_id,
            item.title,
            item.unit_code,
            item.assigned_lecturer_name,
            item.assigned_to,
            item.assigned_role,
            item.status,
            item.priority,
        ]
            .map((value) => String(value ?? '').toLowerCase())
            .join(' ')
            .includes(query);
    });

    renderAdminEscalationRows(filtered);
    return filtered;
}

function exportAdminEscalations(rows) {
    if (!Array.isArray(rows) || !rows.length) {
        showToast('No escalation records to export.', 'warning');
        return;
    }

    const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""')}"`;
    const csv = [
        ['Ticket', 'Student', 'Unit', 'Assigned Lecturer', 'Escalation Reason', 'Current Status', 'Escalated On']
            .map(csvEscape)
            .join(','),
        ...rows.map((item) => [
            item.ticket_number ?? '',
            item.student_id ?? '',
            item.unit_code ?? '',
            item.assigned_lecturer_name ?? item.assigned_name ?? item.assigned_to ?? '',
            escalationReasonText(item),
            escalationStatusTokens(item).join(' / '),
            formatDateOnly(item.escalated_at ?? item.updated_at ?? item.created_at),
        ].map(csvEscape).join(',')),
    ].join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'admin-escalations.csv';
    document.body.append(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

function loadAdminDisputePage(page = 1) {
    adminDisputePage = page;
    const filtered = filteredAdminDisputes();
    renderAdminDisputePagination(filtered.length);
    renderAdminDisputePaginationInfo(filtered.length);

    const startIndex = (adminDisputePage - 1) * ADMIN_DISPUTE_PAGE_SIZE;
    const pageRows = filtered.slice(startIndex, startIndex + ADMIN_DISPUTE_PAGE_SIZE);
    renderAdminDisputesRows(pageRows);
}

function renderUnitReports(rows) {
    const body = document.getElementById('unitReportTable');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No unit report data available.', 7);
        return;
    }

    body.innerHTML = rows
        .map((row) => `
            <tr>
                <td class="cell-strong">${escapeHtml(row.unit_code ?? '-')}</td>
                <td>${escapeHtml(row.unit_name ?? '-')}</td>
                <td>${escapeHtml(row.total ?? 0)}</td>
                <td>${escapeHtml(row.pending ?? 0)}</td>
                <td>${escapeHtml(row.under_review ?? 0)}</td>
                <td>${escapeHtml(row.resolved ?? 0)}</td>
                <td>${escapeHtml(row.escalated ?? 0)}</td>
            </tr>
        `)
        .join('');
}

function lecturerReportName(row) {
    const salutation = String(row?.salutation ?? '').trim();
    const lastName = String(row?.last_name ?? '').trim();
    const firstName = String(row?.first_name ?? '').trim();

    if (salutation && lastName) {
        return `${salutation} ${lastName}`;
    }

    if (lastName) {
        return lastName;
    }

    if (firstName) {
        return firstName;
    }

    return String(row?.assigned_to ?? '-').trim() || '-';
}

function renderLecturerReports(rows) {
    const body = document.getElementById('lecturerReportTable');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No lecturer report data available.', 5);
        return;
    }

    body.innerHTML = rows
        .map((row) => `
            <tr>
                <td>
                    <div class="stacked-text">
                        <span class="primary-text">${escapeHtml(lecturerReportName(row))}</span>
                        <span class="sub-text">${escapeHtml(row.assigned_to ?? '-')}</span>
                    </div>
                </td>
                <td>${escapeHtml(row.total ?? 0)}</td>
                <td>${escapeHtml(row.pending ?? 0)}</td>
                <td>${escapeHtml(row.under_review ?? 0)}</td>
                <td>${escapeHtml(row.resolved ?? 0)}</td>
            </tr>
        `)
        .join('');
}

function formatMonthLabel(value) {
    const source = String(value ?? '').trim();
    if (!source) {
        return '-';
    }

    const [year, month] = source.split('-');
    const numericYear = Number.parseInt(year ?? '', 10);
    const numericMonth = Number.parseInt(month ?? '', 10);

    if (Number.isNaN(numericYear) || Number.isNaN(numericMonth)) {
        return source;
    }

    const date = new Date(numericYear, numericMonth - 1, 1);
    return date.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
}

function settingsLookup(rows) {
    const lookup = new Map();

    if (!Array.isArray(rows)) {
        return lookup;
    }

    rows.forEach((item) => {
        const key = String(item?.setting_key ?? '').trim();
        if (!key) {
            return;
        }

        lookup.set(key, String(item?.setting_value ?? '').trim());
    });

    return lookup;
}

function booleanSettingValue(value, fallback = true) {
    const normalized = String(value ?? '').trim().toLowerCase();
    if (normalized === '') {
        return fallback;
    }

    return !['0', 'false', 'no', 'off'].includes(normalized);
}

function renderAdminSettingsForm(rows) {
    const form = document.getElementById('adminSettingsForm');
    if (!form) {
        return;
    }

    const lookup = settingsLookup(rows);

    form.querySelectorAll('[data-setting-key]').forEach((field) => {
        const key = field.dataset.settingKey;
        if (!key) {
            return;
        }

        const fallback = DEFAULT_ADMIN_SETTINGS[key] ?? '';
        const value = lookup.get(key) ?? fallback;

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
            field.checked = booleanSettingValue(value, booleanSettingValue(fallback, true));
            return;
        }

        field.value = value;
    });
}

function collectAdminSettingsPayload() {
    const form = document.getElementById('adminSettingsForm');
    if (!form) {
        return {};
    }

    const settings = {};

    form.querySelectorAll('[data-setting-key]').forEach((field) => {
        const key = field.dataset.settingKey;
        if (!key) {
            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
            settings[key] = field.checked ? '1' : '0';
            return;
        }

        settings[key] = String(field.value ?? '').trim();
    });

    return settings;
}

function bindAdminSettingsDangerZone() {
    document.getElementById('archiveOldDataBtn')?.addEventListener('click', () => {
        showToast('Archive tools will be added in the next pass.', 'primary');
    });

    document.getElementById('purgeSystemCacheBtn')?.addEventListener('click', () => {
        showToast('Cache purge controls will be added in the next pass.', 'warning');
    });
}

function renderActivity(rows) {
    const body = document.getElementById('adminActivityTableBody');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No activity records found.', 5);
        return;
    }

    body.innerHTML = rows
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(formatDate(item.created_at))}</td>
                    <td>${escapeHtml(item.user?.identifier ?? item.user_id ?? 'system')}</td>
                    <td>${escapeHtml(item.action ?? '-')}</td>
                    <td>${escapeHtml(item.target_type ?? '-')} #${escapeHtml(item.target_id ?? '-')}</td>
                    <td>${escapeHtml(item.description ?? '-')}</td>
                </tr>
            `;
        })
        .join('');
}

function renderAdminActivityPagination(totalRows) {
    const container = document.getElementById('adminActivityPaginationControls');
    if (!container) {
        return;
    }

    const totalPages = Math.max(1, Math.ceil(totalRows / ADMIN_ACTIVITY_PAGE_SIZE));
    adminActivityLastPage = totalPages;
    adminActivityPage = Math.min(Math.max(adminActivityPage, 1), totalPages);

    const pages = [];
    if (totalPages <= 5) {
        for (let page = 1; page <= totalPages; page += 1) {
            pages.push(page);
        }
    } else if (adminActivityPage <= 3) {
        pages.push(1, 2, 3, 'dots', totalPages);
    } else if (adminActivityPage >= totalPages - 2) {
        pages.push(1, 'dots', totalPages - 2, totalPages - 1, totalPages);
    } else {
        pages.push(1, 'dots', adminActivityPage - 1, adminActivityPage, adminActivityPage + 1, 'dots', totalPages);
    }

    container.innerHTML = [
        `<button class="page-btn" data-activity-page-nav="prev" type="button" ${adminActivityPage === 1 ? 'disabled' : ''}>Previous</button>`,
        ...pages.map((page) => {
            if (page === 'dots') {
                return '<span class="page-dots">...</span>';
            }

            return `
                <button class="page-btn ${page === adminActivityPage ? 'active' : ''}" data-activity-page-number="${page}" type="button">
                    ${page}
                </button>
            `;
        }),
        `<button class="page-btn" data-activity-page-nav="next" type="button" ${adminActivityPage === totalPages ? 'disabled' : ''}>Next</button>`,
    ].join('');
}

function renderAdminActivityPaginationInfo(totalRows) {
    const info = document.getElementById('adminActivityPaginationInfo');
    if (!info) {
        return;
    }

    if (!totalRows) {
        info.textContent = 'Showing 0 to 0 of 0 total records';
        return;
    }

    const start = (adminActivityPage - 1) * ADMIN_ACTIVITY_PAGE_SIZE + 1;
    const end = Math.min(start + ADMIN_ACTIVITY_PAGE_SIZE - 1, totalRows);
    info.textContent = `Showing ${start} to ${end} of ${totalRows} total records`;
}

function loadAdminActivityPage(page = 1) {
    const rows = Array.isArray(adminActivityCache) ? adminActivityCache : [];
    const totalPages = Math.max(1, Math.ceil(rows.length / ADMIN_ACTIVITY_PAGE_SIZE));
    adminActivityPage = Math.min(Math.max(page, 1), totalPages);

    const startIndex = (adminActivityPage - 1) * ADMIN_ACTIVITY_PAGE_SIZE;
    const visibleRows = rows.slice(startIndex, startIndex + ADMIN_ACTIVITY_PAGE_SIZE);

    renderActivity(visibleRows);
    renderAdminActivityPaginationInfo(rows.length);
    renderAdminActivityPagination(rows.length);
}

function renderDashboardActivity(rows) {
    const container = document.getElementById('adminRecentActivity');
    if (!container) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        container.innerHTML = `
            <div class="activity-item">
                <div class="activity-title">No recent activity found.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = rows
        .slice(0, 3)
        .map((item) => {
            return `
                <div class="activity-item">
                    <div class="activity-title">${escapeHtml(item.action ?? 'Activity')}</div>
                    <div class="activity-desc">${escapeHtml(item.description ?? 'No description')}</div>
                    <div class="activity-time">${escapeHtml(formatRelativeTime(item.created_at))}</div>
                </div>
            `;
        })
        .join('');
}

export async function initAdminDashboard() {
    try {
        const [metrics, alerts, disputes, units, activity] = await Promise.all([
            apiGet('/admin/dashboard'),
            apiGet('/admin/alerts'),
            apiGet('/admin/disputes'),
            apiGet('/admin/reports/units'),
            apiGet('/admin/activity'),
        ]);

        setDashboardMetrics(metrics);
        setAlertMetrics(alerts);
        renderDashboardActivity(activity);
        renderAdminUnitSummary(disputes, units);
        bindAdminTrendTabs(disputes);
        renderAdminTrendChart(disputes, 'week');
    } catch {
        showAlert('Unable to load admin dashboard analytics.');
    }
}

export async function initAdminDisputes() {
    adminDisputeMeta = readAdminDisputeMeta();

    const searchInput = document.getElementById('adminDisputeSearch');
    const statusFilter = document.getElementById('adminDisputeStatusFilter');
    const departmentFilter = document.getElementById('adminDisputeDepartmentFilter');
    const sortFilter = document.getElementById('adminDisputeSortFilter');
    const exportBtn = document.getElementById('adminDisputesExportBtn');
    const createBtn = document.getElementById('adminCreateTicketBtn');
    const pagination = document.getElementById('adminDisputePaginationControls');
    const tableBody = document.getElementById('adminDisputesTableBody');

    const refresh = () => {
        loadAdminDisputePage(1);
    };

    searchInput?.addEventListener('input', refresh);
    statusFilter?.addEventListener('change', refresh);
    departmentFilter?.addEventListener('change', refresh);
    sortFilter?.addEventListener('change', refresh);

    pagination?.addEventListener('click', (event) => {
        const button = event.target.closest('.page-btn');
        if (!button) {
            return;
        }

        const pageNumber = Number.parseInt(button.dataset.pageNumber ?? '', 10);
        if (!Number.isNaN(pageNumber)) {
            loadAdminDisputePage(pageNumber);
            return;
        }

        const direction = button.dataset.pageNav;
        if (direction === 'prev' && adminDisputePage > 1) {
            loadAdminDisputePage(adminDisputePage - 1);
        }

        if (direction === 'next' && adminDisputePage < adminLastPage) {
            loadAdminDisputePage(adminDisputePage + 1);
        }
    });

    exportBtn?.addEventListener('click', () => {
        const rows = filteredAdminDisputes();
        if (!rows.length) {
            showToast('No ticket records to export.', 'warning');
            return;
        }

        const units = unitMetaMap();
        const csvEscape = (value) => `"${String(value ?? '').replaceAll('"', '""')}"`;
        const csv = [
            ['Ticket ID', 'Student', 'Course', 'Issue Type', 'Priority', 'Status', 'Date Submitted']
                .map(csvEscape)
                .join(','),
            ...rows.map((item) => {
                const unit = units.get(String(item.unit_code ?? '').trim()) ?? { unitName: '' };

                return [
                    item.ticket_number ?? '',
                    item.student_id ?? '',
                    item.unit_code || unit.unitName || '',
                    issueTypeLabel(item),
                    String(item.priority ?? '').replace(/\b\w/g, (char) => char.toUpperCase()),
                    String(item.status ?? '').replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()),
                    formatDateOnly(item.created_at),
                ].map(csvEscape).join(',');
            }),
        ].join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'admin-manage-tickets.csv';
        document.body.append(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    });

    createBtn?.addEventListener('click', () => {
        showToast('Admin ticket creation will be added on the next pass.', 'primary');
    });

    tableBody?.addEventListener('click', (event) => {
        const trigger = event.target.closest('.admin-manage-ticket-btn');
        if (!trigger) {
            return;
        }

        const disputeId = String(trigger.dataset.disputeId ?? '').trim();
        const ticketNumber = trigger.dataset.ticketId ?? 'this ticket';
        if (disputeId !== '') {
            window.location.href = `/portal/admin/disputes/${encodeURIComponent(disputeId)}`;
            return;
        }

        showToast(`Unable to open ${ticketNumber}.`, 'warning');
    });

    try {
        const data = await apiGet('/admin/disputes');
        adminDisputeCache = Array.isArray(data) ? data : [];
        loadAdminDisputePage(1);
    } catch {
        showAlert('Unable to load admin disputes.');
    }
}

function userRoleBadge(role) {
    const normalized = String(role ?? '').toLowerCase();
    const label = normalized ? normalized.toUpperCase() : 'USER';
    const toneClass = normalized === 'admin' ? 'role-dark' : 'role-outline';
    return `<span class="role-badge ${toneClass}">${escapeHtml(label)}</span>`;
}

function userStatusIndicator(status) {
    const normalized = String(status ?? 'active').toLowerCase();
    const inactive = ['inactive', 'disabled', 'suspended'].includes(normalized);
    const label = inactive ? 'Inactive' : 'Active';
    const dotClass = inactive ? 'inactive' : 'active';
    return `<div class="status-indicator"><span class="status-dot ${dotClass}"></span> ${label}</div>`;
}

function filteredAdminUsers() {
    const searchValue = String(document.getElementById('adminUserSearch')?.value ?? '').trim().toLowerCase();
    const roleValue = String(document.getElementById('adminUserRoleFilter')?.value ?? '').trim().toLowerCase();
    const statusValue = String(document.getElementById('adminUserStatusFilter')?.value ?? '').trim().toLowerCase();

    return adminUserCache.filter((item) => {
        const role = String(item.role ?? '').toLowerCase();
        const status = String(item.status ?? '').toLowerCase();

        if (roleValue && role !== roleValue) {
            return false;
        }

        if (statusValue && status !== statusValue) {
            return false;
        }

        if (!searchValue) {
            return true;
        }

        return [
            item.name,
            item.email,
            item.identifier,
            item.department,
            item.role,
        ]
            .map((value) => String(value ?? '').toLowerCase())
            .join(' ')
            .includes(searchValue);
    });
}

function populateAdminUserRoleFilter(rows) {
    const select = document.getElementById('adminUserRoleFilter');
    if (!select) {
        return;
    }

    const baseRoles = ['lecturer', 'student', 'admin'];
    const dynamicRoles = [...new Set((Array.isArray(rows) ? rows : []).map((item) => String(item.role ?? '').toLowerCase()).filter(Boolean))];
    const orderedRoles = [...new Set([...baseRoles, ...dynamicRoles])];
    const currentValue = select.value;

    select.innerHTML = [
        '<option value="">All Roles</option>',
        ...orderedRoles.map((role) => `<option value="${escapeHtml(role)}">${escapeHtml(role.charAt(0).toUpperCase() + role.slice(1))}</option>`),
    ].join('');

    if (orderedRoles.includes(currentValue)) {
        select.value = currentValue;
    }
}

function renderAdminUsers(rows) {
    const body = document.getElementById('adminUsersTableBody');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No users match the selected filters.', 6);
        return;
    }

    body.innerHTML = rows
        .map((item) => {
            const icon = item.role === 'admin' ? 'shield' : 'person';
            const identifier = item.identifier ?? 'ID unavailable';
            const email = item.email ?? '-';
            const department = item.department ?? '-';

            return `
                <tr>
                    <td>
                        <div class="user-profile-cell">
                            <div class="user-avatar"><span class="material-symbols-outlined">${escapeHtml(icon)}</span></div>
                            <div class="user-info">
                                <span class="user-name">${escapeHtml(item.name ?? 'Unknown User')}</span>
                                <span class="user-meta">${escapeHtml(email)}</span>
                                <span class="user-meta user-meta-muted">${escapeHtml(identifier)}</span>
                            </div>
                        </div>
                    </td>
                    <td>${userRoleBadge(item.role)}</td>
                    <td>${escapeHtml(department)}</td>
                    <td>${userStatusIndicator(item.status)}</td>
                    <td>${escapeHtml(formatDateOnly(item.last_login))}</td>
                    <td class="table-action-cell">
                        <div class="action-buttons">
                            <button class="btn-icon admin-user-edit-btn" type="button" data-user-id="${escapeHtml(item.identifier ?? item.id ?? '')}" aria-label="Edit user">
                                <span class="material-symbols-outlined">edit_square</span>
                            </button>
                            <button class="btn-icon admin-user-delete-btn" type="button" data-user-id="${escapeHtml(item.identifier ?? item.id ?? '')}" aria-label="Delete user">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        })
        .join('');
}

function renderAdminUserPagination(totalRows) {
    const container = document.getElementById('adminUsersPaginationControls');
    if (!container) {
        return;
    }

    const totalPages = Math.max(1, Math.ceil(totalRows / ADMIN_USER_PAGE_SIZE));
    adminUserLastPage = totalPages;
    adminUserPage = Math.min(Math.max(adminUserPage, 1), totalPages);

    const pages = [];
    if (totalPages <= 5) {
        for (let page = 1; page <= totalPages; page += 1) {
            pages.push(page);
        }
    } else if (adminUserPage <= 3) {
        pages.push(1, 2, 3, 'dots', totalPages);
    } else if (adminUserPage >= totalPages - 2) {
        pages.push(1, 'dots', totalPages - 2, totalPages - 1, totalPages);
    } else {
        pages.push(1, 'dots', adminUserPage - 1, adminUserPage, adminUserPage + 1, 'dots', totalPages);
    }

    container.innerHTML = [
        `<button class="page-btn" data-user-page-nav="prev" type="button" ${adminUserPage === 1 ? 'disabled' : ''}>Previous</button>`,
        ...pages.map((page) => {
            if (page === 'dots') {
                return '<span class="page-dots">...</span>';
            }

            return `
                <button class="page-btn ${page === adminUserPage ? 'active' : ''}" data-user-page-number="${page}" type="button">
                    ${page}
                </button>
            `;
        }),
        `<button class="page-btn" data-user-page-nav="next" type="button" ${adminUserPage === totalPages ? 'disabled' : ''}>Next</button>`,
    ].join('');
}

function renderAdminUserPaginationInfo(totalRows) {
    const info = document.getElementById('adminUsersPaginationInfo');
    if (!info) {
        return;
    }

    if (!totalRows) {
        info.textContent = 'Showing 0 to 0 of 0 total users';
        return;
    }

    const start = (adminUserPage - 1) * ADMIN_USER_PAGE_SIZE + 1;
    const end = Math.min(start + ADMIN_USER_PAGE_SIZE - 1, totalRows);
    info.textContent = `Showing ${start} to ${end} of ${totalRows} total users`;
}

function loadAdminUserPage(page = 1) {
    const filtered = filteredAdminUsers();
    const totalPages = Math.max(1, Math.ceil(filtered.length / ADMIN_USER_PAGE_SIZE));
    adminUserPage = Math.min(Math.max(page, 1), totalPages);

    const startIndex = (adminUserPage - 1) * ADMIN_USER_PAGE_SIZE;
    const visibleRows = filtered.slice(startIndex, startIndex + ADMIN_USER_PAGE_SIZE);

    renderAdminUsers(visibleRows);
    renderAdminUserPaginationInfo(filtered.length);
    renderAdminUserPagination(filtered.length);
}

export async function initAdminUsers() {
    const searchInput = document.getElementById('adminUserSearch');
    const roleFilter = document.getElementById('adminUserRoleFilter');
    const statusFilter = document.getElementById('adminUserStatusFilter');
    const pagination = document.getElementById('adminUsersPaginationControls');
    const importBtn = document.getElementById('adminUsersImportBtn');
    const addBtn = document.getElementById('adminUsersAddBtn');
    const tableBody = document.getElementById('adminUsersTableBody');

    const refresh = () => {
        loadAdminUserPage(1);
    };

    searchInput?.addEventListener('input', refresh);
    roleFilter?.addEventListener('change', refresh);
    statusFilter?.addEventListener('change', refresh);

    pagination?.addEventListener('click', (event) => {
        const button = event.target.closest('.page-btn');
        if (!button) {
            return;
        }

        const pageNumber = Number.parseInt(button.dataset.userPageNumber ?? '', 10);
        if (!Number.isNaN(pageNumber)) {
            loadAdminUserPage(pageNumber);
            return;
        }

        const direction = button.dataset.userPageNav;
        if (direction === 'prev' && adminUserPage > 1) {
            loadAdminUserPage(adminUserPage - 1);
        }

        if (direction === 'next' && adminUserPage < adminUserLastPage) {
            loadAdminUserPage(adminUserPage + 1);
        }
    });

    importBtn?.addEventListener('click', () => {
        showToast('Bulk user import will be added in the next pass.', 'primary');
    });

    addBtn?.addEventListener('click', () => {
        window.location.href = '/portal/admin/users/create';
    });

    tableBody?.addEventListener('click', (event) => {
        const editTrigger = event.target.closest('.admin-user-edit-btn');
        if (editTrigger) {
            const identifier = editTrigger.dataset.userId ?? 'this user';
            if (identifier) {
                window.location.href = `/portal/admin/users/${encodeURIComponent(identifier)}/edit`;
                return;
            }

            showToast('Unable to determine which user should be edited.', 'warning');
            return;
        }

        const deleteTrigger = event.target.closest('.admin-user-delete-btn');
        if (deleteTrigger) {
            const identifier = deleteTrigger.dataset.userId ?? 'this user';
            showToast(`Deletion controls for ${identifier} will be added next.`, 'warning');
        }
    });

    try {
        const data = await apiGet('/admin/users');
        adminUserCache = Array.isArray(data) ? data : [];
        populateAdminUserRoleFilter(adminUserCache);
        loadAdminUserPage(1);
    } catch {
        showAlert('Unable to load the user directory.');
    }
}

export async function initAdminReports() {
    try {
        const [units, lecturers, monthly, resolution] = await Promise.all([
            apiGet('/admin/reports/units'),
            apiGet('/admin/reports/lecturers'),
            apiGet('/admin/reports/monthly'),
            apiGet('/admin/reports/resolution-time'),
        ]);

        renderUnitReports(units);
        renderLecturerReports(lecturers);

        const resolutionEl = document.getElementById('resolutionDays');
        if (resolutionEl) {
            const avg = Number(resolution?.avg_days ?? 0);
            resolutionEl.textContent = Number.isFinite(avg) ? avg.toFixed(1) : '0.0';
        }

        const chartCanvas = document.getElementById('adminMonthlyReportChart');
        const context = chartCanvas?.getContext('2d');
        let gradient = '#2d62dd';

        if (context) {
            gradient = context.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(45, 98, 221, 0.24)');
            gradient.addColorStop(1, 'rgba(45, 98, 221, 0.03)');
        }

        maybeRenderChart('adminMonthlyReportChart', {
            type: 'line',
            data: {
                labels: (monthly ?? []).map((item) => formatMonthLabel(item.month)),
                datasets: [{
                    label: 'Monthly disputes',
                    data: (monthly ?? []).map((item) => item.total),
                    borderColor: '#2d62dd',
                    backgroundColor: gradient,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#2d62dd',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#7a8799', maxRotation: 0 },
                        border: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#7a8799' },
                        grid: { color: 'rgba(95, 111, 130, 0.12)' },
                        border: { display: false },
                    },
                },
            },
        });
    } catch {
        showAlert('Unable to load report datasets.');
    }
}

export async function initAdminEscalations() {
    const searchInput = document.getElementById('adminEscalationSearch');
    const roleFilter = document.getElementById('adminEscalationRoleFilter');
    const exportBtn = document.getElementById('adminEscalationsExportBtn');

    searchInput?.addEventListener('input', filterAdminEscalations);
    roleFilter?.addEventListener('change', filterAdminEscalations);
    exportBtn?.addEventListener('click', () => {
        exportAdminEscalations(filterAdminEscalations() ?? adminEscalationCache);
    });

    try {
        const data = await apiGet('/admin/disputes/escalated');
        adminEscalationCache = Array.isArray(data) ? data : [];
        setEscalationMetrics(adminEscalationCache);
        populateEscalationRoleFilter(adminEscalationCache);
        filterAdminEscalations();
    } catch {
        showAlert('Unable to load escalated disputes.');
    }
}

export async function initAdminManageTicket() {
    const ticketNumber = String(document.querySelector('.ticket-page-header')?.dataset.ticketNumber ?? 'this ticket').trim() || 'this ticket';
    const printBtn = document.getElementById('adminTicketPrintBtn');
    const resolveBtn = document.getElementById('adminTicketResolveBtn');
    const saveNotesBtn = document.getElementById('adminTicketSaveNotesBtn');

    printBtn?.addEventListener('click', () => {
        showToast(`Print-ready export for ${ticketNumber} will be added next.`, 'primary');
    });

    resolveBtn?.addEventListener('click', () => {
        showToast(`Resolution workflow for ${ticketNumber} is ready for backend wiring next.`, 'success');
    });

    saveNotesBtn?.addEventListener('click', () => {
        showToast(`Internal note saving for ${ticketNumber} will be connected next.`, 'primary');
    });

    document.querySelectorAll('[data-admin-ticket-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const label = String(button.getAttribute('data-admin-ticket-action') ?? 'This action').trim();
            showToast(`${label} for ${ticketNumber} will be connected next.`, 'primary');
        });
    });
}

export async function initAdminSettings() {
    const form = document.getElementById('adminSettingsForm');
    bindAdminSettingsDangerZone();

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const settings = collectAdminSettingsPayload();

        if (!Object.keys(settings).length) {
            showAlert('No settings were available to save.');
            return;
        }

        try {
            await apiPost('/admin/settings/update', { settings });
            showToast('Settings updated', 'success');
        } catch (error) {
            showAlert(error.message ?? 'Unable to save settings.');
        }
    });

    try {
        const settings = await apiGet('/admin/settings');
        renderAdminSettingsForm(settings);
    } catch {
        showAlert('Unable to load settings.');
    }
}

export async function initAdminActivity() {
    const refresh = document.getElementById('refreshActivityBtn');
    const pagination = document.getElementById('adminActivityPaginationControls');

    const load = async () => {
        try {
            const data = await apiGet('/admin/activity');
            adminActivityCache = Array.isArray(data) ? data : [];
            adminActivityPage = 1;
            loadAdminActivityPage(adminActivityPage);
        } catch {
            showAlert('Unable to load recent activity.');
        }
    };

    refresh?.addEventListener('click', load);
    pagination?.addEventListener('click', (event) => {
        const target = event.target.closest('button');
        if (!target) {
            return;
        }

        const pageNumber = Number(target.dataset.activityPageNumber);
        if (Number.isInteger(pageNumber) && pageNumber > 0) {
            loadAdminActivityPage(pageNumber);
            return;
        }

        const direction = target.dataset.activityPageNav;
        if (direction === 'prev' && adminActivityPage > 1) {
            loadAdminActivityPage(adminActivityPage - 1);
        }

        if (direction === 'next' && adminActivityPage < adminActivityLastPage) {
            loadAdminActivityPage(adminActivityPage + 1);
        }
    });
    await load();
}

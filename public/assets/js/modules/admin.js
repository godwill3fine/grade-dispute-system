import { apiGet, apiPost } from './api.js';
import {
    emptyState,
    escapeHtml,
    feedbackText,
    formatDate,
    priorityBadge,
    showAlert,
    showToast,
    statusBadge,
} from './dom.js';

let adminDisputePage = 1;
let adminLastPage = 1;

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

function renderAdminDisputesRows(rows) {
    const body = document.getElementById('adminDisputesTableBody');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No disputes found.', 7);
        return;
    }

    body.innerHTML = rows
        .map((item) => {
            return `
                <tr>
                    <td>${escapeHtml(item.ticket_number ?? '-')}</td>
                    <td>${escapeHtml(item.student_id ?? '-')}</td>
                    <td>${escapeHtml(item.title ?? '-')}</td>
                    <td>${statusBadge(item.status)}</td>
                    <td>${priorityBadge(item.priority)}</td>
                    <td>${escapeHtml(item.assigned_role ?? '-')}</td>
                    <td>${escapeHtml(formatDate(item.created_at))}</td>
                </tr>
            `;
        })
        .join('');
}

async function loadAdminDisputePage(page = 1) {
    const form = document.getElementById('adminDisputeFilterForm');
    if (!form) {
        return;
    }

    const params = new URLSearchParams();
    const formData = new FormData(form);

    formData.forEach((value, key) => {
        const normalized = String(value ?? '').trim();
        if (normalized) {
            params.append(key, normalized);
        }
    });

    const query = params.toString();
    const url = `/admin/disputes/filter?page=${page}${query ? `&${query}` : ''}`;
    const data = await apiGet(url);

    const rows = Array.isArray(data?.data) ? data.data : [];
    renderAdminDisputesRows(rows);

    adminDisputePage = Number(data?.current_page ?? page);
    adminLastPage = Number(data?.last_page ?? page);

    const info = document.getElementById('adminDisputePaginationInfo');
    if (info) {
        info.textContent = `Page ${adminDisputePage} of ${adminLastPage} (${data?.total ?? rows.length} total)`;
    }
}

function renderUnitReports(rows) {
    const body = document.getElementById('unitReportTable');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No unit report data available.', 3);
        return;
    }

    body.innerHTML = rows
        .map((row) => `
            <tr>
                <td>${escapeHtml(row.unit_code ?? '-')}</td>
                <td>${escapeHtml(row.unit_name ?? '-')}</td>
                <td>${escapeHtml(row.total ?? 0)}</td>
            </tr>
        `)
        .join('');
}

function renderLecturerReports(rows) {
    const body = document.getElementById('lecturerReportTable');
    if (!body) {
        return;
    }

    if (!Array.isArray(rows) || !rows.length) {
        body.innerHTML = emptyState('No lecturer report data available.', 2);
        return;
    }

    body.innerHTML = rows
        .map((row) => `
            <tr>
                <td>${escapeHtml(row.assigned_to ?? '-')}</td>
                <td>${escapeHtml(row.total ?? 0)}</td>
            </tr>
        `)
        .join('');
}

function renderSettingsRows(settings) {
    const container = document.getElementById('settingsFieldList');
    if (!container) {
        return;
    }

    if (!Array.isArray(settings) || !settings.length) {
        container.innerHTML = `
            <div class="row g-2 align-items-end setting-row">
                <div class="col-md-5"><label class="form-label">Key</label><input class="form-control" name="setting_key" placeholder="escalation_days_hod"></div>
                <div class="col-md-5"><label class="form-label">Value</label><input class="form-control" name="setting_value" placeholder="3"></div>
                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-setting">Remove</button></div>
            </div>
        `;
        return;
    }

    container.innerHTML = settings
        .map((item) => {
            return `
                <div class="row g-2 align-items-end setting-row">
                    <div class="col-md-5"><label class="form-label">Key</label><input class="form-control" name="setting_key" value="${escapeHtml(item.setting_key ?? '')}"></div>
                    <div class="col-md-5"><label class="form-label">Value</label><input class="form-control" name="setting_value" value="${escapeHtml(item.setting_value ?? '')}"></div>
                    <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-setting">Remove</button></div>
                </div>
            `;
        })
        .join('');
}

function collectSettingsPayload() {
    const rows = Array.from(document.querySelectorAll('.setting-row'));
    const settings = {};

    rows.forEach((row) => {
        const key = row.querySelector('[name="setting_key"]')?.value?.trim();
        const value = row.querySelector('[name="setting_value"]')?.value?.trim();
        if (key) {
            settings[key] = value ?? '';
        }
    });

    return settings;
}

function bindSettingRowRemoval() {
    const container = document.getElementById('settingsFieldList');
    container?.addEventListener('click', (event) => {
        const trigger = event.target.closest('.remove-setting');
        if (!trigger) {
            return;
        }

        const row = trigger.closest('.setting-row');
        row?.remove();
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

export async function initAdminDashboard() {
    try {
        const [metrics, alerts, perMonth, byStatus] = await Promise.all([
            apiGet('/admin/dashboard'),
            apiGet('/admin/alerts'),
            apiGet('/admin/charts/disputes-per-month'),
            apiGet('/admin/charts/disputes-by-status'),
        ]);

        setDashboardMetrics(metrics);
        setAlertMetrics(alerts);

        maybeRenderChart('adminMonthChart', {
            type: 'line',
            data: {
                labels: (perMonth ?? []).map((item) => item.month),
                datasets: [{
                    label: 'Disputes',
                    data: (perMonth ?? []).map((item) => item.total),
                    borderColor: '#2d62dd',
                    backgroundColor: 'rgba(45, 98, 221, 0.2)',
                    fill: true,
                    tension: 0.35,
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });

        maybeRenderChart('adminStatusChart', {
            type: 'doughnut',
            data: {
                labels: (byStatus ?? []).map((item) => item.status),
                datasets: [{
                    data: (byStatus ?? []).map((item) => item.total),
                    backgroundColor: ['#d97706', '#2563eb', '#0f9f6e', '#ea580c', '#5f6f82'],
                }],
            },
            options: { responsive: true },
        });
    } catch {
        showAlert('Unable to load admin dashboard analytics.');
    }
}

export async function initAdminDisputes() {
    const form = document.getElementById('adminDisputeFilterForm');
    const prev = document.getElementById('adminPrevPage');
    const next = document.getElementById('adminNextPage');
    const escalated = document.getElementById('loadEscalatedBtn');

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            await loadAdminDisputePage(1);
        } catch {
            showAlert('Unable to apply dispute filters.');
        }
    });

    prev?.addEventListener('click', async () => {
        if (adminDisputePage <= 1) {
            return;
        }

        try {
            await loadAdminDisputePage(adminDisputePage - 1);
        } catch {
            showAlert('Unable to load previous page.');
        }
    });

    next?.addEventListener('click', async () => {
        if (adminDisputePage >= adminLastPage) {
            return;
        }

        try {
            await loadAdminDisputePage(adminDisputePage + 1);
        } catch {
            showAlert('Unable to load next page.');
        }
    });

    escalated?.addEventListener('click', async () => {
        try {
            const data = await apiGet('/admin/disputes/escalated');
            renderAdminDisputesRows(Array.isArray(data) ? data : []);
            const info = document.getElementById('adminDisputePaginationInfo');
            if (info) {
                info.textContent = 'Escalated disputes snapshot';
            }
        } catch {
            showAlert('Unable to load escalated disputes.');
        }
    });

    try {
        await loadAdminDisputePage(1);
    } catch {
        showAlert('Unable to load admin disputes.');
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

        maybeRenderChart('adminMonthlyReportChart', {
            type: 'bar',
            data: {
                labels: (monthly ?? []).map((item) => item.month),
                datasets: [{
                    label: 'Monthly disputes',
                    data: (monthly ?? []).map((item) => item.total),
                    backgroundColor: '#2d62dd',
                    borderRadius: 8,
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });
    } catch {
        showAlert('Unable to load report datasets.');
    }
}

export async function initAdminSettings() {
    const addField = document.getElementById('addSettingField');
    const form = document.getElementById('adminSettingsForm');

    bindSettingRowRemoval();

    addField?.addEventListener('click', () => {
        const container = document.getElementById('settingsFieldList');
        if (!container) {
            return;
        }

        container.insertAdjacentHTML(
            'beforeend',
            `
            <div class="row g-2 align-items-end setting-row">
                <div class="col-md-5"><label class="form-label">Key</label><input class="form-control" name="setting_key" placeholder="new_setting_key"></div>
                <div class="col-md-5"><label class="form-label">Value</label><input class="form-control" name="setting_value" placeholder="value"></div>
                <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100 remove-setting">Remove</button></div>
            </div>
            `,
        );
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const settings = collectSettingsPayload();

        if (!Object.keys(settings).length) {
            feedbackText('#settingsFeedback', 'Add at least one setting key/value pair.');
            return;
        }

        try {
            await apiPost('/admin/settings/update', { settings });
            feedbackText('#settingsFeedback', 'Settings updated successfully.', true);
            showToast('Settings updated', 'success');
        } catch (error) {
            feedbackText('#settingsFeedback', error.message ?? 'Unable to save settings.');
        }
    });

    try {
        const settings = await apiGet('/admin/settings');
        renderSettingsRows(settings);
    } catch {
        showAlert('Unable to load settings.');
    }
}

export async function initAdminActivity() {
    const refresh = document.getElementById('refreshActivityBtn');

    const load = async () => {
        try {
            const data = await apiGet('/admin/activity');
            renderActivity(data);
        } catch {
            showAlert('Unable to load recent activity.');
        }
    };

    refresh?.addEventListener('click', load);
    await load();
}

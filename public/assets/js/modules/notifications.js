import { apiGet, apiPost } from './api.js';
import { escapeHtml, formatDate, getCurrentUser, showToast } from './dom.js';

// Encodes route parameters that can contain slashes or spaces.
function encodeRouteParam(value) {
    return encodeURIComponent(String(value ?? '').trim());
}

// Rebuilds the notification menu and unread badge from API data.
function renderNotifications(items) {
    const list = document.querySelector('[data-notification-list]');
    const badge = document.querySelector('[data-notification-count]');
    const total = document.querySelector('[data-notification-total]');

    if (!list || !badge || !total) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML = '<div class="list-group-item text-muted small">No notifications yet.</div>';
        badge.classList.add('d-none');
        total.textContent = '0';
        return;
    }

    list.innerHTML = items
        .map((item) => {
            const unreadClass = item.is_read ? '' : 'unread';
            return `
                <button
                    class="list-group-item list-group-item-action gd-notification-item ${unreadClass}"
                    data-notification-id="${item.id}"
                    type="button"
                >
                    <div class="d-flex w-100 justify-content-between">
                        <strong class="small">${escapeHtml(item.title ?? 'Notification')}</strong>
                        <small class="text-muted">${escapeHtml(formatDate(item.created_at))}</small>
                    </div>
                    <div class="small text-muted">${escapeHtml(item.message ?? '')}</div>
                </button>
            `;
        })
        .join('');

    const unreadCount = items.filter((item) => !item.is_read).length;
    badge.textContent = String(unreadCount);
    badge.classList.toggle('d-none', unreadCount === 0);
    total.textContent = String(items.length);
}

// Marks one notification as read on the server.
async function markAsRead(id) {
    await apiPost(`/notifications/read/${id}`);
}

// Handles notification clicks with event delegation.
function registerNotificationClicks() {
    const list = document.querySelector('[data-notification-list]');
    if (!list) {
        return;
    }

    list.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-notification-id]');
        if (!target) {
            return;
        }

        const id = target.getAttribute('data-notification-id');
        if (!id) {
            return;
        }

        try {
            await markAsRead(id);
            target.classList.remove('unread');
            showToast('Notification marked as read', 'success');

            const user = getCurrentUser();
            if (user?.identifier) {
                const data = await apiGet(`/notifications/${encodeRouteParam(user.identifier)}`);
                renderNotifications(data);
            }
        } catch {
            showToast('Unable to mark notification as read', 'danger');
        }
    });
}

// Loads notifications for the signed-in user into the topbar menu.
export async function initNotifications() {
    const user = getCurrentUser();
    if (!user?.identifier) {
        return;
    }

    try {
        const data = await apiGet(`/notifications/${encodeRouteParam(user.identifier)}`);
        renderNotifications(data);
        registerNotificationClicks();
    } catch {
        renderNotifications([]);
    }
}

import { getCurrentUser } from './dom.js';

// Converts any API error payload into one predictable object shape.
function normalizeErrorPayload(payload, status, fallback) {
    if (!payload || typeof payload !== 'object') {
        return { status, message: fallback, errors: null };
    }

    const message = payload.error ?? payload.message ?? fallback;
    return {
        status,
        message,
        errors: payload.errors ?? null,
        payload,
    };
}

// Allows endpoints to return JSON, plain text, or an empty response.
async function parseResponse(response) {
    const text = await response.text();
    if (!text) {
        return null;
    }

    try {
        return JSON.parse(text);
    } catch {
        return text;
    }
}

// Wraps fetch with CSRF, JSON encoding, and the current portal identifier header.
export async function apiRequest(path, options = {}) {
    const {
        method = 'GET',
        body = null,
        headers = {},
    } = options;

    const normalizedMethod = method.toUpperCase();
    const csrfToken = window.GDS_CONFIG?.csrfToken ?? '';
    const user = getCurrentUser();

    const requestHeaders = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...headers,
    };

    if (user?.identifier && !requestHeaders['X-User-Identifier']) {
        requestHeaders['X-User-Identifier'] = user.identifier;
    }

    let payload = body;

    if (body && !(body instanceof FormData) && typeof body === 'object') {
        payload = JSON.stringify(body);
        requestHeaders['Content-Type'] = requestHeaders['Content-Type'] ?? 'application/json';
    }

    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(normalizedMethod)) {
        requestHeaders['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(path, {
        method: normalizedMethod,
        headers: requestHeaders,
        body: payload,
        credentials: 'same-origin',
    });

    const parsed = await parseResponse(response);

    if (!response.ok) {
        const fallback = `Request failed with status ${response.status}`;
        throw normalizeErrorPayload(parsed, response.status, fallback);
    }

    return parsed;
}

export async function apiGet(path) {
    return apiRequest(path, { method: 'GET' });
}

export async function apiPost(path, body, headers = {}) {
    return apiRequest(path, { method: 'POST', body, headers });
}

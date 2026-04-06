import { apiPost } from './api.js';
import { feedbackText } from './dom.js';

function persistUser(data) {
    if (!data?.identifier) {
        return;
    }

    window.localStorage.setItem('gds_user_identifier', data.identifier);
    window.localStorage.setItem('gds_user_role', data.role ?? '');
}

function parseUrlParam(name) {
    const params = new URLSearchParams(window.location.search);
    return (params.get(name) ?? '').trim();
}

function prefillLoginIdentifier() {
    const identifier = parseUrlParam('identifier');
    if (!identifier) {
        return;
    }

    const input = document.getElementById('loginIdentifier');
    if (input) {
        input.value = identifier;
    }
}

async function handleLogin(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        const data = await apiPost('/login', payload);
        persistUser(data);
        feedbackText('#loginFeedback', 'Login successful. Redirecting...', true);

        window.setTimeout(() => {
            window.location.assign('/portal/app');
        }, 450);
    } catch (error) {
        feedbackText('#loginFeedback', error.message ?? 'Unable to login.');
    }
}

async function handleSignupIdentifier(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const identifier = String(formData.get('identifier') ?? '').trim();

    if (!identifier) {
        feedbackText('#signupIdentifyFeedback', 'Identifier is required.');
        return;
    }

    try {
        const data = await apiPost('/identify', { identifier });

        if (data.status === 'existing_user') {
            feedbackText('#signupIdentifyFeedback', 'Account already exists. Please log in instead.');
            return;
        }

        if (data.status === 'first_time' && data.role) {
            const next = `/portal/signup/password?identifier=${encodeURIComponent(identifier)}&role=${encodeURIComponent(data.role)}`;
            window.location.assign(next);
            return;
        }

        feedbackText('#signupIdentifyFeedback', 'Unable to continue sign up for this identifier.');
    } catch (error) {
        feedbackText('#signupIdentifyFeedback', error.message ?? 'Identifier not found.');
    }
}

async function handleSignupPassword(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await apiPost('/set-password', payload);
        feedbackText('#signupPasswordFeedback', 'Account created. Redirecting to login...', true);

        const identifier = encodeURIComponent(String(payload.identifier ?? ''));
        window.setTimeout(() => {
            window.location.assign(`/portal/auth?signup=success&identifier=${identifier}`);
        }, 450);
    } catch (error) {
        feedbackText('#signupPasswordFeedback', error.message ?? 'Unable to create account.');
    }
}

async function handleForgotPassword(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        await apiPost('/forgot-password', payload);
        feedbackText('#forgotPasswordFeedback', 'Password updated. Redirecting to login...', true);

        const identifier = encodeURIComponent(String(payload.identifier ?? ''));
        window.setTimeout(() => {
            window.location.assign(`/portal/auth?reset=success&identifier=${identifier}`);
        }, 450);
    } catch (error) {
        feedbackText('#forgotPasswordFeedback', error.message ?? 'Unable to reset password.');
    }
}

export function initAuthLoginPage() {
    prefillLoginIdentifier();
    document.getElementById('loginForm')?.addEventListener('submit', handleLogin);
}

export function initAuthSignupIdentifierPage() {
    document.getElementById('signupIdentifyForm')?.addEventListener('submit', handleSignupIdentifier);
}

export function initAuthSignupPasswordPage() {
    document.getElementById('signupPasswordForm')?.addEventListener('submit', handleSignupPassword);
}

export function initAuthForgotPasswordPage() {
    document.getElementById('forgotPasswordForm')?.addEventListener('submit', handleForgotPassword);
}

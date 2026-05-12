import { apiPost } from './api.js';
import { feedbackText } from './dom.js';

// Saves the authenticated user so legacy API calls can include the identifier.
function persistUser(data) {
    if (!data?.identifier) {
        return;
    }

    window.localStorage.setItem('gds_user_identifier', data.identifier);
    window.localStorage.setItem('gds_user_role', data.role ?? '');
}

// Reads optional query values used to prefill auth forms after redirects.
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

function prefillForgotPasswordIdentifier() {
    const identifier = parseUrlParam('identifier');
    if (!identifier) {
        return;
    }

    const input = document.getElementById('forgotIdentifier');
    if (input) {
        input.value = identifier;
    }
}

// Handles the returning-user login form.
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

        if (error.status === 409 && error.payload?.next) {
            window.setTimeout(() => {
                window.location.assign(error.payload.next);
            }, 900);
        }
    }
}

// Checks whether the entered university ID can start first-time signup.
async function handleSignupIdentifier(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const identifier = String(formData.get('identifier') ?? '').trim();

    if (!identifier) {
        feedbackText('#signupIdentifyFeedback', 'University ID is required.');
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

        feedbackText('#signupIdentifyFeedback', 'Unable to continue sign up for this University ID.');
    } catch (error) {
        feedbackText('#signupIdentifyFeedback', error.message ?? 'University ID not found.');
    }
}

// Creates the user's first password after their identifier is confirmed.
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

// Starts password reset by confirming the university ID.
async function handleForgotPassword(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        const data = await apiPost('/forgot-password/identify', payload);
        feedbackText('#forgotPasswordIdentifyFeedback', 'University ID confirmed. Redirecting...', true);

        window.setTimeout(() => {
            window.location.assign(data.next ?? '/portal/password/forgot/email');
        }, 350);
    } catch (error) {
        feedbackText('#forgotPasswordIdentifyFeedback', error.message ?? 'Unable to continue password reset.');

        if (error.status === 404) {
            window.setTimeout(() => {
                window.location.assign(error.payload?.signup_url ?? '/portal/signup');
            }, 1200);
        }
    }
}

// Sends the verification code to the account email on record.
async function handleForgotPasswordEmail(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        const data = await apiPost('/forgot-password/email', payload);
        feedbackText('#forgotPasswordEmailFeedback', 'Verification code sent. Redirecting...', true);

        window.setTimeout(() => {
            window.location.assign(data.next ?? '/portal/password/forgot/verify');
        }, 400);
    } catch (error) {
        feedbackText('#forgotPasswordEmailFeedback', error.message ?? 'Unable to send verification code.');
    }
}

// Verifies the six-digit password reset code.
async function handleForgotPasswordVerify(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        const data = await apiPost('/forgot-password/verify', payload);
        feedbackText('#forgotPasswordVerifyFeedback', 'Code verified. Redirecting...', true);

        window.setTimeout(() => {
            window.location.assign(data.next ?? '/portal/password/forgot/reset');
        }, 350);
    } catch (error) {
        feedbackText('#forgotPasswordVerifyFeedback', error.message ?? 'Unable to verify the code.');
    }
}

// Stores the new password after the reset code has been verified.
async function handleForgotPasswordReset(event) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const payload = Object.fromEntries(formData.entries());

    try {
        const data = await apiPost('/forgot-password/reset', payload);
        feedbackText('#forgotPasswordResetFeedback', 'Password updated. Redirecting to login...', true);

        const identifier = encodeURIComponent(String(data.identifier ?? ''));
        window.setTimeout(() => {
            window.location.assign(`/portal/auth?reset=success&identifier=${identifier}`);
        }, 450);
    } catch (error) {
        feedbackText('#forgotPasswordResetFeedback', error.message ?? 'Unable to reset password.');
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
    prefillForgotPasswordIdentifier();
    document.getElementById('forgotPasswordIdentifyForm')?.addEventListener('submit', handleForgotPassword);
}

export function initAuthForgotPasswordEmailPage() {
    document.getElementById('forgotPasswordEmailForm')?.addEventListener('submit', handleForgotPasswordEmail);
}

export function initAuthForgotPasswordVerifyPage() {
    document.getElementById('forgotPasswordVerifyForm')?.addEventListener('submit', handleForgotPasswordVerify);
}

export function initAuthForgotPasswordResetPage() {
    document.getElementById('forgotPasswordResetForm')?.addEventListener('submit', handleForgotPasswordReset);
}

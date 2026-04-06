import { initAdminActivity, initAdminDashboard, initAdminDisputes, initAdminReports, initAdminSettings } from './modules/admin.js';
import {
    initAuthForgotPasswordPage,
    initAuthLoginPage,
    initAuthSignupIdentifierPage,
    initAuthSignupPasswordPage,
} from './modules/auth.js';
import { initLecturerDashboard, initLecturerDisputeDetail, initLecturerDisputes } from './modules/lecturer.js';
import { initNotifications } from './modules/notifications.js';
import { pageId } from './modules/dom.js';
import {
    initStudentDashboard,
    initStudentDisputeDetail,
    initStudentNotifications,
    initStudentSubmitDispute,
    initStudentTickets,
} from './modules/student.js';

function clearLocalSession() {
    window.localStorage.removeItem('gds_user_identifier');
    window.localStorage.removeItem('gds_user_role');
}

function bindLogoutForms() {
    document.querySelectorAll('form[action="/logout"]').forEach((form) => {
        form.addEventListener('submit', () => {
            clearLocalSession();
        });
    });
}

async function bootstrap() {
    const currentPage = pageId();
    bindLogoutForms();

    if (!currentPage.startsWith('auth-')) {
        await initNotifications();
    }

    switch (currentPage) {
        case 'auth-login':
            initAuthLoginPage();
            break;
        case 'auth-signup-id':
            initAuthSignupIdentifierPage();
            break;
        case 'auth-signup-password':
            initAuthSignupPasswordPage();
            break;
        case 'auth-forgot-password':
            initAuthForgotPasswordPage();
            break;

        case 'student-dashboard':
            await initStudentDashboard();
            break;
        case 'student-submit-dispute':
            initStudentSubmitDispute();
            break;
        case 'student-tickets':
        case 'student-disputes':
            await initStudentTickets();
            break;
        case 'student-notifications':
            await initStudentNotifications();
            break;
        case 'student-dispute-detail':
            await initStudentDisputeDetail();
            break;

        case 'lecturer-dashboard':
            await initLecturerDashboard();
            break;
        case 'lecturer-disputes':
            await initLecturerDisputes();
            break;
        case 'lecturer-dispute-detail':
            await initLecturerDisputeDetail();
            break;

        case 'admin-dashboard':
            await initAdminDashboard();
            break;
        case 'admin-disputes':
            await initAdminDisputes();
            break;
        case 'admin-reports':
            await initAdminReports();
            break;
        case 'admin-settings':
            await initAdminSettings();
            break;
        case 'admin-activity':
            await initAdminActivity();
            break;

        default:
            break;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    bootstrap();
});

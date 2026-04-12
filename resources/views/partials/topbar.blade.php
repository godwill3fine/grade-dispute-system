@php
    use Illuminate\Support\Str;

    $currentHour = now()->hour;
    $greetingPrefix = match (true) {
        $currentHour < 12 => 'Good morning',
        $currentHour < 17 => 'Good afternoon',
        default => 'Good evening',
    };
    $dashboardHeadline = $greetingPrefix.', '.Str::before(auth()->user()->name ?? 'User', ' ');
    $pageHeadline = html_entity_decode((string) $__env->yieldContent('page_title', 'Dashboard'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $headline = request()->routeIs('portal.student.dashboard', 'portal.lecturer.dashboard', 'portal.admin.dashboard')
        ? $dashboardHeadline
        : $pageHeadline;
@endphp

<header class="navbar">
    <div class="navbar-left">
        <span class="material-symbols-outlined mobile-menu" id="mobile-menu">menu</span>
        <h2 class="greeting">{{ $headline }}</h2>
        <h2 class="page-title">Grade Dispute Portal</h2>
    </div>

    <div class="navbar-right">
        <button class="icon-button" id="theme-toggle" type="button" aria-label="Toggle theme">
            <span class="material-symbols-outlined" data-theme-icon>dark_mode</span>
        </button>

        <div class="dropdown">
            <button
                class="icon-button icon-button-notification"
                data-bs-toggle="dropdown"
                data-notification-toggle
                aria-expanded="false"
                type="button"
            >
                <span class="material-symbols-outlined">notifications</span>
                <span class="notification-badge d-none" data-notification-count>0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 gd-notification-menu">
                <div class="gd-notification-menu-header">
                    <strong>Notifications</strong>
                    <span class="gd-notification-total" data-notification-total>0</span>
                </div>
                <div data-notification-list class="gd-notification-list">
                    <div class="gd-notification-empty">No notifications yet.</div>
                </div>
            </div>
        </div>

        <button class="icon-button" type="button" title="Settings" aria-label="Settings">
            <span class="material-symbols-outlined">settings</span>
        </button>
    </div>
</header>

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
    $role = auth()->user()->role ?? '';
    $settingsMenus = [
        'student' => [
            ['route' => 'portal.student.profile', 'label' => 'View Profile', 'icon' => 'person'],
            ['route' => 'portal.student.settings', 'label' => 'Account Settings', 'icon' => 'settings'],
        ],
        'lecturer' => [
            ['route' => 'portal.lecturer.disputes', 'label' => 'Assigned Tickets', 'icon' => 'list_alt'],
            ['route' => 'portal.lecturer.history', 'label' => 'Review History', 'icon' => 'history'],
        ],
        'admin' => [
            ['route' => 'portal.admin.users', 'label' => 'Manage Users', 'icon' => 'group'],
            ['route' => 'portal.admin.settings', 'label' => 'System Settings', 'icon' => 'tune'],
        ],
    ];
    $settingsMenu = $settingsMenus[$role] ?? [];
    $settingsRoleLabel = $role !== '' ? Str::headline($role) : 'User';
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

        @if (!empty($settingsMenu))
            <div class="settings-dropdown-wrapper">
                <button
                    class="icon-button settings-trigger"
                    type="button"
                    title="Settings"
                    aria-label="Settings"
                    aria-expanded="false"
                >
                    <span class="material-symbols-outlined">settings</span>
                </button>

                <div class="settings-dropdown-menu" role="menu">
                    <div class="dropdown-header">
                        <h4>{{ auth()->user()->name ?? 'User' }}</h4>
                        <p>{{ $settingsRoleLabel }}</p>
                    </div>

                    <ul class="dropdown-list">
                        @foreach ($settingsMenu as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" role="menuitem">
                                    <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach

                        <li class="logout-item">
                            <form method="POST" action="{{ route('portal.logout') }}" class="settings-logout-form">
                                @csrf
                                <button type="submit" class="settings-dropdown-action" role="menuitem">
                                    <span class="material-symbols-outlined">logout</span>
                                    Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </div>
</header>

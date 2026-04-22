@php
    $role = auth()->user()->role ?? '';
    $navigationRole = in_array($role, ['lecturer', 'staff'], true) ? 'lecturer' : $role;
    $menus = [
        'student' => [
            ['route' => 'portal.student.dashboard', 'match' => 'portal.student.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'portal.student.submit', 'match' => 'portal.student.submit', 'label' => 'Submit Ticket', 'icon' => 'add_box'],
            ['route' => 'portal.student.tickets', 'match' => ['portal.student.tickets', 'portal.student.disputes', 'portal.student.dispute.detail'], 'label' => 'My Tickets', 'icon' => 'list_alt'],
            ['route' => 'portal.student.notifications', 'match' => 'portal.student.notifications', 'label' => 'Notifications', 'icon' => 'mark_email_unread'],
        ],
        'lecturer' => [
            ['route' => 'portal.lecturer.dashboard', 'match' => 'portal.lecturer.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'portal.lecturer.disputes', 'match' => 'portal.lecturer.disputes*', 'label' => 'Assigned Tickets', 'icon' => 'list'],
            ['route' => 'portal.lecturer.history', 'match' => 'portal.lecturer.history', 'label' => 'Review History', 'icon' => 'history'],
        ],
        'admin' => [
            ['route' => 'portal.admin.dashboard', 'match' => 'portal.admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'portal.admin.disputes', 'match' => 'portal.admin.disputes', 'label' => 'Manage Tickets', 'icon' => 'list'],
            ['route' => 'portal.admin.users', 'match' => 'portal.admin.users', 'label' => 'Manage Users', 'icon' => 'group'],
            ['route' => 'portal.admin.escalations', 'match' => 'portal.admin.escalations', 'label' => 'Escalations', 'icon' => 'warning'],
            ['route' => 'portal.admin.reports', 'match' => 'portal.admin.reports', 'label' => 'Reports', 'icon' => 'bar_chart'],
            ['route' => 'portal.admin.settings', 'match' => 'portal.admin.settings', 'label' => 'System Settings', 'icon' => 'settings'],
            ['route' => 'portal.admin.activity', 'match' => 'portal.admin.activity', 'label' => 'Recent Activity', 'icon' => 'schedule'],
        ],
    ];
    $profileIdentifier = auth()->user()->identifier ?? 'ID unavailable';
@endphp

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="material-symbols-outlined">automation</span>
        </div>
        <span class="text sidebar-header-title"><h3>Grade Dispute System</h3></span>
        <span class="material-symbols-outlined" id="toggle-sidebar">menu</span>
    </div>

    <ul class="menu">
        <div class="menu-title">
            <span class="text"><h3>Menu</h3></span>
        </div>
        @foreach ($menus[$navigationRole] ?? [] as $item)
            @php
                $matchPatterns = is_array($item['match']) ? $item['match'] : [$item['match']];
                $isActive = request()->routeIs(...$matchPatterns);
            @endphp
            <li class="{{ $isActive ? 'active' : '' }}">
                <a href="{{ route($item['route']) }}" class="menu-link">
                    <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                    <span class="text">{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach

        <li id="profile">
            <div class="profile-info">
                <span class="material-symbols-outlined">account_circle</span>
                <span class="text">
                    <h2>{{ auth()->user()->name }}</h2>
                    <h3>{{ $profileIdentifier }}</h3>
                </span>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}" class="sidebar-logout-form">
                @csrf
                <button type="submit" class="logout material-symbols-outlined" aria-label="Logout">logout</button>
            </form>
        </li>
    </ul>
</aside>

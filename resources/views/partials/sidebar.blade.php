@php
    $role = auth()->user()->role ?? '';
    $menus = [
        'student' => [
            ['route' => 'portal.student.dashboard', 'match' => 'portal.student.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
            ['route' => 'portal.student.submit', 'match' => 'portal.student.submit', 'label' => 'Submit Dispute', 'icon' => 'bi-file-earmark-plus'],
            ['route' => 'portal.student.tickets', 'match' => ['portal.student.tickets', 'portal.student.disputes', 'portal.student.dispute.detail'], 'label' => 'Tickets', 'icon' => 'bi-ticket-detailed'],
            ['route' => 'portal.student.notifications', 'match' => 'portal.student.notifications', 'label' => 'Notifications', 'icon' => 'bi-bell'],
        ],
        'lecturer' => [
            ['route' => 'portal.lecturer.dashboard', 'match' => 'portal.lecturer.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
            ['route' => 'portal.lecturer.disputes', 'match' => 'portal.lecturer.disputes*', 'label' => 'Assigned Disputes', 'icon' => 'bi-clipboard2-check'],
        ],
        'admin' => [
            ['route' => 'portal.admin.dashboard', 'match' => 'portal.admin.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill'],
            ['route' => 'portal.admin.disputes', 'match' => 'portal.admin.disputes', 'label' => 'Disputes', 'icon' => 'bi-kanban-fill'],
            ['route' => 'portal.admin.reports', 'match' => 'portal.admin.reports', 'label' => 'Reports', 'icon' => 'bi-bar-chart-line-fill'],
            ['route' => 'portal.admin.settings', 'match' => 'portal.admin.settings', 'label' => 'Settings', 'icon' => 'bi-sliders'],
            ['route' => 'portal.admin.activity', 'match' => 'portal.admin.activity', 'label' => 'Activity', 'icon' => 'bi-clock-history'],
        ],
    ];
@endphp

<aside class="gd-sidebar shadow-sm">
    <div class="gd-brand p-3 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
            <div>
                <h1 class="h6 mb-0">Grade Dispute</h1>
                <small class="text-muted text-uppercase">{{ $role }}</small>
            </div>
        </div>
    </div>

    <nav class="p-2">
        @foreach ($menus[$role] ?? [] as $item)
            @php
                $matchPatterns = is_array($item['match']) ? $item['match'] : [$item['match']];
                $isActive = request()->routeIs(...$matchPatterns);
            @endphp
            <a class="gd-nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-auto p-3 border-top">
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </button>
        </form>
    </div>
</aside>

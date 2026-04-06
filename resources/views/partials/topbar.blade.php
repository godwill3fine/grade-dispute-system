<header class="gd-topbar border-bottom">
    <div class="d-flex align-items-center justify-content-between px-4 py-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Academic Workflow Portal</p>
            <h2 class="h4 mb-0">@yield('page_title', 'Dashboard')</h2>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button
                    class="btn btn-light border position-relative"
                    data-bs-toggle="dropdown"
                    data-notification-toggle
                    aria-expanded="false"
                    type="button"
                >
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" data-notification-count>0</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-sm p-0 gd-notification-menu">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <strong class="small text-uppercase">Notifications</strong>
                        <span class="badge text-bg-secondary" data-notification-total>0</span>
                    </div>
                    <div data-notification-list class="list-group list-group-flush gd-notification-list">
                        <div class="list-group-item text-muted small">No notifications yet.</div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <p class="mb-0 fw-semibold">{{ auth()->user()->name }}</p>
                <small class="text-muted">{{ auth()->user()->identifier }}</small>
            </div>
        </div>
    </div>
</header>

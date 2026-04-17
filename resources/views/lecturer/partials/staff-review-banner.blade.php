@php
    $isStaffReviewer = (bool) ($portalUserMeta['is_staff_reviewer'] ?? false);
    $staffRoleLabel = trim((string) ($portalUserMeta['staff_role_label'] ?? 'Staff'));
@endphp

@if ($isStaffReviewer)
    <section class="staff-review-banner" aria-label="Staff review access">
        <div class="staff-review-banner-icon">
            <span class="material-symbols-outlined">shield</span>
        </div>
        <div class="staff-review-banner-copy">
            <h3>{{ $staffRoleLabel }} Review Access</h3>
            <p>You are using the lecturer workspace with elevated review privileges for escalated cases assigned to your staff account.</p>
        </div>
    </section>
@endif

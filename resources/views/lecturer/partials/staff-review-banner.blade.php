@php
    $portalUserMeta = is_array($portalUserMeta ?? null) ? $portalUserMeta : [];
    $isStaffReviewer = (bool) ($portalUserMeta['is_staff_reviewer'] ?? false);
    $staffRoleLabel = trim((string) ($portalUserMeta['staff_role_label'] ?? 'Staff'));
@endphp

@if ($isStaffReviewer)
    <section class="staff-review-banner" aria-label="Staff review access">
        <div class="staff-review-banner-icon">
            <span class="material-symbols-outlined">shield_person</span>
        </div>
        <div class="staff-review-banner-copy">
            <h3>{{ $staffRoleLabel }} Review Access</h3>
            <p>Escalated tickets assigned to your identifier appear here alongside any lecturer tickets already assigned to you. You can review, respond, and update those cases without admin-only controls.</p>
        </div>
    </section>
@endif

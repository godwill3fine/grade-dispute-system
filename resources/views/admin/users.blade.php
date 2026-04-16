@extends('layouts.app')

@section('title', 'Admin Manage Users | Grade Dispute')
@section('page_id', 'admin-users')
@section('page_title', 'Manage Users')

@section('content')
<div class="page-header flex-header page-header-plain">
    <div class="header-text"></div>
    <div class="header-actions">
        <button class="btn btn-outline btn-surface" id="adminUsersImportBtn" type="button">Import Users</button>
        <input id="adminUsersImportInput" type="file" accept=".csv,text/csv" class="d-none" aria-hidden="true">
        <button class="btn btn-primary" id="adminUsersAddBtn" type="button">
            <span class="material-symbols-outlined">add</span> Add User
        </button>
    </div>
</div>

<div class="table-container">
    <div class="admin-table-controls table-controls-spacious">
        <div class="search-box expanded-search">
            <span class="material-symbols-outlined">search</span>
            <input
                id="adminUserSearch"
                type="text"
                placeholder="Search users by name, email, or ID..."
                aria-label="Search users"
            >
        </div>
        <div class="control-actions admin-users-actions">
            <div class="dropdown-wrapper">
                <span class="material-symbols-outlined filter-icon">filter_alt</span>
                <select class="status-filter with-icon" id="adminUserRoleFilter" aria-label="Filter by role">
                    <option value="">All Roles</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <select class="status-filter" id="adminUserStatusFilter" aria-label="Filter by status">
                <option value="">Status</option>
                <option value="active">Active</option>
                <option value="pending invite">Pending Invite</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="tickets-table admin-users-table">
            <thead>
                <tr>
                    <th>USER DETAILS</th>
                    <th>ROLE</th>
                    <th>DEPARTMENT</th>
                    <th>STATUS</th>
                    <th>LAST LOGIN</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="adminUsersTableBody">
                <tr><td colspan="6" class="text-muted text-center py-4">Loading users...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer-pagination">
        <div class="pagination-info" id="adminUsersPaginationInfo">Showing 0 to 0 of 0 total users</div>
        <div class="pagination-controls" id="adminUsersPaginationControls"></div>
    </div>
</div>
@endsection

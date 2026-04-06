@extends('layouts.app')

@section('title', 'Admin Settings | Grade Dispute')
@section('page_id', 'admin-settings')
@section('page_title', 'System Settings')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h3 class="h6 mb-0">Escalation & Workflow Settings</h3>
        <button class="btn btn-sm btn-outline-primary" id="addSettingField" type="button">Add Field</button>
    </div>
    <div class="card-body">
        <form id="adminSettingsForm" class="d-grid gap-3">
            <div id="settingsFieldList" class="d-grid gap-3">
                <div class="text-muted">Loading settings...</div>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">Save Settings</button>
            </div>
            <p class="small text-muted mb-0" id="settingsFeedback"></p>
        </form>
    </div>
</div>
@endsection

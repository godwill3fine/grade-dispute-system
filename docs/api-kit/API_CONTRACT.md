# Grade Dispute System API Contract (v1)

Date: 2026-04-04
Base URL: `http://127.0.0.1:8000`
Protected route header: `X-User-Identifier: <identifier>`

## 1) Authentication

### POST /identify
- Auth: None
- Body:
```json
{
  "identifier": "S001"
}
```
- 200:
```json
{
  "status": "first_time",
  "role": "student"
}
```
- Errors: `404 user not found`, `422 validation`

### POST /set-password
- Auth: None
- Body:
```json
{
  "identifier": "S001",
  "role": "student",
  "password": "password123",
  "password_confirmation": "password123"
}
```
- 200: `{"message":"Account created successfully"}`
- Errors: `400 account exists`, `404 profile missing`, `422 validation`

### POST /login
- Auth: None
- Body:
```json
{
  "identifier": "S001",
  "password": "password123"
}
```
- 200:
```json
{
  "message": "Login successful",
  "role": "student",
  "identifier": "S001"
}
```
- Errors: `401 invalid credentials`, `422 validation`

## 2) Core Student/Lecturer Dispute Flow

### GET /units/search?q=CSC
- Auth: None
- 200: `[{ unit_code, unit_name, ... }]`

### POST /disputes
- Auth: Student
- Header: `X-User-Identifier: S001`
- Body (unit dispute):
```json
{
  "student_id": "S001",
  "type": "unit",
  "unit_code": "CSC101",
  "course": "BSc CS",
  "title": "Grade mismatch",
  "description": "CAT score missing"
}
```
- 200:
```json
{
  "message": "Dispute submitted successfully",
  "data": {
    "id": 1,
    "ticket_number": "DSP-00001",
    "status": "pending",
    "assigned_to": "L001"
  }
}
```

### GET /student/disputes/{student_id}
- Auth: Student
- Header: `X-User-Identifier: S001`
- 200: `[Dispute...]`

### GET /lecturer/disputes/{lecturer_id}
- Auth: Lecturer/Admin
- Header: `X-User-Identifier: L001`
- 200: `[Dispute...]`

### POST /dispute/respond
- Auth: Lecturer/Admin
- Header: `X-User-Identifier: L001`
- Body:
```json
{
  "dispute_id": 1,
  "comment": "Received. Reviewing scripts."
}
```
- 200: `{"message":"Response added successfully"}`

### POST /dispute/status
- Auth: Lecturer/Admin
- Header: `X-User-Identifier: L001`
- Body:
```json
{
  "dispute_id": 1,
  "status": "under_review",
  "comment": "Moved to Under Review"
}
```
- 200: `{"message":"Status updated successfully"}`

### GET /dispute/{id}/timeline
- Auth: Student/Lecturer/Admin
- Header: `X-User-Identifier: S001`
- 200: `[DisputeLog...]`

### GET /dispute/logs/{dispute_id}
- Auth: Student/Lecturer/Admin
- Header: `X-User-Identifier: S001`
- 200: `[DisputeLog...]`

## 3) Notifications

### GET /notifications/{userId}
- Auth: Student/Lecturer/Admin
- Header: `X-User-Identifier: S001`
- 200: `[Notification...]`

### POST /notifications/read/{id}
- Auth: Student/Lecturer/Admin
- Header: `X-User-Identifier: S001`
- 200: `{"message":"Notification marked as read"}`

## 4) Admin Analytics / Reports / Charts
- Auth: Admin
- Header: `X-User-Identifier: ADMIN001`

### Dashboard/Disputes
- GET `/admin/dashboard`
- GET `/admin/disputes`
- GET `/admin/disputes/escalated`
- GET `/admin/disputes/filter?status=under_review`

### Reports
- GET `/admin/reports/units`
- GET `/admin/reports/lecturers`
- GET `/admin/reports/monthly`
- GET `/admin/reports/resolution-time`

### Charts
- GET `/admin/charts/disputes-per-month`
- GET `/admin/charts/disputes-by-status`
- GET `/admin/charts/disputes-by-unit`
- GET `/admin/charts/disputes-by-lecturer`
- GET `/admin/charts/avg-resolution-time`

### Activity
- GET `/admin/activity`

## 5) System Settings & Escalation

### GET /admin/settings
- Auth: Admin
- Header: `X-User-Identifier: ADMIN001`

### POST /admin/settings/update
- Auth: Admin
- Header: `X-User-Identifier: ADMIN001`
- Body:
```json
{
  "settings": {
    "escalation_days_hod": "3",
    "escalation_days_admin": "6"
  }
}
```

### CLI Escalation Command
```bash
php artisan app:escalate-disputes
```

### Scheduler Check
```bash
php artisan schedule:list
```

## Common Error Shapes
- 401: `{"error":"User identifier missing|User not found"}`
- 403: `{"error":"Access denied"}`
- 404: `{"error":"... not found"}`
- 422:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["..."]
  }
}
```

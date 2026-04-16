# Automated Workflow System for Grade Dispute Management and Resolution

## Overview

The Automated Workflow System for Grade Dispute Management and Resolution is a web-based application designed to support the structured handling of academic grade disputes in a university environment.

The system allows students to submit grade-related complaints such as missing marks or incorrect result entries, track the progress of their disputes, and receive feedback once the issue has been reviewed. It also enables lecturers, departmental administrators, and system administrators to manage dispute tickets, update their status, escalate unresolved cases, and maintain an audit trail of all actions taken.

The project was developed as a final year research project for the Department of Information Technology at Jomo Kenyatta University of Agriculture and Technology.

## Problem Statement

In many academic institutions, examination results are published digitally, but the process of resolving grade disputes is still handled manually through physical follow-ups, informal communication, and fragmented office-based procedures.

This creates several challenges:

- Students may not know the correct office or person to contact.
- There is no centralized way to track the progress of a dispute.
- Lecturers and administrators may rely on manual records or personal communication.
- Delays may occur without clear accountability.
- Departments may lack reliable data on pending, resolved, or recurring grade dispute cases.

This system addresses the problem by introducing a structured digital workflow for managing grade disputes from submission to closure.

## Project Objectives

The main objective of the project is to design and develop an automated workflow system that supports the structured submission, tracking, and resolution of academic grade disputes.

The specific objectives are:

1. To examine the current procedures used in handling grade disputes and missing marks.
2. To design a workflow model that defines the stages, roles, and routing logic involved in dispute resolution.
3. To implement a web-based prototype that enables students and academic staff to submit, manage, and monitor grade dispute queries.
4. To evaluate the functionality and usability of the proposed system through testing and user feedback.

## Key Features

### Student Features

- Student authentication
- First-time password creation
- Grade dispute submission
- Course unit selection
- Assessment type selection
- Supporting evidence upload
- Ticket status tracking
- View dispute history
- Receive dispute updates and resolution feedback

### Lecturer Features

- Lecturer authentication
- View assigned dispute tickets
- Review submitted dispute details
- View supporting evidence
- Respond to dispute tickets
- Update ticket status
- Mark disputes as resolved

### Department Chairman / Escalation Handler Features

- View escalated dispute tickets
- Review unresolved cases
- Provide final decision or action
- Resolve escalated tickets
- Monitor delayed dispute cases

### Administrator Features

- Manage system users
- Manage roles and permissions
- Monitor all dispute tickets
- View pending, resolved, and escalated disputes
- Generate administrative reports
- Maintain system oversight

### System Features

- Role-based access control
- Automatic ticket routing
- Automated escalation of unresolved tickets
- Notification system
- Audit trail / ticket logs
- Secure authentication
- File upload support
- Dashboard-based user interface

## System Workflow

The system follows a structured workflow:

1. A user logs into the system using institutional credentials.
2. A student submits a grade dispute ticket.
3. The system records the dispute and assigns it a unique ticket ID.
4. The ticket is automatically routed to the appropriate lecturer or academic authority.
5. The assigned handler reviews the dispute and supporting evidence.
6. The handler updates the ticket status and provides a response.
7. If the dispute is resolved, the student is notified and the ticket is closed.
8. If the ticket remains unresolved beyond the allowed response period, the system escalates it to the Department Chairman.
9. All actions are recorded in the ticket log for accountability and traceability.

## System Architecture

The system uses a layered client-server architecture.

The main layers are:

- **Presentation Layer**  
  Handles user interfaces such as login pages, dashboards, dispute forms, and ticket management screens.

- **Application Layer**  
  Handles request processing, routing, controllers, and communication between the frontend and backend logic.

- **Business Logic Layer**  
  Manages ticket routing, dispute workflow, escalation, notifications, status updates, and audit logging.

- **Data Access Layer**  
  Handles database queries and interactions between the application and database.

- **Database Layer**  
  Stores users, roles, departments, course units, dispute tickets, attachments, logs, notifications, and escalation records.

## Technologies Used

The system is built using the following technologies:

### Backend

- PHP
- Laravel Framework

### Frontend

- HTML
- CSS
- JavaScript
- Bootstrap

### Database

- MySQL

### Development Tools

- Visual Studio Code
- XAMPP or Laragon
- Git and GitHub
- Google Chrome / Mozilla Firefox for testing

## Database Design

The system uses a relational database structure. The main database tables include:

- `users`
- `roles`
- `departments`
- `course_units`
- `tickets`
- `attachments`
- `ticket_logs`
- `notifications`
- `escalations`

### Main Relationships

- A user belongs to a role.
- A user belongs to a department.
- A student can submit many tickets.
- A lecturer can handle many tickets.
- A course unit belongs to a department.
- A course unit is assigned to a lecturer.
- A ticket can have many attachments.
- A ticket can have many ticket logs.
- A ticket can generate many notifications.
- A ticket may have an escalation record.

## User Roles

The system supports the following user roles:

| Role | Description |
|---|---|
| Student | Submits and tracks grade dispute tickets |
| Lecturer | Reviews and resolves assigned dispute tickets |
| Department Chairman | Handles escalated unresolved disputes |
| Administrator | Manages users, tickets, reports, and system activity |

## Installation and Setup

### Prerequisites

Ensure you have the following installed:

- PHP
- Composer
- MySQL
- Node.js and npm
- Laravel
- XAMPP, Laragon, or any local server environment
- Git

### Clone the Repository

```bash
git clone https://github.com/your-username/grade-dispute-system.git

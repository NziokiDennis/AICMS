Phase 1 — Project Setup

Database creation (sql/schema.sql).

Folder structure.

Landing page (index, about, contact, services).

Base includes (header/footer with Bootstrap).

DB config file.

Phase 2 — Authentication & Roles

Implement login/logout for Student/Counselor (/auth).

Implement separate Admin login/logout (/admin/auth).

Session management: sess_user vs sess_admin.

Role-based access guards (auth_check.php).

Password hashing with password_hash().

Admin-only user registration.

Phase 3 — Student Module

Student dashboard.

Find/Filter counselors (specialty, mode, week availability).

Book appointment → appointment state = PENDING.

See appointment statuses (PENDING, APPROVED, DECLINED, COMPLETED).

View notes (only PUBLISHED + their own).

Submit feedback (only after COMPLETED sessions).

Phase 4 — Counselor Module

Counselor dashboard.

Approve/Decline appointments → auto-create session.

Start/End sessions (update session + appointment status).

Add notes (PRIVATE → PUBLISHED toggle).

View student feedback on completed sessions.

Phase 5 — Admin Module

Admin dashboard.

Manage users (CRUD + assign roles).

Manage appointments (override, cancel).

View all notes, sessions, and feedback.

Reports: export CSV of usage, feedback averages, session counts.

Phase 6 — UI/UX Enhancements

Bootstrap UI refinements (cards, modals, badges, icons).

Status indicators (badges for Pending/Approved/etc).

Student feedback stars.

Pagination on counselor list.

Error/confirmation alerts.

Phase 7 — Security Hardening

CSRF tokens on all POST actions.

Escape output (htmlspecialchars) to prevent XSS.

Enforce password policy + login attempt rate limiting.

Database constraints: unique feedback per session, unique slots per counselor/time.

Session regeneration on login, strict role isolation.

Phase 8 — Scalability & Maintenance

Index tuning on appointments, sessions.

Seed data scripts for testing.

Logging/Audit table (optional).

Modularize includes for reusability.

Documentation of workflows + DB schema.

Phase 9 — Optional Add-ons (Future Scaling)

Email/SMS notifications (via PHPMailer or API).

Counselor specialties as many-to-many instead of single string.

Student search by counselor rating/feedback.

AI/NLP assistant for booking (reserved for future).

Calendar export (iCal/Google Calendar integration).


Folder structure:
/counseling-system
├── index.php  
├── aboutus.php 
├── contact.php                        # redirect based on session
├── /config
│   └── db.php                        # PDO, error mode EXCEPTION
├── /includes
│   ├── auth_check.php                # role guard helpers
│   ├── header.php                    # Bootstrap + navbar (role-aware)
│   └── footer.php                    # scripts (Bootstrap, icons)
├── /assets
│   ├── css/app.css
│   └── js/app.js
├── /auth
│   ├── login.php                     # student/counselor login
│   ├── logout.php
│   └── register.php                  # admin-only linked, or disabled in MVP
├── /student
│   ├── dashboard.php                 # list appointments, notes, feedback CTA
│   ├── find_counselor.php            # filter form + results
│   ├── book_appointment.php          # slot picker + create
│   ├── my_notes.php                  # published notes listing
│   └── feedback.php                  # feedback form (guarded)
├── /counselor
│   ├── dashboard.php                 # pending approvals, today, completed
│   ├── approve.php                   # approve/decline actions (POST)
│   ├── session.php                   # start/end actions (POST)
│   ├── add_note.php                  # create/publish notes
│   └── view_feedback.php             # list feedback for their sessions
├── /admin
│   ├── /auth
│   │   ├── login.php                 # separate admin login
│   │   └── logout.php
│   ├── dashboard.php
│   ├── manage_users.php              # CRUD users/roles
│   ├── manage_appointments.php       # global view + overrides
│   └── reports.php                   # CSV exports
└── /sql
   └── schema.sql                    # tables + indexes + constraints

System description
A lean PHP/MySQL web app that lets students find counselors, book appointments, attend sessions (offline/online per mode), and view counselor notes once published. Counselors approve requests, run the session lifecycle, and publish notes. Students can submit feedback only for completed sessions they actually attended. Admin has a separate login area and full governance (users, appointments, sessions, notes, feedback, reports). UI is Bootstrap-first with Bootstrap Icons; interactivity is vanilla JS; no REST APIs or sockets—page loads and standard POST forms.

Roles and authentication

Student/Counselor login: /auth/login.php

Admin login (separate area, separate session cookie/namespace): /admin/auth/login.php

Session isolation: use two cookies, e.g., sess_user for student/counselor and sess_admin for admin. Never reuse one session across roles.

RBAC: server-side checks on every page; deny-by-default for actions.

Primary features (MVP)
Student

Search/Filter counselors by Specialty, Mode, and “Date (open slots in that week)”.

View counselor profile and available slots; book an appointment.

See live appointment status (Pending/Approved/Declined/Completed) on reload.

View session notes when the counselor publishes them.

Submit feedback (rating + comment) only if the session with that counselor is Completed and not already reviewed.

Counselor

See pending appointment requests; Approve/Decline.

Auto session creation on approval; start/end session at the right time.

Write notes as Private or Published-to-student; publish toggles visibility.

View feedback from students across their completed sessions.

Admin (separate login area)

Full CRUD for users; assign roles.

Global view and override for appointments and sessions.

View all notes and feedback; export reports (CSV).

System settings: slot duration defaults, cancellation/lead-time policies.

Functional workflows (happy path)
Student

Find counselor → apply filters (Specialty/Mode/Date-week).

Pick counselor → select an open slot → submit booking (status=PENDING).

On approval → status=APPROVED in dashboard; session scheduled.

Attend session (per mode).

On counselor publish → notes visible; submit feedback (if session COMPLETED).

Counselor

Open dashboard → Pending approvals → Approve/Decline.

Approved → session created (SCHEDULED). Start at session time → End → COMPLETED.

Add note (PRIVATE) → review → Publish (visible to student).

Review received feedback for quality improvement.

Admin

Log in via /admin/auth/login.php (separate session).

Create/manage users; monitor queues; override stuck items.

Export reports and audit essential actions.

Filtering and availability logic

Inputs: Specialty, Mode, Date (any day in desired week).

Compute week window [weekStart, weekEnd] from the chosen date.

Show counselors that:

Match specialty and mode, and

Have at least one availability slot in [weekStart, weekEnd] that is not taken by an Approved/Pending appointment.

Student drills into counselor → sees concrete open slots for that week → books.

Key functionalities (by module)
Users & Auth

BCrypt (password_hash/password_verify), rate-limited login.

Separate admin login area and session.

Counselors

Profile: name, specialty, meeting mode; optional bio/location.

Availability: simple slot management (create/delete). MVP can be static/seeded if time is tight.

Appointments

Create (Student) → Pending.

Approve/Decline (Counselor/Admin).

Auto-create Session on Approve.

Statuses: PENDING, APPROVED, DECLINED, CANCELLED, COMPLETED (only via session end).

Sessions

States: SCHEDULED → IN_PROGRESS → COMPLETED (or CANCELLED).

Start/End buttons gated by time window checks.

Notes

Counselor-authored; PRIVATE or PUBLISHED.

Students only see PUBLISHED and only their own sessions’ notes.

Feedback

Student-only; allowed iff session.status=COMPLETED and student_id matches session’s appointment.

One feedback per session (DB unique constraint).
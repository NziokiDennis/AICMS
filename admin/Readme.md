Admin Module Summary - Happy Hearts Counseling System
Overall Structure & Navigation
Admin Authentication

Separate Login: /admin/auth/login.php (distinct from student/counselor login)
Session Isolation: Uses sess_admin cookies, completely separate from regular user sessions
Red Theme: Danger/red color scheme throughout to distinguish from main site

Navigation Header (admin/includes/admin_header.php)

Brand: "Admin Portal" with shield icon
Main Navigation:

Dashboard
Manage Users
Manage Appointments
Reports (dropdown with 5 report types)


User Menu: Admin name + logout option
Styling: Bootstrap navbar with bg-danger theme


Core Admin Pages
1. Admin Dashboard (/admin/dashboard.php)
Purpose: System overview and quick actions
Key Features:

Alert System: Shows stuck sessions, old pending appointments
Stats Cards: Total users, pending appointments, completed today, average rating
Monthly Trends Chart: Last 6 months appointment data using Chart.js
Recent Activity Feed: Last 24 hours of appointments and feedback
Quick Action Buttons: Add user, review appointments, system health, export reports

2. User Management (/admin/manage_users.php)
Purpose: Full CRUD operations on all users
Key Features:

Role-based Stats Cards: Student/Counselor/Admin counts
Advanced Filtering: Search by name/email, filter by role
User Creation: Dropdown to "Add Student/Counselor/Admin"

Dynamic form fields (counselor profile fields appear for counselors)


Full Edit Capabilities: Update user info, change roles, manage counselor profiles
Password Reset: Admin can reset any user's password
Safe Deletion: Prevents deletion if user has appointments
User Statistics: Shows appointment counts, ratings for each user

3. Appointment Management (/admin/manage_appointments.php)
Purpose: Override all appointments with full admin powers
Key Features:

Stuck Session Alerts: Prominent warning for sessions running >2 hours
Status Overview: Cards showing counts by appointment status
Advanced Filtering: Status, counselor, date range filters
Admin Override Actions:

Force approve pending appointments
Force decline with reason
Cancel any appointment with reason
Force mark as completed
Reschedule appointments (change date/time)
Fix stuck sessions (end or cancel)


Comprehensive View: Shows appointment details, participants, session info, all statuses


5 Key Reports System (/admin/reports/)
1. Usage Analytics (usage_analytics.php)

Monthly appointment trends with Chart.js
Peak booking hours and days
Popular specialties and meeting modes
Completion rates and student engagement metrics
CSV export functionality

2. Counselor Performance (counselor_performance.php)

Individual counselor statistics and ratings
Session completion rates
Student feedback summaries
Utilization rates and availability analysis

3. Student Engagement (student_engagement.php)

Active vs inactive students
Repeat booking patterns
No-show rates and engagement trends
Student satisfaction metrics

4. System Health (system_health.php)

Stuck sessions and system issues detection
Data integrity checks
Pending approvals requiring attention
Automated fixes and maintenance tools

5. Compliance Report (compliance_report.php)

Session duration compliance
Documentation rates (notes, feedback)
Export-ready reports for administrative oversight
Audit trails and data completeness


Technical Implementation
Security & Access Control

Admin Auth Check: requireAdminAuth() on every page
CSRF Protection: All POST forms have CSRF tokens
Input Validation: Comprehensive validation on all admin actions
Audit Logging: Track all admin actions for accountability

UI/UX Consistency

Bootstrap 5: Consistent with main site styling
Red/Danger Theme: Distinguishes admin area
Chart.js Integration: Interactive charts and visualizations
Modal Workflows: Edit/delete actions use Bootstrap modals
Responsive Design: Works on desktop and mobile
Font Awesome Icons: Consistent iconography

Database Operations

Full CRUD Powers: Admin can create, read, update, delete everything
Transaction Safety: Critical operations wrapped in database transactions
Constraint Handling: Proper handling of foreign key constraints
Bulk Operations: Efficient queries for large datasets

Export & Reporting

CSV Export: All reports exportable to CSV
Date Range Filtering: Flexible time period selection
Real-time Data: Live statistics and metrics
Visual Analytics: Charts and graphs for data presentation


Admin Capabilities Summary
User Powers

Create students, counselors, and admins
Edit any user's profile and role
Reset passwords for any user
View user statistics and engagement
Delete users (with safety checks)

Appointment Powers

Override any appointment status
Force approve/decline appointments
Cancel appointments with reasons
Reschedule appointments to new times
Fix stuck or problematic sessions
View all appointment details

System Management

Monitor system health and performance
Resolve stuck sessions automatically
Generate comprehensive reports
Export data for external analysis
Track system usage patterns

Reporting & Analytics

5 specialized report types
Interactive charts and visualizations
CSV exports for all data
Flexible date range filtering
Real-time statistics and metrics

The admin system provides complete administrative control while maintaining security, usability, and comprehensive oversight of the counseling platform.
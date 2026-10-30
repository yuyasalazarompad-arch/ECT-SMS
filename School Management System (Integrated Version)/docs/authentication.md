# Authentication and role-based access

The campus sign-in uses the unified `school_management.campus_users` directory.
Each account has one role, and that role determines which workspaces the account
may open.

## Roles

| Database role | Session key | Workspaces |
| --- | --- | --- |
| Super Admin | `super_admin` | Academic, Attendance, Assessment, Finance, Enrollment |
| Admin | `admin` | Academic, Attendance, Assessment, Finance, Enrollment |
| Academic Admin | `academic_admin` | Academic |
| Attendance Admin | `attendance_admin` | Attendance |
| Assessment Admin | `assessment_admin` | Assessment |
| Finance Admin | `finance_admin` | Finance |
| Enrollment Admin | `enrollment_admin` | Enrollment |
| Teacher | `teacher` | Attendance, Assessment |
| Student | `student` | Assessment, Enrollment |

## Demo accounts

These accounts are seeded by `backend/config/seed_demo_accounts.php`. Demo
credentials are only shown on the login page for loopback clients
(`127.0.0.1` / `::1`), or when `APP_DEMO_MODE=1` is set.

| Username | Password | Role |
| --- | --- | --- |
| `fbcadmin` | `fbcadmin123` | Super Admin |
| `admin` | `admin123` | Admin |
| `academic.admin` | `academic123` | Academic Admin |
| `attendance.admin` | `attendance123` | Attendance Admin |
| `assessment.admin` | `assessment123` | Assessment Admin |
| `finance.admin` | `finance123` | Finance Admin |
| `enrollment.admin` | `enrollment123` | Enrollment Admin |
| `teacher` | `teacher123` | Teacher |
| `student` | `student123` | Student |

Change or remove these accounts before deploying the system.

## Files

| File | Purpose |
| --- | --- |
| `frontend/login.php` | Campus sign-in screen |
| `frontend/dashboard.php` | Role-aware landing page |
| `frontend/workspace.php` | Access-checked workspace route; redirects Finance, Attendance, and Enrollment to their module folders |
| `frontend/src/pages/attendance/` | Attendance & Scheduling workspace (guarded) |
| `frontend/src/pages/enrollment/` | Enrollment & Student Portal (public admissions pages) |
| `frontend/src/pages/finance/` | Finance & Reporting workspace (guarded) |
| `frontend/logout.php` | CSRF-protected sign out |
| `frontend/includes/auth.php` | Sessions, role map, guards, CSRF, throttling |
| `backend/config/database.php` | PDO connection to `school_management` |
| `backend/config/seed_demo_accounts.php` | Seeds one account per role |
| `databases/20261030_role_based_access.sql` | Extends the role enum |
| `frontend/index.php` | Redirects to login or dashboard |

## Protecting a module page

When a module page is connected, put the guard before any output:

```php
<?php
require_once __DIR__ . '/../../../includes/auth.php'; // adjust depth
auth_require_system('attendance');
```

`auth_require_system()` redirects guests to the login page and returns HTTP 403
when a signed-in account does not have that workspace in its role.

Module workspaces redirect from `workspace.php` to their own folders: Finance,
Attendance & Scheduling, and Enrollment & Student Portal. Attendance pages keep
the `auth_require_system('attendance')` guard. The enrollment pages are public by
design so applicants can file without an account; the Student Login link sends
them to the campus sign-in above.

## Setup commands

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "source databases/20261030_role_based_access.sql"
C:\xampp\php\php.exe backend\config\seed_demo_accounts.php
```

Open `http://localhost/School%20Management%20System%20(Integrated%20Version)/frontend/`
to reach the sign-in page.

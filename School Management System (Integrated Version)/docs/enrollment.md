# Enrollment & Student Portal module

The enrollment workspace is the public admissions front door for Fullbright
College. Applicants can read the process, prepare requirements, and file an
application without an account; enrolled students are sent to the campus login.

## Routes

All pages live in `frontend/src/pages/enrollment/` and share `_bootstrap.php`,
`partials/header.php`, `partials/footer.php`, and `partials/icons.php`.

| Page | URL | Purpose |
| --- | --- | --- |
| Home | `index.php` | Admissions overview, key dates, process, requirements, programs |
| Student Enrollment Form — step 1 | `enroll.php` | Instructions, stepper, document list, "Before you begin" checklist |
| Student Enrollment Form — step 2 | `application.php` | Personal information form; saves the application and shows a receipt |
| Student Login | link to `frontend/login.php` | Reuses the existing campus sign-in and role-based dashboard |

The old access URL `frontend/workspace.php?system=enrollment` redirects here.

## Access

The pages are intentionally public: there is no `auth_require_system()` guard.
`_bootstrap.php` still loads `frontend/includes/auth.php` so the header can:

- show the current account and a sign-out menu when a user is signed in;
- send guests to the campus login through `enr_login_url()`;
- reuse the campus CSRF token for the application form.

## Data

`backend/enrollment/EnrollmentRepository.php` owns every query:

| Method | Purpose |
| --- | --- |
| `programs()` | The four ECT programs, blurbs, and career tags |
| `applicationCount()` / `verifiedCount()` | Home page counters |
| `programCounts()` | "On file" count per program code |
| `emailExists()` | Prevents duplicate applications |
| `createApplication()` | Inserts a row into `enrollment_students` and returns the generated student number |

Student numbers follow `YY` + `01` + a six-digit sequence (for example
`2601010002`). The department is fixed to
`Engineering & Communication Technology (ECT)`.

## Application flow

`enroll.php` is the step 1 screen: it explains email verification, personal
information, document upload, and the entrance exam, then links to
`application.php`.

`application.php` validates the CSRF token, required names, email format and
uniqueness, birthday, contact numbers, and program before inserting the
applicant as `email_verified = 0`. On success it redirects to
`application.php?done=1`, where the receipt (student number) and the next three
actions are shown. Document upload and the entrance exam remain registrar-led
steps, so the page never claims a completed enrollment prematurely.

## Front-end behaviour

`frontend/src/assets/js/enrollment.js` provides:

- the live Manila clock;
- accessible dropdown menus;
- the "Before you begin" checklist, persisted in `localStorage` per device;
- same-as-permanent address syncing;
- client-side required-field validation with an inline summary and focus
  management before the form is posted.

Styles live in `frontend/src/assets/css/enrollment.css` using the same paper
grid, navy topbar, cream cards, and Fraunces headings as the rest of the
integrated system.

## Quick checks

```powershell
C:\xampp\php\php.exe -l frontend\src\pages\enrollment\enroll.php
C:\xampp\php\php.exe -l frontend\src\pages\enrollment\application.php
C:\xampp\php\php.exe -l backend\enrollment\EnrollmentRepository.php
```

Open `http://localhost/School%20Management%20System%20(Integrated%20Version)/frontend/src/pages/enrollment/`.

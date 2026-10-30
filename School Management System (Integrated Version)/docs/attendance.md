# Attendance & Scheduling module

The attendance workspace turns the `attendance_*` tables into a daily operations
desk: take attendance, review timetables, watch rooms, and clear conflicts.

The interface uses a light blue institutional design system: a blue gradient top
bar with a red ribbon accent, a white sticky sub-navigation bar with a red active
underline, a pale blue canvas, and white cards with a navy/red top rule. Headings
use Plus Jakarta Sans and body copy uses Inter.

## Routes

All pages live in `frontend/src/pages/attendance/` and share
`_bootstrap.php`, `partials/header.php`, and `partials/footer.php`.

| Page | URL | Purpose |
| --- | --- | --- |
| Attendance Overview / Take Attendance | `index.php` → `dashboard.php` | Filter by subject, section, year, and date; mark P/L/A/E; save or export |
| Teacher Schedule | `teacher-schedule.php` | Hourly teaching timeline, availability, and daily load for one teacher |
| Student Schedule | `student-schedule.php` | Week grid and weekly allocation for one section |
| Room Monitoring | `rooms.php` | Floor plan and today's occupancy by building and level |
| Conflict Detection | `conflicts.php` | Automatic overlap checks and manually reported issues |
| CSV export | `export.php?subject=&section=&date=` | One row per student for the selected class and date |

## Access

`_bootstrap.php` calls `auth_require_system('attendance')`. Super Admin, Admin,
Attendance Admin, and Teacher accounts may open the workspace; everyone else is
redirected to login or receives HTTP 403. The old access URL
`frontend/workspace.php?system=attendance` redirects here.

## Data

`backend/attendance/AttendanceRepository.php` is the only file that queries the
database:

| Table | Used for |
| --- | --- |
| `attendance_subjects` | Subject filter, class labels, and units |
| `attendance_sections` (derived from students) | Section and year filters |
| `attendance_students` + `attendance_users` | Roster names and student numbers |
| `attendance_teachers` + `attendance_users` | Teacher schedules and departments |
| `attendance_schedules` | Class day, time, room, section, status |
| `attendance_rooms` | Room monitoring board, building, floor, and type |
| `attendance_attendance` | Saved P/L/A/E marks, one row per student/schedule/date |
| `attendance_manual_conflicts` | Reported conflicts and their resolution state |
| `attendance_conflict_acknowledgements` | Acknowledgement keys that hide a conflict from the open queue |

### Default selections

| Page | Default |
| --- | --- |
| Attendance Overview | `CS101`, `BSCS-1A`, Year 1, today in Asia/Manila |
| Teacher Schedule | `EMP-001`, today if they teach today, otherwise their first teaching day |
| Student Schedule | BS Computer Science, Year 1, `BSCS-1A`, all meeting days |
| Room Monitoring | Building D, Level 3 (first available building/floor as fallback) |
| Conflict Detection | Automatic tab, all types, acknowledged items hidden |

The student schedule only renders meeting days that actually have classes, and
its time rows follow the section's real class start times. Program names are
shortened in the filter, for example `Bachelor of Science in Computer Science`
reads `BS Computer Science`.

## Saving attendance

`dashboard.php` posts the roster to itself with a CSRF token, the resolved
`schedule_id`, the date, and `status[<student_id>]` values. The page:

1. verifies the CSRF token;
2. confirms the posted schedule matches the selected subject/section;
3. keeps only student IDs present in the visible roster;
4. upserts into `attendance_attendance` through the repository.

A successful save flashes a confirmation and redirects back to the same filters.
The Export CSV button streams the current roster for the selected class and date.

## Teacher schedule

`teacher-schedule.php` combines a 7:00 AM – 5:00 PM hourly timeline with a side
panel and a weekly load table:

- Filters: department, teacher, and day.
- Timeline: class cards positioned by start/end time; open blocks of 30 minutes
  or longer inside the day are marked **Available**.
- Day at a glance: classes, subjects, units, free slots, first/last class, contact
  hours, rooms used, and load status.
- Daily Load table: day, classes, subjects, units, free slots, and status for the
  whole week, plus a weekly total row.

Load status uses the scheduling policy: more than 6 classes is **Overload**, 5–6
is **Full Load**, 1–4 is **Balanced**, and 0 is **Free**. Free slots count open
gaps of at least 30 minutes between 7:00 AM and 5:00 PM.

## Student schedule

`student-schedule.php` shows a week grid plus a Weekly Allocation table:

- Filters: program, year level, section, and days (all, weekdays, or Saturday).
- Week grid: one column per meeting day, one row per distinct class start time.
- Weekly Allocation: day, classes, time allocated, rooms allocated, and status
  (Scheduled, Heavy above 4 classes, or Free), plus a weekly total.

## Room monitoring

`rooms.php` renders a single floor-plan grid with a hallway/stairs band:

- Building A–D and floor segmented controls.
- Instant room search that filters tiles client-side and updates the shown count.
- Occupancy summary: rooms shown, in use today, available today, and occupancy %.
- Room tiles ordered Room → Lab → Facility, each showing weekly bookings, today's
  classes, and facility notes.
- An **Updated** timestamp and building/level/legend header.

Room state is based on `attendance_schedules` for the current weekday. Because
schedules store only the room name, matching is by name across buildings.

## Conflict detection

`conflicts.php` has two tabs:

### Automatic

Scans every pair of schedules for the same day with overlapping time and flags:

- **Room** double-booking,
- **Teacher** double-booking,
- **Section** double-booking.

Each detected conflict has a stable key (`auto|Type|Day|lowScheduleId|highScheduleId`).
Clicking **Acknowledge** writes that key to
`attendance_conflict_acknowledgements`, which removes it from the default open
queue without resolving the underlying schedule. **Re-scan Schedule** re-runs the
check and reports the current count.

### Manual

Shows open rows from `attendance_manual_conflicts`. **Report Conflict** opens a
dialog (type, day, reference, severity, title, description) and inserts a new
open record. Manual rows can be **Acknowledged** (hidden from the open queue) and
**Resolved** (sets `status = 'Resolved'` and stores `resolution_note`).

### Filters and policy

- Type filter: Room, Teacher, Section, or Other.
- **Show acknowledged** toggle ( `ack=1` ) reveals acknowledged items.
- The scheduling policy panel checks four seeded-data rules:
  1. teacher daily class cap — max 6 classes per teacher per day;
  2. teacher subject spread — max 6 subjects per teacher per day;
  3. section daily load — 1 to 4 classes per section per day;
  4. room double-booking — zero overlaps.

When every rule passes the panel shows **All rules met**; otherwise it shows
**Review required** and marks the failing rules.

## Front-end behaviour

`frontend/src/assets/js/attendance.js` provides:

- the live Manila clock in the top bar;
- one-at-a-time dropdown menus that close on outside click or Escape;
- live P/L/A/E status chips and the four summary counters;
- auto-submitting selects (and the acknowledged toggle) on every page;
- section/year filter sync and auto-submit on date change;
- instant room search with hallway and empty-state handling;
- the Report Conflict dialog open/close controls and print actions.

Styles live in `frontend/src/assets/css/attendance.css` (light blue canvas, blue
gradient topbar, white cards, red accents, Plus Jakarta Sans / Inter). On narrow
screens the conflict, daily-load, and allocation tables collapse into stacked
cards, and the student week grid sizes to the number of meeting days.

## Quick checks

```powershell
C:\xampp\php\php.exe -l frontend\src\pages\attendance\dashboard.php
C:\xampp\php\php.exe -l frontend\src\pages\attendance\conflicts.php
C:\xampp\php\php.exe -l backend\attendance\AttendanceRepository.php
node --check frontend\src\assets\js\attendance.js
```

Then sign in as `attendance.admin` / `attendance123` and open
`http://localhost/School%20Management%20System%20(Integrated%20Version)/frontend/src/pages/attendance/`.

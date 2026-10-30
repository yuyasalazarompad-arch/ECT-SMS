<?php
declare(strict_types=1);

/**
 * Attendance & Scheduling data layer.
 *
 * Works with the existing attendance tables in school_management. The module
 * keeps every query here so the pages only deal with presentation.
 */
final class AttendanceRepository
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    public const STATUSES = ['Present', 'Late', 'Absent', 'Excused'];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array{id: int, code: string, name: string, units: float, year: int}>
     */
    public function subjects(): array
    {
        $rows = $this->pdo->query(
            'SELECT subject_id, subject_code, subject_name, units, year_level
               FROM attendance_subjects
              ORDER BY subject_code ASC'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['subject_id'],
            'code' => (string) $row['subject_code'],
            'name' => (string) $row['subject_name'],
            'units' => (float) $row['units'],
            'year' => max(1, (int) ($row['year_level'] ?? 1)),
        ], $rows);
    }

    /**
     * @return list<array{section: string, course: string, year: int, students: int}>
     */
    public function sections(): array
    {
        $rows = $this->pdo->query(
            'SELECT section, course, year_level, COUNT(*) AS students
               FROM attendance_students
              WHERE section IS NOT NULL AND section <> \'\'
              GROUP BY section, course, year_level
              ORDER BY section ASC'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'section' => (string) $row['section'],
            'course' => (string) $row['course'],
            'year' => (int) $row['year_level'],
            'students' => (int) $row['students'],
        ], $rows);
    }

    /**
     * Students assigned to one section.
     *
     * @return list<array{id: int, no: string, name: string, course: string, year: int, section: string}>
     */
    public function students(string $section): array
    {
        $statement = $this->pdo->prepare(
            'SELECT s.student_id, s.student_number, s.course, s.year_level, s.section, u.full_name
               FROM attendance_students s
               JOIN attendance_users u ON u.user_id = s.user_id
              WHERE s.section = :section
              ORDER BY u.full_name ASC, s.student_number ASC'
        );
        $statement->execute([':section' => $section]);

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['student_id'],
            'no' => (string) $row['student_number'],
            'name' => (string) $row['full_name'],
            'course' => (string) $row['course'],
            'year' => (int) $row['year_level'],
            'section' => (string) $row['section'],
        ], $statement->fetchAll());
    }

    /**
     * The first timetable row for one subject and section.
     *
     * @return array<string, mixed>|null
     */
    public function scheduleFor(int $subjectId, string $section): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT sch.schedule_id, sch.subject_id, sch.teacher_id, sch.day, sch.start_time, sch.end_time,
                    sch.room, sch.section, sch.class_status,
                    sub.subject_code, sub.subject_name, sub.units, sub.year_level,
                    t.employee_number, u.full_name AS teacher_name
               FROM attendance_schedules sch
               JOIN attendance_subjects sub ON sub.subject_id = sch.subject_id
               JOIN attendance_teachers t ON t.teacher_id = sch.teacher_id
               JOIN attendance_users u ON u.user_id = t.user_id
              WHERE sch.subject_id = :subject AND sch.section = :section
              ORDER BY FIELD(sch.day, \'Monday\',\'Tuesday\',\'Wednesday\',\'Thursday\',\'Friday\',\'Saturday\',\'Sunday\'),
                       sch.start_time ASC
              LIMIT 1'
        );
        $statement->execute([':subject' => $subjectId, ':section' => $section]);
        $row = $statement->fetch();

        return $row === false ? null : $this->scheduleRow($row);
    }

    /**
     * Saved attendance for one schedule and date.
     *
     * @return array<int, string> student id => status
     */
    public function attendanceFor(int $scheduleId, string $date): array
    {
        if ($scheduleId <= 0 || !$this->isDate($date)) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT student_id, status
               FROM attendance_attendance
              WHERE schedule_id = :schedule AND attendance_date = :date'
        );
        $statement->execute([':schedule' => $scheduleId, ':date' => $date]);

        $marks = [];
        foreach ($statement->fetchAll() as $row) {
            $marks[(int) $row['student_id']] = (string) $row['status'];
        }

        return $marks;
    }

    /**
     * Create or update attendance rows.
     *
     * @param array<int|string, string> $statuses student id => status
     * @return int number of rows written
     */
    public function save(int $scheduleId, string $date, array $statuses): int
    {
        if ($scheduleId <= 0 || !$this->isDate($date) || $statuses === []) {
            return 0;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO attendance_attendance (student_id, schedule_id, attendance_date, status)
             VALUES (:student, :schedule, :date, :status)
             ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()'
        );

        $written = 0;
        $this->pdo->beginTransaction();

        try {
            foreach ($statuses as $studentId => $status) {
                $studentId = (int) $studentId;
                $status = ucfirst(strtolower(trim((string) $status)));

                if ($studentId <= 0 || !in_array($status, self::STATUSES, true)) {
                    continue;
                }

                $statement->execute([
                    ':student' => $studentId,
                    ':schedule' => $scheduleId,
                    ':date' => $date,
                    ':status' => $status,
                ]);
                $written++;
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $written;
    }

    /**
     * Timetable rows, optionally filtered to one teacher.
     *
     * @return list<array<string, mixed>>
     */
    public function teacherSchedules(?int $teacherId = null): array
    {
        $sql = 'SELECT sch.schedule_id, sch.teacher_id, sch.day, sch.start_time, sch.end_time, sch.room,
                       sch.section, sch.class_status,
                       sub.subject_code, sub.subject_name, sub.units, sub.year_level,
                       t.employee_number, u.full_name AS teacher_name
                  FROM attendance_schedules sch
                  JOIN attendance_subjects sub ON sub.subject_id = sch.subject_id
                  JOIN attendance_teachers t ON t.teacher_id = sch.teacher_id
                  JOIN attendance_users u ON u.user_id = t.user_id';
        $parameters = [];

        if ($teacherId !== null && $teacherId > 0) {
            $sql .= ' WHERE sch.teacher_id = :teacher';
            $parameters[':teacher'] = $teacherId;
        }

        $sql .= ' ORDER BY FIELD(sch.day, \'Monday\',\'Tuesday\',\'Wednesday\',\'Thursday\',\'Friday\',\'Saturday\',\'Sunday\'),
                           sch.start_time ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return array_map(fn (array $row): array => $this->scheduleRow($row), $statement->fetchAll());
    }

    /**
     * @return list<array{id: int, name: string, employee_number: string, department: string}>
     */
    public function teachers(): array
    {
        $rows = $this->pdo->query(
            'SELECT t.teacher_id, t.employee_number, t.department, u.full_name
               FROM attendance_teachers t
               JOIN attendance_users u ON u.user_id = t.user_id
              ORDER BY u.full_name ASC'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['teacher_id'],
            'name' => (string) $row['full_name'],
            'employee_number' => (string) $row['employee_number'],
            'department' => (string) ($row['department'] ?? ''),
        ], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sectionSchedules(string $section): array
    {
        $statement = $this->pdo->prepare(
            'SELECT sch.schedule_id, sch.teacher_id, sch.day, sch.start_time, sch.end_time, sch.room,
                    sch.section, sch.class_status,
                    sub.subject_code, sub.subject_name, sub.units, sub.year_level,
                    t.employee_number, u.full_name AS teacher_name
               FROM attendance_schedules sch
               JOIN attendance_subjects sub ON sub.subject_id = sch.subject_id
               JOIN attendance_teachers t ON t.teacher_id = sch.teacher_id
               JOIN attendance_users u ON u.user_id = t.user_id
              WHERE sch.section = :section
              ORDER BY FIELD(sch.day, \'Monday\',\'Tuesday\',\'Wednesday\',\'Thursday\',\'Friday\',\'Saturday\',\'Sunday\'),
                       sch.start_time ASC'
        );
        $statement->execute([':section' => $section]);

        return array_map(fn (array $row): array => $this->scheduleRow($row), $statement->fetchAll());
    }

    /**
     * @return list<string>
     */
    public function buildings(): array
    {
        $rows = $this->pdo->query(
            'SELECT DISTINCT building FROM attendance_rooms ORDER BY building ASC'
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $rows);
    }

    /**
     * @return list<string>
     */
    public function departments(): array
    {
        $rows = $this->pdo->query(
            "SELECT DISTINCT department
               FROM attendance_teachers
              WHERE department IS NOT NULL AND department <> ''
              ORDER BY department ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strval', $rows);
    }

    /**
     * Per-day teaching load for one teacher (or everyone).
     *
     * @return array<string, array{day: string, classes: int, subjects: int, units: float, minutes: int, free_slots: int, status: string}>
     */
    public function dailyLoad(?int $teacherId = null): array
    {
        $schedules = $this->teacherSchedules($teacherId);
        $load = [];

        foreach (self::DAYS as $day) {
            $rows = array_values(array_filter(
                $schedules,
                static fn (array $row): bool => $row['day'] === $day
            ));

            if ($rows === []) {
                continue;
            }

            $subjects = [];
            $units = 0.0;
            $minutes = 0;
            $blocks = [];

            foreach ($rows as $row) {
                $subjects[(int) $row['subject_id']] = true;
                $units += (float) $row['units'];
                $start = $this->minutesOf((string) $row['start_time']);
                $end = max($start, $this->minutesOf((string) $row['end_time']));
                $minutes += $end - $start;
                $blocks[] = [$start, $end];
            }

            $load[$day] = [
                'day' => $day,
                'classes' => count($rows),
                'subjects' => count($subjects),
                'units' => $units,
                'minutes' => $minutes,
                'free_slots' => $this->freeSlots($blocks),
                'status' => $this->loadStatus(count($rows)),
            ];
        }

        return $load;
    }

    /**
     * Rooms on one building floor with today's classes and weekly usage.
     *
     * @return list<array{name: string, building: string, floor: int, type: string, description: string, weekly: int, classes: list<array<string, mixed>>}>
     */
    public function roomBoard(string $building = '', ?int $floor = null): array
    {
        $sql = 'SELECT room_name, building, floor, room_type, description
                  FROM attendance_rooms';
        $where = [];
        $parameters = [];

        if ($building !== '') {
            $where[] = 'building = :building';
            $parameters[':building'] = $building;
        }
        if ($floor !== null && $floor > 0) {
            $where[] = 'floor = :floor';
            $parameters[':floor'] = $floor;
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY building ASC, floor ASC, FIELD(room_type, 'Room', 'Lab', 'Facility') ASC, room_name ASC";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        $today = strtolower((new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('l'));
        $byRoom = [];
        $weekly = [];

        foreach ($this->teacherSchedules() as $schedule) {
            $key = strtolower((string) $schedule['room']);
            if ($key === '') {
                continue;
            }

            $weekly[$key] = ($weekly[$key] ?? 0) + 1;

            if (strtolower((string) $schedule['day']) === $today) {
                $byRoom[$key][] = $schedule;
            }
        }

        $rooms = [];

        foreach ($statement->fetchAll() as $row) {
            $key = strtolower((string) $row['room_name']);
            $rooms[] = [
                'name' => (string) $row['room_name'],
                'building' => (string) $row['building'],
                'floor' => (int) $row['floor'],
                'type' => (string) $row['room_type'],
                'description' => (string) ($row['description'] ?? ''),
                'weekly' => $weekly[$key] ?? 0,
                'classes' => $byRoom[$key] ?? [],
            ];
        }

        return $rooms;
    }

    /**
     * @return list<int>
     */
    public function floors(string $building): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT floor FROM attendance_rooms WHERE building = :building ORDER BY floor ASC'
        );
        $statement->execute([':building' => $building]);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Overlapping room, teacher, or section bookings detected in the timetable.
     *
     * @return list<array<string, mixed>>
     */
    public function automaticConflicts(): array
    {
        $schedules = $this->teacherSchedules();
        $acks = $this->acknowledgedKeys();
        $conflicts = [];
        $seen = [];

        $count = count($schedules);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $schedules[$i];
                $right = $schedules[$j];
                if ($left['day'] !== $right['day']) {
                    continue;
                }
                if (!$this->overlaps((string) $left['start_time'], (string) $left['end_time'], (string) $right['start_time'], (string) $right['end_time'])) {
                    continue;
                }

                $pairs = [
                    ['Room', (string) $left['room'], (string) $right['room'], $left['room'] === $right['room'] && $left['room'] !== ''],
                    ['Teacher', (string) $left['teacher_name'], (string) $right['teacher_name'], $left['teacher_id'] === $right['teacher_id']],
                    ['Section', (string) $left['section'], (string) $right['section'], $left['section'] === $right['section']],
                ];

                foreach ($pairs as [$type, $leftValue, $rightValue, $matches]) {
                    if (!$matches) {
                        continue;
                    }

                    $low = min((int) $left['schedule_id'], (int) $right['schedule_id']);
                    $high = max((int) $left['schedule_id'], (int) $right['schedule_id']);
                    $key = 'auto|' . $type . '|' . $left['day'] . '|' . $low . '|' . $high;

                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;

                    $conflicts[] = [
                        'key' => $key,
                        'id' => null,
                        'type' => $type,
                        'day' => (string) $left['day'],
                        'reference' => $type === 'Teacher' ? (string) $left['teacher_name'] : $leftValue,
                        'title' => $type . ' overlap: ' . $left['subject_code'] . ' and ' . $right['subject_code'],
                        'description' => sprintf(
                            '%s (%s) and %s (%s) both run %s to %s.',
                            $left['subject_code'],
                            $left['section'],
                            $right['subject_code'],
                            $right['section'],
                            $this->timeLabel((string) $left['start_time']),
                            $this->timeLabel((string) $left['end_time'])
                        ),
                        'severity' => 'error',
                        'source' => 'Automatic',
                        'acknowledged' => isset($acks[$key]),
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Manually reported scheduling issues.
     *
     * @return list<array<string, mixed>>
     */
    public function manualConflicts(bool $includeResolved = false): array
    {
        $sql = 'SELECT conflict_id, conflict_type, day, reference, title, description, severity, status,
                       resolution_note, reported_by, created_at
                  FROM attendance_manual_conflicts';

        if (!$includeResolved) {
            $sql .= " WHERE status = 'Open'";
        }

        $sql .= " ORDER BY FIELD(severity, 'error', 'warn'), created_at DESC";

        $acks = $this->acknowledgedKeys();
        $conflicts = [];

        foreach ($this->pdo->query($sql)->fetchAll() as $row) {
            $key = 'manual|' . (int) $row['conflict_id'];
            $conflicts[] = [
                'key' => $key,
                'id' => (int) $row['conflict_id'],
                'type' => (string) $row['conflict_type'],
                'day' => (string) ($row['day'] ?? ''),
                'reference' => (string) ($row['reference'] ?? ''),
                'title' => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
                'severity' => (string) $row['severity'],
                'status' => (string) $row['status'],
                'reported_by' => (string) ($row['reported_by'] ?? ''),
                'created_at' => (string) $row['created_at'],
                'resolution_note' => (string) ($row['resolution_note'] ?? ''),
                'source' => 'Manual',
                'acknowledged' => isset($acks[$key]),
            ];
        }

        return $conflicts;
    }

    /**
     * @return array<string, bool>
     */
    public function acknowledgedKeys(): array
    {
        $rows = $this->pdo->query(
            'SELECT conflict_key FROM attendance_conflict_acknowledgements'
        )->fetchAll(PDO::FETCH_COLUMN);

        $keys = [];
        foreach ($rows as $row) {
            $keys[(string) $row] = true;
        }

        return $keys;
    }

    public function acknowledgeConflict(string $key, string $note = ''): bool
    {
        if (trim($key) === '') {
            return false;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO attendance_conflict_acknowledgements (conflict_key, note)
             VALUES (:key, :note)
             ON DUPLICATE KEY UPDATE note = VALUES(note)'
        );

        return $statement->execute([
            ':key' => substr($key, 0, 191),
            ':note' => $note !== '' ? substr($note, 0, 255) : null,
        ]);
    }

    public function resolveManualConflict(int $id, string $note = ''): bool
    {
        if ($id <= 0) {
            return false;
        }

        $statement = $this->pdo->prepare(
            "UPDATE attendance_manual_conflicts
                SET status = 'Resolved', resolution_note = :note, updated_at = NOW()
              WHERE conflict_id = :id AND status = 'Open'"
        );

        return $statement->execute([
            ':id' => $id,
            ':note' => $note !== '' ? substr($note, 0, 255) : null,
        ]);
    }

    public function createManualConflict(
        string $type,
        string $day,
        string $reference,
        string $title,
        string $description,
        string $severity,
        string $reportedBy
    ): int {
        $types = ['Teacher', 'Room', 'Section', 'Other'];
        $days = ['', ...self::DAYS];
        $severities = ['error', 'warn'];

        if (!in_array($type, $types, true) || !in_array($day, $days, true) || !in_array($severity, $severities, true)) {
            return 0;
        }

        $title = trim($title);
        if ($title === '') {
            return 0;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO attendance_manual_conflicts
                (conflict_type, day, reference, title, description, severity, status, reported_by)
             VALUES (:type, :day, :reference, :title, :description, :severity, \'Open\', :reported_by)'
        );
        $statement->execute([
            ':type' => $type,
            ':day' => $day !== '' ? $day : null,
            ':reference' => $reference !== '' ? substr($reference, 0, 150) : null,
            ':title' => substr($title, 0, 150),
            ':description' => $description !== '' ? $description : null,
            ':severity' => $severity,
            ':reported_by' => $reportedBy !== '' ? substr($reportedBy, 0, 150) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function openManualConflicts(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM attendance_manual_conflicts WHERE status = 'Open'"
        )->fetchColumn();
    }

    public function openUnacknowledgedManualConflicts(): int
    {
        $acks = $this->acknowledgedKeys();
        $count = 0;

        foreach ($this->manualConflicts() as $conflict) {
            if (empty($acks[$conflict['key']])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Scheduling policy checks used by the Conflict Detection page.
     *
     * @return list<array{key: string, label: string, rule: string, actual: string, pass: bool}>
     */
    public function policyReport(): array
    {
        $teacherClasses = [];
        $teacherSubjects = [];
        $sectionClasses = [];

        foreach ($this->teacherSchedules() as $schedule) {
            $teacher = (int) $schedule['teacher_id'];
            $day = (string) $schedule['day'];
            $section = (string) $schedule['section'];

            $teacherClasses[$teacher][$day] = ($teacherClasses[$teacher][$day] ?? 0) + 1;
            $teacherSubjects[$teacher][$day][(int) $schedule['subject_id']] = true;
            if ($section !== '') {
                $sectionClasses[$section][$day] = ($sectionClasses[$section][$day] ?? 0) + 1;
            }
        }

        $maxTeacherClasses = 0;
        foreach ($teacherClasses as $days) {
            $maxTeacherClasses = max($maxTeacherClasses, ...array_values($days));
        }

        $maxTeacherSubjects = 0;
        foreach ($teacherSubjects as $days) {
            foreach ($days as $subjects) {
                $maxTeacherSubjects = max($maxTeacherSubjects, count($subjects));
            }
        }

        $maxSectionClasses = 0;
        $minSectionClasses = PHP_INT_MAX;
        foreach ($sectionClasses as $days) {
            foreach ($days as $count) {
                $maxSectionClasses = max($maxSectionClasses, $count);
                $minSectionClasses = min($minSectionClasses, $count);
            }
        }
        if ($minSectionClasses === PHP_INT_MAX) {
            $minSectionClasses = 0;
        }

        $automatic = $this->automaticConflicts();

        return [
            [
                'key' => 'teacher_classes',
                'label' => 'Teacher daily class cap',
                'rule' => 'Maximum 6 classes per teacher per day',
                'actual' => $maxTeacherClasses . ' classes peak',
                'pass' => $maxTeacherClasses <= 6,
            ],
            [
                'key' => 'teacher_subjects',
                'label' => 'Teacher subject spread',
                'rule' => 'Maximum 6 subjects per teacher per day',
                'actual' => $maxTeacherSubjects . ' subject' . ($maxTeacherSubjects === 1 ? '' : 's') . ' peak',
                'pass' => $maxTeacherSubjects <= 6,
            ],
            [
                'key' => 'section_load',
                'label' => 'Section daily load',
                'rule' => 'Between 1 and 4 classes per section per day',
                'actual' => $minSectionClasses === $maxSectionClasses
                    ? $minSectionClasses . ' class' . ($minSectionClasses === 1 ? '' : 'es')
                    : $minSectionClasses . '–' . $maxSectionClasses . ' classes',
                'pass' => $maxSectionClasses <= 4 && $minSectionClasses >= 1,
            ],
            [
                'key' => 'room_overlap',
                'label' => 'Room double-booking',
                'rule' => 'No room hosts two classes at the same time',
                'actual' => count($automatic) . ' overlaps',
                'pass' => $automatic === [],
            ],
        ];
    }

    /**
     * Backwards-compatible combined list.
     *
     * @return list<array<string, mixed>>
     */
    public function conflicts(): array
    {
        return array_merge($this->automaticConflicts(), $this->manualConflicts());
    }

    public function isDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    public function dayOf(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? '' : date('l', $timestamp);
    }

    public function timeLabel(string $time): string
    {
        $timestamp = strtotime($time);

        return $timestamp === false ? $time : date('g:i A', $timestamp);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function scheduleRow(array $row): array
    {
        return [
            'schedule_id' => (int) $row['schedule_id'],
            'teacher_id' => (int) ($row['teacher_id'] ?? 0),
            'day' => (string) $row['day'],
            'start_time' => (string) $row['start_time'],
            'end_time' => (string) $row['end_time'],
            'room' => (string) ($row['room'] ?? ''),
            'section' => (string) ($row['section'] ?? ''),
            'class_status' => (string) ($row['class_status'] ?? 'scheduled'),
            'subject_id' => (int) ($row['subject_id'] ?? 0),
            'subject_code' => (string) ($row['subject_code'] ?? ''),
            'subject_name' => (string) ($row['subject_name'] ?? ''),
            'units' => (float) ($row['units'] ?? 0),
            'year_level' => (int) ($row['year_level'] ?? 1),
            'employee_number' => (string) ($row['employee_number'] ?? ''),
            'teacher_name' => (string) ($row['teacher_name'] ?? ''),
        ];
    }

    private function overlaps(string $leftStart, string $leftEnd, string $rightStart, string $rightEnd): bool
    {
        return $leftStart < $rightEnd && $rightStart < $leftEnd;
    }

    private function minutesOf(string $time): int
    {
        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));
    }

    /**
     * Count the open gaps of 30 minutes or more inside the 7 AM – 5 PM day.
     *
     * @param list<array{0: int, 1: int}> $blocks
     */
    private function freeSlots(array $blocks): int
    {
        $dayStart = 7 * 60;
        $dayEnd = 17 * 60;

        if ($blocks === []) {
            return 10;
        }

        usort($blocks, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $cursor = $dayStart;
        $slots = 0;

        foreach ($blocks as [$start, $end]) {
            if ($start - $cursor >= 30) {
                $slots++;
            }
            $cursor = max($cursor, $end);
        }

        if ($dayEnd - $cursor >= 30) {
            $slots++;
        }

        return $slots;
    }

    private function loadStatus(int $classes): string
    {
        if ($classes > 6) {
            return 'Overload';
        }
        if ($classes >= 5) {
            return 'Full Load';
        }
        if ($classes >= 1) {
            return 'Balanced';
        }

        return 'Free';
    }
}

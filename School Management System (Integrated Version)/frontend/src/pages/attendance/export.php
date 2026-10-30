<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$subjectId = (int) ($_GET['subject'] ?? 0);
$section = trim((string) ($_GET['section'] ?? ''));
$date = (string) ($_GET['date'] ?? $attendanceToday);

if (!$attendanceRepository->isDate($date) || $section === '') {
    http_response_code(400);
    exit('Invalid attendance export request.');
}

$subject = null;
foreach ($attendanceRepository->subjects() as $row) {
    if ($row['id'] === $subjectId) {
        $subject = $row;
        break;
    }
}

if ($subject === null) {
    http_response_code(404);
    exit('Subject not found.');
}

$schedule = $attendanceRepository->scheduleFor($subjectId, $section);
$roster = $attendanceRepository->students($section);
$marks = $schedule !== null ? $attendanceRepository->attendanceFor((int) $schedule['schedule_id'], $date) : [];

$filename = 'attendance-' . preg_replace('/[^A-Za-z0-9-]+/', '-', $section) . '-' . $date . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$output = fopen('php://output', 'wb');
if ($output === false) {
    exit;
}

fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Student Number', 'Student Name', 'Course', 'Year', 'Section', 'Subject', 'Date', 'Status']);

foreach ($roster as $student) {
    fputcsv($output, [
        $student['no'],
        $student['name'],
        $student['course'],
        $student['year'],
        $student['section'],
        $subject['code'] . ' – ' . $subject['name'],
        $date,
        $marks[(int) $student['id']] ?? 'Unmarked',
    ]);
}

fclose($output);
exit;

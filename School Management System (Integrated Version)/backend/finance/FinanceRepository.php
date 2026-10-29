<?php
declare(strict_types=1);

/**
 * Finance & Reporting data layer.
 *
 * Reads the finance tables in school_management and turns each student's
 * enrollment details plus recorded payments into a term-by-term assessment.
 *
 * Tables (JSON-backed, matching the existing finance schema):
 *   finance_users     student/admin profiles
 *   finance_payments  payment records
 *   finance_settings  rate per unit + enrollment fee
 *   finance_notes     activity and announcements
 */
final class FinanceRepository
{
    public const SCHOOL_YEAR = '2026-2027';
    public const TERMS = ['Prelim', 'Midterm', 'Finals'];
    public const METHODS = ['Cash', 'GCash', 'Maya', 'Card', 'Bank'];

    /** Units per semester, indexed by year level and semester. */
    private const UNITS = [
        1 => [26, 24],
        2 => [24, 24],
        3 => [25, 24],
        4 => [21, 12],
    ];

    /** Due dates for the two semesters of school year 2026-2027. */
    private const DUE_DATES = [
        1 => ['2026-10-27', '2026-12-04', '2027-01-18'],
        2 => ['2027-03-15', '2027-04-26', '2027-06-07'],
    ];

    /** The published first-year, first-semester package. */
    private const FIXED_PACKAGE = [
        '1-1' => ['units' => 26, 'total' => 6900.0],
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Fee settings with safe defaults for a fresh install.
     *
     * @return array{rate: float, enroll: float, school_year: string}
     */
    public function settings(): array
    {
        $row = $this->pdo->query("SELECT data FROM finance_settings WHERE id = 'default' LIMIT 1")->fetch();
        $data = $row ? $this->decode((string) $row['data']) : [];

        return [
            'rate' => max(0.0, (float) ($data['rate'] ?? 300)),
            'enroll' => max(0.0, (float) ($data['enroll'] ?? 2000)),
            'school_year' => trim((string) ($data['school_year'] ?? self::SCHOOL_YEAR)) ?: self::SCHOOL_YEAR,
        ];
    }

    /**
     * Students from the finance directory.
     *
     * @return list<array{no: string, name: string, email: string, course: string, year: int, sem: int}>
     */
    public function students(): array
    {
        $rows = $this->pdo->query('SELECT data FROM finance_users ORDER BY id ASC')->fetchAll();
        $students = [];

        foreach ($rows as $row) {
            $data = $this->decode((string) $row['data']);
            if (strtolower((string) ($data['role'] ?? 'student')) !== 'student') {
                continue;
            }

            $no = trim((string) ($data['no'] ?? $data['student_no'] ?? $data['id'] ?? ''));
            $name = trim((string) ($data['name'] ?? $data['full_name'] ?? ''));

            if ($no === '' || $name === '') {
                continue;
            }

            $students[] = [
                'no' => $no,
                'name' => $name,
                'email' => trim((string) ($data['email'] ?? '')),
                'course' => trim((string) ($data['course'] ?? $data['program'] ?? 'Unassigned')),
                'year' => $this->normalizeYear($data['year'] ?? $data['grade_level'] ?? 1),
                'sem' => $this->normalizeSemester($data['sem'] ?? $data['semester'] ?? 1),
            ];
        }

        return $students;
    }

    /**
     * Every recorded payment, newest first.
     *
     * @return list<array{ref: string, no: string, term: string, method: string, amount: float, date: string, created_at: string}>
     */
    public function payments(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, student_no, data, created_at FROM finance_payments ORDER BY created_at ASC, id ASC'
        )->fetchAll();

        $payments = [];

        foreach ($rows as $row) {
            $data = $this->decode((string) $row['data']);
            $amount = round((float) ($data['amt'] ?? $data['amount'] ?? 0), 2);

            if ($amount <= 0) {
                continue;
            }

            $payments[] = [
                'ref' => trim((string) ($data['ref'] ?? $row['id'])),
                'no' => trim((string) ($data['no'] ?? $data['student_no'] ?? $row['student_no'])),
                'term' => trim((string) ($data['term'] ?? '')),
                'method' => trim((string) ($data['method'] ?? 'Cash')),
                'amount' => $amount,
                'date' => trim((string) ($data['date'] ?? substr((string) $row['created_at'], 0, 10))),
                'created_at' => (string) $row['created_at'],
            ];
        }

        usort($payments, static function (array $a, array $b): int {
            return [$b['date'], $b['created_at'], $b['ref']] <=> [$a['date'], $a['created_at'], $a['ref']];
        });

        return $payments;
    }

    /**
     * Notification/activity entries for a recipient.
     *
     * @return list<array{id: string, title: string, message: string, date: string, read: bool}>
     */
    public function notesFor(string $recipient): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, data FROM finance_notes WHERE student_no = :recipient ORDER BY created_at DESC, id DESC'
        );
        $statement->execute([':recipient' => $recipient]);

        $notes = [];

        foreach ($statement->fetchAll() as $row) {
            $data = $this->decode((string) $row['data']);
            $notes[] = [
                'id' => (string) ($data['id'] ?? $row['id']),
                'title' => trim((string) ($data['title'] ?? 'Finance update')),
                'message' => trim((string) ($data['msg'] ?? $data['message'] ?? '')),
                'date' => trim((string) ($data['date'] ?? '')),
                'read' => (bool) ($data['read'] ?? false),
            ];
        }

        return $notes;
    }

    /**
     * Assessment for one student, based on the current fee settings.
     *
     * @param array{no: string, name: string, course: string, year: int, sem: int} $student
     * @return array<string, mixed>
     */
    public function assess(array $student): array
    {
        $settings = $this->settings();
        $year = $this->normalizeYear($student['year'] ?? 1);
        $semester = $this->normalizeSemester($student['sem'] ?? 1);
        $package = self::FIXED_PACKAGE[$year . '-' . $semester] ?? null;
        $units = (int) ($package['units'] ?? self::UNITS[$year][$semester - 1]);
        $tuition = $package !== null && $settings['rate'] === 300.0
            ? (float) $package['total']
            : round($units * $settings['rate'], 2);
        $enrollment = min($settings['enroll'], $tuition);
        $balance = max(0.0, round($tuition - $enrollment, 2));

        // Split the remaining balance across the three terms: 50% / 25% / 25%.
        $amounts = [
            round($balance * 0.5, 2),
            round(($balance - round($balance * 0.5, 2)) * 0.5, 2),
        ];
        $amounts[] = round($balance - $amounts[0] - $amounts[1], 2);

        $paidByTerm = [];
        foreach ($this->payments() as $payment) {
            if ($payment['no'] !== $student['no']) {
                continue;
            }
            $paidByTerm[$payment['term']] = ($paidByTerm[$payment['term']] ?? 0.0) + $payment['amount'];
        }

        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        $terms = [];
        $termTotal = 0.0;
        $termPaid = 0.0;
        $overdue = false;

        foreach (self::TERMS as $index => $name) {
            $amount = $amounts[$index];
            $paid = round($paidByTerm[$name] ?? 0.0, 2);
            $termBalance = max(0.0, round($amount - $paid, 2));
            $due = self::DUE_DATES[$semester][$index];
            $isOverdue = $termBalance > 0 && $due < $today;
            $status = $termBalance <= 0 ? 'Paid' : ($isOverdue ? 'Overdue' : ($paid > 0 ? 'Partial' : 'Unpaid'));

            $terms[] = [
                'name' => $name,
                'amount' => $amount,
                'paid' => min($paid, $amount),
                'balance' => $termBalance,
                'due' => $due,
                'status' => $status,
            ];

            $termTotal += $amount;
            $termPaid += min($paid, $amount);
            $overdue = $overdue || $isOverdue;
        }

        $paid = round($enrollment + $termPaid, 2);
        $remaining = max(0.0, round($tuition - $paid, 2));
        $status = $remaining <= 0
            ? 'Paid'
            : ($overdue ? 'Overdue' : ($paid > 0 ? 'Partial' : 'Unpaid'));

        return [
            'units' => $units,
            'tuition' => $tuition,
            'enrollment' => $enrollment,
            'term_total' => round($termTotal, 2),
            'term_paid' => round($termPaid, 2),
            'paid' => $paid,
            'remaining' => $remaining,
            'progress' => $tuition > 0 ? min(100, (int) round($paid / $tuition * 100)) : 0,
            'overdue' => $overdue,
            'status' => $status,
            'terms' => $terms,
        ];
    }

    /**
     * Students paired with their assessment.
     *
     * @return list<array{student: array<string, mixed>, assessment: array<string, mixed>}>
     */
    public function summaries(): array
    {
        return array_map(
            fn (array $student): array => [
                'student' => $student,
                'assessment' => $this->assess($student),
            ],
            $this->students()
        );
    }

    /**
     * Figures used by the admin dashboard.
     *
     * @return array{
     *   students: int,
     *   assessed: float,
     *   collected: float,
     *   overdue: int,
     *   recent_payments: list<array<string, mixed>>,
     *   highest_balances: list<array<string, mixed>>
     * }
     */
    public function dashboard(): array
    {
        $summaries = $this->summaries();
        $assessed = 0.0;
        $collected = 0.0;
        $overdue = 0;
        $balances = [];

        foreach ($summaries as $summary) {
            $assessment = $summary['assessment'];
            $assessed += (float) $assessment['tuition'];
            $collected += (float) $assessment['paid'];
            $overdue += $assessment['overdue'] ? 1 : 0;

            if ($assessment['remaining'] > 0) {
                $balances[] = [
                    'student' => $summary['student'],
                    'assessment' => $assessment,
                ];
            }
        }

        usort($balances, static fn (array $a, array $b): int => $b['assessment']['remaining'] <=> $a['assessment']['remaining']);

        return [
            'students' => count($summaries),
            'assessed' => round($assessed, 2),
            'collected' => round($collected, 2),
            'overdue' => $overdue,
            'recent_payments' => array_slice($this->payments(), 0, 5),
            'highest_balances' => array_slice($balances, 0, 5),
        ];
    }

    /**
     * Collections grouped by term, for the reports page.
     *
     * @return list<array{term: string, expected: float, collected: float, outstanding: float, rate: int}>
     */
    public function termCollections(): array
    {
        $buckets = [];
        foreach (self::TERMS as $term) {
            $buckets[$term] = ['expected' => 0.0, 'collected' => 0.0];
        }

        foreach ($this->summaries() as $summary) {
            foreach ($summary['assessment']['terms'] as $term) {
                $buckets[$term['name']]['expected'] += (float) $term['amount'];
                $buckets[$term['name']]['collected'] += (float) $term['paid'];
            }
        }

        $rows = [];
        foreach ($buckets as $term => $bucket) {
            $expected = round($bucket['expected'], 2);
            $collected = round($bucket['collected'], 2);
            $rows[] = [
                'term' => $term,
                'expected' => $expected,
                'collected' => $collected,
                'outstanding' => max(0.0, round($expected - $collected, 2)),
                'rate' => $expected > 0 ? (int) round($collected / $expected * 100) : 0,
            ];
        }

        return $rows;
    }

    /**
     * Collections grouped by payment method.
     *
     * @return list<array{method: string, total: float, count: int}>
     */
    public function methodCollections(): array
    {
        $buckets = [];
        foreach ($this->payments() as $payment) {
            $method = $payment['method'] !== '' ? $payment['method'] : 'Other';
            $buckets[$method] ??= ['total' => 0.0, 'count' => 0];
            $buckets[$method]['total'] += $payment['amount'];
            $buckets[$method]['count']++;
        }

        $rows = [];
        foreach ($buckets as $method => $bucket) {
            $rows[] = [
                'method' => $method,
                'total' => round($bucket['total'], 2),
                'count' => $bucket['count'],
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $rows;
    }

    /**
     * Save fee settings. Returns an error message, or null on success.
     */
    public function saveSettings(float $rate, float $enroll): ?string
    {
        if ($rate <= 0) {
            return 'The rate per unit must be greater than zero.';
        }

        if ($enroll < 0) {
            return 'The enrollment fee cannot be negative.';
        }

        if ($enroll >= 12 * $rate) {
            return 'The enrollment fee must be lower than the smallest term assessment.';
        }

        $statement = $this->pdo->prepare(
            "INSERT INTO finance_settings (id, data) VALUES ('default', :data)
             ON DUPLICATE KEY UPDATE data = VALUES(data)"
        );
        $statement->execute([
            ':data' => json_encode([
                'rate' => $rate,
                'enroll' => $enroll,
                'school_year' => self::SCHOOL_YEAR,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return null;
    }

    /**
     * Record a payment against one term. Returns the reference number, or an
     * error message when the student/term/amount is not valid.
     *
     * @return array{ref: string, amount: float, balance: float}|string
     */
    public function recordPayment(string $studentNo, string $term, string $method, float $amount): array|string
    {
        if (!in_array($term, self::TERMS, true)) {
            return 'Choose a valid term.';
        }

        if (!in_array($method, self::METHODS, true)) {
            return 'Choose a valid payment method.';
        }

        if ($amount <= 0) {
            return 'Enter a payment amount greater than zero.';
        }

        $student = null;
        foreach ($this->students() as $candidate) {
            if ($candidate['no'] === $studentNo) {
                $student = $candidate;
                break;
            }
        }

        if ($student === null) {
            return 'That student is not in the finance directory.';
        }

        $assessment = $this->assess($student);
        $selectedTerm = null;
        foreach ($assessment['terms'] as $termRow) {
            if ($termRow['name'] === $term) {
                $selectedTerm = $termRow;
                break;
            }
        }

        if ($selectedTerm === null) {
            return 'That term is not part of the student assessment.';
        }

        if ($selectedTerm['balance'] <= 0) {
            return $term . ' is already fully paid.';
        }

        if ($amount > $selectedTerm['balance'] + 0.009) {
            return 'The amount cannot exceed the ' . $term . ' balance of ' . number_format($selectedTerm['balance'], 2) . '.';
        }

        $date = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
        $reference = 'FBC-' . str_replace('-', '', $date) . '-' . strtoupper(bin2hex(random_bytes(3)));
        $data = [
            'ref' => $reference,
            'no' => $student['no'],
            'term' => $term,
            'method' => $method,
            'amt' => round($amount, 2),
            'date' => $date,
        ];

        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO finance_payments (id, student_no, data) VALUES (:id, :student_no, :data)'
            );
            $statement->execute([
                ':id' => $reference,
                ':student_no' => $student['no'],
                ':data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);

            $this->addNote(
                $student['no'],
                'Payment received',
                'We received PHP ' . number_format($amount, 2) . ' for ' . $term . '. Reference ' . $reference . '.',
                'payment-' . $reference
            );
            $this->addNote(
                'ADMIN',
                'Payment recorded',
                $student['name'] . ' paid PHP ' . number_format($amount, 2) . ' for ' . $term . ' via ' . $method . '. Reference ' . $reference . '.',
                'admin-payment-' . $reference
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            return 'The payment could not be saved. Please try again.';
        }

        return [
            'ref' => $reference,
            'amount' => round($amount, 2),
            'balance' => round($selectedTerm['balance'] - $amount, 2),
        ];
    }

    private function addNote(string $recipient, string $title, string $message, string $key): void
    {
        $id = 'note-' . bin2hex(random_bytes(8));
        $data = [
            'id' => $id,
            'no' => $recipient,
            'title' => $title,
            'msg' => $message,
            'date' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d'),
            'read' => false,
            'key' => $key,
        ];

        $statement = $this->pdo->prepare(
            'INSERT INTO finance_notes (id, student_no, data) VALUES (:id, :student_no, :data)'
        );
        $statement->execute([
            ':id' => $id,
            ':student_no' => $recipient,
            ':data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    private function normalizeYear(mixed $value): int
    {
        if (is_int($value) || is_numeric($value)) {
            return max(1, min(4, (int) $value));
        }

        if (preg_match('/([1-4])/', (string) $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return 1;
    }

    private function normalizeSemester(mixed $value): int
    {
        if (is_int($value) || is_numeric($value)) {
            return max(1, min(2, (int) $value));
        }

        if (preg_match('/([12])/', (string) $value, $matches) === 1) {
            return (int) $matches[1];
        }

        return 1;
    }
}

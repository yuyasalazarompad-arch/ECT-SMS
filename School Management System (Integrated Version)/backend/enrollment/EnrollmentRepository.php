<?php
declare(strict_types=1);

/**
 * Enrollment & Student Portal data layer.
 *
 * The public admissions pages only need program information, application
 * counters, and a safe way to store a new application. Everything lives here
 * so the page files stay presentation-only.
 */
final class EnrollmentRepository
{
    public const DEPARTMENT = 'Engineering & Communication Technology (ECT)';

    /**
     * Programs offered by the department: code => full title.
     *
     * @return array<string, array{title: string, blurb: string, careers: list<string>}>
     */
    public function programs(): array
    {
        return [
            'BSIT' => [
                'title' => 'BS in Information Technology',
                'blurb' => 'Networks, systems administration, and enterprise support.',
                'careers' => ['Network Engineer', 'Systems Analyst', 'IT Support Lead'],
            ],
            'BSCS' => [
                'title' => 'BS in Computer Science',
                'blurb' => 'Software engineering, algorithms, and applied computing.',
                'careers' => ['Software Engineer', 'Data Analyst', 'ML Engineer'],
            ],
            'BSCpE' => [
                'title' => 'BS in Computer Engineering',
                'blurb' => 'Embedded systems, hardware design, and automation.',
                'careers' => ['Embedded Developer', 'Hardware Engineer', 'IoT Specialist'],
            ],
            'BSECE' => [
                'title' => 'BS in Electronics & Communication Engineering',
                'blurb' => 'Telecommunications, electronics, and signal systems.',
                'careers' => ['Telecom Engineer', 'Electronics Designer', 'Broadcast Engineer'],
            ],
        ];
    }

    public function __construct(private PDO $pdo)
    {
    }

    public function applicationCount(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM enrollment_students')->fetchColumn();
    }

    public function verifiedCount(): int
    {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM enrollment_students WHERE email_verified = 1'
        )->fetchColumn();
    }

    /**
     * Applications grouped by program code.
     *
     * @return array<string, int>
     */
    public function programCounts(): array
    {
        $rows = $this->pdo->query(
            'SELECT program, COUNT(*) AS total
               FROM enrollment_students
              GROUP BY program
              ORDER BY total DESC'
        )->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['program']] = (int) $row['total'];
        }

        return $counts;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM enrollment_students WHERE email = :email LIMIT 1'
        );
        $statement->execute([':email' => strtolower(trim($email))]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * Store a new application and return its generated student number.
     *
     * @param array<string, string> $applicant
     */
    public function createApplication(array $applicant): string
    {
        $pdo = $this->pdo;
        $pdo->beginTransaction();

        try {
            $studentId = $this->nextStudentId();

            $statement = $pdo->prepare(
                'INSERT INTO enrollment_students
                    (student_id, first_name, middle_name, last_name, suffix, email,
                     birthday, contact_no, emergency_contact_no, emergency_contact_name,
                     marital_status, permanent_address, current_address, same_address,
                     department, program, profile_photo_opt)
                 VALUES
                    (:student_id, :first_name, :middle_name, :last_name, :suffix, :email,
                     :birthday, :contact_no, :emergency_contact_no, :emergency_contact_name,
                     :marital_status, :permanent_address, :current_address, :same_address,
                     :department, :program, :profile_photo_opt)'
            );

            $statement->execute([
                ':student_id' => $studentId,
                ':first_name' => $applicant['first_name'],
                ':middle_name' => $applicant['middle_name'] !== '' ? $applicant['middle_name'] : null,
                ':last_name' => $applicant['last_name'],
                ':suffix' => $applicant['suffix'] !== '' ? $applicant['suffix'] : null,
                ':email' => strtolower($applicant['email']),
                ':birthday' => $applicant['birthday'],
                ':contact_no' => $applicant['contact_no'],
                ':emergency_contact_no' => $applicant['emergency_contact_no'],
                ':emergency_contact_name' => $applicant['emergency_contact_name'] !== ''
                    ? $applicant['emergency_contact_name']
                    : null,
                ':marital_status' => $applicant['marital_status'] === 'Married' ? 'Married' : 'Single',
                ':permanent_address' => $applicant['permanent_address'] !== ''
                    ? $applicant['permanent_address']
                    : null,
                ':current_address' => $applicant['current_address'] !== ''
                    ? $applicant['current_address']
                    : null,
                ':same_address' => $applicant['same_address'] === '1' ? 1 : 0,
                ':department' => self::DEPARTMENT,
                ':program' => $applicant['program'],
                ':profile_photo_opt' => 'skip',
            ]);

            $pdo->commit();

            return $studentId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * YY + department code + six-digit sequence.
     */
    private function nextStudentId(): string
    {
        $year = date('y');
        $prefix = $year . '01';

        $statement = $this->pdo->prepare(
            'SELECT student_id FROM enrollment_students
              WHERE student_id LIKE :prefix
              ORDER BY student_id DESC
              LIMIT 1'
        );
        $statement->execute([':prefix' => $prefix . '%']);
        $last = (string) ($statement->fetchColumn() ?: '');

        $sequence = $last !== '' ? ((int) substr($last, 4)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}

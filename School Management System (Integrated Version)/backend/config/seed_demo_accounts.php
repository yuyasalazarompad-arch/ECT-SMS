<?php
declare(strict_types=1);

/**
 * Seeds the unified campus directory with one demo account per role.
 *
 * Local XAMPP demo credentials:
 *   fbcadmin        / fbcadmin123    Super Admin
 *   admin           / admin123       Admin
 *   academic.admin  / academic123    Academic Admin
 *   attendance.admin/ attendance123  Attendance Admin
 *   assessment.admin/ assessment123  Assessment Admin
 *   finance.admin   / finance123     Finance Admin
 *   enrollment.admin/ enrollment123  Enrollment Admin
 *   teacher         / teacher123     Teacher
 *   student         / student123     Student
 *
 * Usage:
 *   C:\xampp\php\php.exe backend\config\seed_demo_accounts.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("This seeder can only be run from the command line.\n");
}

require_once __DIR__ . '/database.php';

if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Database connection failed. Start MySQL and try again.\n");
    exit(1);
}

$accounts = [
    ['fbcadmin', 'fbcadmin123', 'Campus Administrator', 'fbcadmin@gmail.com', 'Super Admin'],
    ['admin', 'admin123', 'Operations Administrator', 'admin@fullbright.edu.ph', 'Admin'],
    ['academic.admin', 'academic123', 'Academic Administrator', 'academic.admin@fullbright.edu.ph', 'Academic Admin'],
    ['attendance.admin', 'attendance123', 'Attendance Administrator', 'attendance.admin@fullbright.edu.ph', 'Attendance Admin'],
    ['assessment.admin', 'assessment123', 'Assessment Administrator', 'assessment.admin@fullbright.edu.ph', 'Assessment Admin'],
    ['finance.admin', 'finance123', 'Finance Administrator', 'finance.admin@fullbright.edu.ph', 'Finance Admin'],
    ['enrollment.admin', 'enrollment123', 'Enrollment Administrator', 'enrollment.admin@fullbright.edu.ph', 'Enrollment Admin'],
    ['teacher', 'teacher123', 'Jirah Billones', 'teacher@fullbright.edu.ph', 'Teacher'],
    ['student', 'student123', 'Juan Dela Cruz', 'student@student.fullbright.edu.ph', 'Student'],
];

$statement = $pdo->prepare(
    'INSERT INTO campus_users (username, email, password_hash, full_name, role, status)
     VALUES (:username, :email, :password_hash, :full_name, :role, :status)
     ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        password_hash = VALUES(password_hash),
        full_name = VALUES(full_name),
        role = VALUES(role),
        status = VALUES(status)'
);

foreach ($accounts as [$username, $password, $fullName, $email, $role]) {
    $statement->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':full_name' => $fullName,
        ':role' => $role,
        ':status' => 'Active',
    ]);
}

$rows = $pdo->query(
    "SELECT username, full_name, role, status
       FROM campus_users
      WHERE username IN (
          'fbcadmin', 'admin', 'academic.admin', 'attendance.admin', 'assessment.admin',
          'finance.admin', 'enrollment.admin', 'teacher', 'student'
      )
      ORDER BY FIELD(role, 'Super Admin', 'Admin', 'Academic Admin', 'Attendance Admin',
                     'Assessment Admin', 'Finance Admin', 'Enrollment Admin', 'Teacher', 'Student')"
)->fetchAll();

echo "Campus demo accounts ready in school_management.campus_users\n\n";

foreach ($rows as $row) {
    printf(
        "  %-16s %-26s %-20s %s\n",
        $row['username'],
        $row['full_name'],
        $row['role'],
        $row['status']
    );
}

echo "\nPasswords are listed in the header of this file.\n";

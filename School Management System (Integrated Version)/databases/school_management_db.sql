

CREATE DATABASE IF NOT EXISTS school_management_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_management_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS v_student_balances;
DROP VIEW IF EXISTS v_student_current_enrollment;
DROP VIEW IF EXISTS v_attendance_summary;
DROP VIEW IF EXISTS v_grade_summary;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS report_jobs;
DROP TABLE IF EXISTS financial_transactions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS student_invoices;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS report_cards;
DROP TABLE IF EXISTS final_grades;
DROP TABLE IF EXISTS assessment_scores;
DROP TABLE IF EXISTS assessments;
DROP TABLE IF EXISTS grading_periods;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS class_schedules;
DROP TABLE IF EXISTS teacher_assignments;
DROP TABLE IF EXISTS subject_prerequisites;
DROP TABLE IF EXISTS section_subjects;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS sections;
DROP TABLE IF EXISTS grade_levels;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS school_years;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS student_guardians;
DROP TABLE IF EXISTS guardians;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS school_settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. AUTHENTICATION, ADMINISTRATION, ROLES AND PERMISSIONS
-- ------------------------------------------------------------

CREATE TABLE users (
  user_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80) NULL,
  last_name VARCHAR(80) NOT NULL,
  phone VARCHAR(30) NULL,
  status ENUM('Active','Inactive','Suspended','Pending') NOT NULL DEFAULT 'Pending',
  last_login_at DATETIME NULL,
  password_changed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
  role_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(60) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  permission_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(100) NOT NULL UNIQUE,
  module_name VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id SMALLINT UNSIGNED NOT NULL,
  assigned_by BIGINT UNSIGNED NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_assigner FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id SMALLINT UNSIGNED NOT NULL,
  permission_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE school_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NOT NULL,
  entity_id VARCHAR(100) NULL,
  details JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user_date (user_id, created_at),
  INDEX idx_audit_entity (entity_type, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. STUDENT MANAGEMENT
-- ------------------------------------------------------------

CREATE TABLE students (
  student_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_number VARCHAR(40) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NULL UNIQUE,
  first_name VARCHAR(80) NOT NULL,
  middle_name VARCHAR(80) NULL,
  last_name VARCHAR(80) NOT NULL,
  suffix VARCHAR(20) NULL,
  birth_date DATE NULL,
  sex ENUM('Female','Male','Other','Prefer not to say') NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NULL,
  address_line VARCHAR(255) NULL,
  city VARCHAR(100) NULL,
  province VARCHAR(100) NULL,
  postal_code VARCHAR(20) NULL,
  admission_date DATE NOT NULL,
  status ENUM('Active','Inactive','Graduated','Transferred','Dropped') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_student_name (last_name, first_name),
  INDEX idx_student_status (status),
  CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE guardians (
  guardian_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  relationship_to_student VARCHAR(50) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NOT NULL,
  address_line VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE student_guardians (
  student_id BIGINT UNSIGNED NOT NULL,
  guardian_id BIGINT UNSIGNED NOT NULL,
  is_primary BOOLEAN NOT NULL DEFAULT FALSE,
  receives_notifications BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (student_id, guardian_id),
  CONSTRAINT fk_student_guardians_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
  CONSTRAINT fk_student_guardians_guardian FOREIGN KEY (guardian_id) REFERENCES guardians(guardian_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. ACADEMIC MANAGEMENT
-- ------------------------------------------------------------

CREATE TABLE school_years (
  school_year_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year_name VARCHAR(30) NOT NULL UNIQUE,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('Planned','Active','Completed','Archived') NOT NULL DEFAULT 'Planned',
  CHECK (end_date > start_date),
  INDEX idx_school_year_status (status)
) ENGINE=InnoDB;

CREATE TABLE departments (
  department_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_code VARCHAR(20) NOT NULL UNIQUE,
  department_name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

CREATE TABLE grade_levels (
  grade_level_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level_code VARCHAR(30) NOT NULL UNIQUE,
  level_name VARCHAR(100) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  department_id BIGINT UNSIGNED NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  CONSTRAINT fk_grade_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sections (
  section_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_year_id BIGINT UNSIGNED NOT NULL,
  grade_level_id BIGINT UNSIGNED NOT NULL,
  section_name VARCHAR(80) NOT NULL,
  room_label VARCHAR(50) NULL,
  capacity SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  status ENUM('Active','Inactive','Closed') NOT NULL DEFAULT 'Active',
  UNIQUE KEY uq_section_year_grade_name (school_year_id, grade_level_id, section_name),
  CONSTRAINT fk_section_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
  CONSTRAINT fk_section_grade FOREIGN KEY (grade_level_id) REFERENCES grade_levels(grade_level_id)
) ENGINE=InnoDB;

CREATE TABLE subjects (
  subject_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(30) NOT NULL UNIQUE,
  subject_name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  units DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  department_id BIGINT UNSIGNED NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  CHECK (units >= 0),
  CONSTRAINT fk_subject_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE section_subjects (
  section_subject_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  school_year_id BIGINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_section_subject_year (section_id, subject_id, school_year_id),
  CONSTRAINT fk_section_subject_section FOREIGN KEY (section_id) REFERENCES sections(section_id) ON DELETE CASCADE,
  CONSTRAINT fk_section_subject_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
  CONSTRAINT fk_section_subject_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  teacher_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  employee_number VARCHAR(40) NOT NULL UNIQUE,
  department_id BIGINT UNSIGNED NULL,
  hire_date DATE NULL,
  employment_status ENUM('Active','Inactive','On Leave','Separated') NOT NULL DEFAULT 'Active',
  CONSTRAINT fk_teacher_user FOREIGN KEY (user_id) REFERENCES users(user_id),
  CONSTRAINT fk_teacher_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE teacher_assignments (
  assignment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_subject_id BIGINT UNSIGNED NOT NULL,
  teacher_id BIGINT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_teacher_section_subject (section_subject_id, teacher_id),
  CONSTRAINT fk_assignment_section_subject FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id) ON DELETE CASCADE,
  CONSTRAINT fk_assignment_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
) ENGINE=InnoDB;

CREATE TABLE subject_prerequisites (
  subject_id BIGINT UNSIGNED NOT NULL,
  prerequisite_subject_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (subject_id, prerequisite_subject_id),
  CHECK (subject_id <> prerequisite_subject_id),
  CONSTRAINT fk_prereq_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
  CONSTRAINT fk_prereq_required FOREIGN KEY (prerequisite_subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  enrollment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  school_year_id BIGINT UNSIGNED NOT NULL,
  section_id BIGINT UNSIGNED NOT NULL,
  enrollment_date DATE NOT NULL,
  status ENUM('Pending','Enrolled','Completed','Withdrawn','Transferred') NOT NULL DEFAULT 'Pending',
  remarks VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_school_year (student_id, school_year_id),
  INDEX idx_enrollment_section_status (section_id, status),
  CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_enrollment_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
  CONSTRAINT fk_enrollment_section FOREIGN KEY (section_id) REFERENCES sections(section_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. ATTENDANCE AND SCHEDULING
-- ------------------------------------------------------------

CREATE TABLE class_schedules (
  schedule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_subject_id BIGINT UNSIGNED NOT NULL,
  day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  room_label VARCHAR(50) NULL,
  effective_from DATE NULL,
  effective_to DATE NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  CHECK (end_time > start_time),
  CHECK (effective_to IS NULL OR effective_from IS NULL OR effective_to >= effective_from),
  INDEX idx_schedule_day_time (day_of_week, start_time),
  CONSTRAINT fk_schedule_section_subject FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attendance_sessions (
  attendance_session_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_subject_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NULL,
  session_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  topic VARCHAR(255) NULL,
  opened_by BIGINT UNSIGNED NULL,
  status ENUM('Open','Submitted','Locked','Cancelled') NOT NULL DEFAULT 'Open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance_session (section_subject_id, session_date, start_time),
  INDEX idx_attendance_session_date (session_date, status),
  CONSTRAINT fk_att_session_section_subject FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id),
  CONSTRAINT fk_att_session_schedule FOREIGN KEY (schedule_id) REFERENCES class_schedules(schedule_id) ON DELETE SET NULL,
  CONSTRAINT fk_att_session_user FOREIGN KEY (opened_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attendance_records (
  attendance_record_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  attendance_session_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  attendance_status ENUM('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Present',
  minutes_late SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  remarks VARCHAR(500) NULL,
  recorded_by BIGINT UNSIGNED NULL,
  recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance_student_session (attendance_session_id, student_id),
  INDEX idx_attendance_student_status (student_id, attendance_status),
  CONSTRAINT fk_att_record_session FOREIGN KEY (attendance_session_id) REFERENCES attendance_sessions(attendance_session_id) ON DELETE CASCADE,
  CONSTRAINT fk_att_record_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_att_record_user FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. ASSESSMENT AND GRADING
-- ------------------------------------------------------------

CREATE TABLE grading_periods (
  grading_period_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_year_id BIGINT UNSIGNED NOT NULL,
  period_name VARCHAR(60) NOT NULL,
  sequence_no SMALLINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('Open','Closed','Published') NOT NULL DEFAULT 'Open',
  UNIQUE KEY uq_grading_period_year_seq (school_year_id, sequence_no),
  CHECK (end_date >= start_date),
  CONSTRAINT fk_grading_period_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id)
) ENGINE=InnoDB;

CREATE TABLE assessments (
  assessment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_subject_id BIGINT UNSIGNED NOT NULL,
  grading_period_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  assessment_type ENUM('Quiz','Assignment','Project','Exam','Recitation','Performance Task','Other') NOT NULL DEFAULT 'Other',
  max_score DECIMAL(8,2) NOT NULL,
  weight_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  assessment_date DATE NULL,
  description TEXT NULL,
  status ENUM('Draft','Published','Closed') NOT NULL DEFAULT 'Draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CHECK (max_score > 0),
  CHECK (weight_percent >= 0 AND weight_percent <= 100),
  INDEX idx_assessment_period (grading_period_id, assessment_date),
  CONSTRAINT fk_assessment_section_subject FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id),
  CONSTRAINT fk_assessment_period FOREIGN KEY (grading_period_id) REFERENCES grading_periods(grading_period_id),
  CONSTRAINT fk_assessment_creator FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE assessment_scores (
  assessment_score_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assessment_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(8,2) NULL,
  is_missing BOOLEAN NOT NULL DEFAULT FALSE,
  remarks VARCHAR(500) NULL,
  entered_by BIGINT UNSIGNED NULL,
  entered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_assessment_student (assessment_id, student_id),
  CHECK (score IS NULL OR score >= 0),
  INDEX idx_assessment_score_student (student_id),
  CONSTRAINT fk_score_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE,
  CONSTRAINT fk_score_student FOREIGN KEY (student_id) REFERENCES students(student_id),
  CONSTRAINT fk_score_user FOREIGN KEY (entered_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE final_grades (
  final_grade_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  section_subject_id BIGINT UNSIGNED NOT NULL,
  grading_period_id BIGINT UNSIGNED NOT NULL,
  raw_grade DECIMAL(6,2) NULL,
  transmuted_grade DECIMAL(5,2) NULL,
  remarks ENUM('Passed','Failed','Incomplete','In Progress') NOT NULL DEFAULT 'In Progress',
  calculated_by BIGINT UNSIGNED NULL,
  calculated_at DATETIME NULL,
  published_at DATETIME NULL,
  UNIQUE KEY uq_final_grade (enrollment_id, section_subject_id, grading_period_id),
  CHECK (raw_grade IS NULL OR (raw_grade >= 0 AND raw_grade <= 100)),
  CHECK (transmuted_grade IS NULL OR (transmuted_grade >= 0 AND transmuted_grade <= 100)),
  CONSTRAINT fk_final_grade_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id) ON DELETE CASCADE,
  CONSTRAINT fk_final_grade_section_subject FOREIGN KEY (section_subject_id) REFERENCES section_subjects(section_subject_id),
  CONSTRAINT fk_final_grade_period FOREIGN KEY (grading_period_id) REFERENCES grading_periods(grading_period_id),
  CONSTRAINT fk_final_grade_user FOREIGN KEY (calculated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE report_cards (
  report_card_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  grading_period_id BIGINT UNSIGNED NOT NULL,
  generated_by BIGINT UNSIGNED NULL,
  file_path VARCHAR(500) NULL,
  status ENUM('Draft','Generated','Published','Revoked') NOT NULL DEFAULT 'Draft',
  generated_at DATETIME NULL,
  published_at DATETIME NULL,
  UNIQUE KEY uq_report_card (enrollment_id, grading_period_id),
  CONSTRAINT fk_report_card_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id) ON DELETE CASCADE,
  CONSTRAINT fk_report_card_period FOREIGN KEY (grading_period_id) REFERENCES grading_periods(grading_period_id),
  CONSTRAINT fk_report_card_user FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. FINANCE
-- ------------------------------------------------------------

CREATE TABLE fee_structures (
  fee_structure_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  school_year_id BIGINT UNSIGNED NOT NULL,
  grade_level_id BIGINT UNSIGNED NULL,
  fee_code VARCHAR(40) NOT NULL,
  fee_name VARCHAR(150) NOT NULL,
  description VARCHAR(255) NULL,
  amount DECIMAL(12,2) NOT NULL,
  is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  CHECK (amount >= 0),
  UNIQUE KEY uq_fee_year_code_grade (school_year_id, fee_code, grade_level_id),
  CONSTRAINT fk_fee_year FOREIGN KEY (school_year_id) REFERENCES school_years(school_year_id),
  CONSTRAINT fk_fee_grade FOREIGN KEY (grade_level_id) REFERENCES grade_levels(grade_level_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE student_invoices (
  invoice_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  enrollment_id BIGINT UNSIGNED NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NULL,
  status ENUM('Draft','Issued','Partially Paid','Paid','Overdue','Void') NOT NULL DEFAULT 'Draft',
  notes VARCHAR(500) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_invoice_enrollment_status (enrollment_id, status),
  CONSTRAINT fk_invoice_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id),
  CONSTRAINT fk_invoice_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE invoice_items (
  invoice_item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  fee_structure_id BIGINT UNSIGNED NULL,
  item_description VARCHAR(180) NOT NULL,
  quantity DECIMAL(8,2) NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  CHECK (quantity > 0),
  CHECK (unit_price >= 0),
  CHECK (discount_amount >= 0),
  CONSTRAINT fk_invoice_item_invoice FOREIGN KEY (invoice_id) REFERENCES student_invoices(invoice_id) ON DELETE CASCADE,
  CONSTRAINT fk_invoice_item_fee FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(fee_structure_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payments (
  payment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(50) NOT NULL UNIQUE,
  invoice_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  payment_method ENUM('Cash','Bank Transfer','GCash','Maya','Card','Check','Other') NOT NULL DEFAULT 'Cash',
  reference_number VARCHAR(120) NULL,
  status ENUM('Pending','Completed','Failed','Refunded','Voided') NOT NULL DEFAULT 'Completed',
  received_by BIGINT UNSIGNED NULL,
  notes VARCHAR(500) NULL,
  CHECK (amount > 0),
  INDEX idx_payment_date_status (payment_date, status),
  CONSTRAINT fk_payment_invoice FOREIGN KEY (invoice_id) REFERENCES student_invoices(invoice_id),
  CONSTRAINT fk_payment_user FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE financial_transactions (
  transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_type ENUM('Income','Expense','Refund','Adjustment') NOT NULL,
  transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  amount DECIMAL(12,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  payment_id BIGINT UNSIGNED NULL,
  recorded_by BIGINT UNSIGNED NULL,
  status ENUM('Pending','Posted','Voided') NOT NULL DEFAULT 'Posted',
  CHECK (amount > 0),
  INDEX idx_financial_transaction_date (transaction_date, transaction_type, status),
  CONSTRAINT fk_transaction_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE SET NULL,
  CONSTRAINT fk_transaction_user FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE report_jobs (
  report_job_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requested_by BIGINT UNSIGNED NULL,
  report_type VARCHAR(100) NOT NULL,
  filters JSON NULL,
  output_path VARCHAR(500) NULL,
  status ENUM('Queued','Processing','Completed','Failed') NOT NULL DEFAULT 'Queued',
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  CONSTRAINT fk_report_job_user FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. REPORTING VIEWS
-- ------------------------------------------------------------

CREATE VIEW v_student_current_enrollment AS
SELECT
  e.enrollment_id,
  s.student_id,
  s.student_number,
  CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS student_name,
  sy.year_name,
  gl.level_name,
  sec.section_name,
  e.status AS enrollment_status
FROM enrollments e
JOIN students s ON s.student_id = e.student_id
JOIN school_years sy ON sy.school_year_id = e.school_year_id
JOIN sections sec ON sec.section_id = e.section_id
JOIN grade_levels gl ON gl.grade_level_id = sec.grade_level_id;

CREATE VIEW v_student_balances AS
SELECT
  i.invoice_id,
  i.invoice_number,
  i.enrollment_id,
  s.student_id,
  s.student_number,
  CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS student_name,
  COALESCE(items.invoice_total, 0.00) AS invoice_total,
  COALESCE(paid.amount_paid, 0.00) AS amount_paid,
  GREATEST(COALESCE(items.invoice_total, 0.00) - COALESCE(paid.amount_paid, 0.00), 0.00) AS balance
FROM student_invoices i
JOIN enrollments e ON e.enrollment_id = i.enrollment_id
JOIN students s ON s.student_id = e.student_id
LEFT JOIN (
  SELECT invoice_id,
         SUM(GREATEST(quantity * unit_price - discount_amount, 0)) AS invoice_total
  FROM invoice_items
  GROUP BY invoice_id
) items ON items.invoice_id = i.invoice_id
LEFT JOIN (
  SELECT invoice_id, SUM(amount) AS amount_paid
  FROM payments
  WHERE status = 'Completed'
  GROUP BY invoice_id
) paid ON paid.invoice_id = i.invoice_id
WHERE i.status <> 'Void';

CREATE VIEW v_attendance_summary AS
SELECT
  ar.student_id,
  s.student_number,
  CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS student_name,
  COUNT(*) AS total_records,
  SUM(ar.attendance_status = 'Present') AS present_count,
  SUM(ar.attendance_status = 'Absent') AS absent_count,
  SUM(ar.attendance_status = 'Late') AS late_count,
  SUM(ar.attendance_status = 'Excused') AS excused_count
FROM attendance_records ar
JOIN students s ON s.student_id = ar.student_id
JOIN attendance_sessions ats ON ats.attendance_session_id = ar.attendance_session_id
WHERE ats.status IN ('Submitted','Locked')
GROUP BY ar.student_id, s.student_number, s.first_name, s.middle_name, s.last_name;

CREATE VIEW v_grade_summary AS
SELECT
  fg.final_grade_id,
  e.student_id,
  s.student_number,
  CONCAT_WS(' ', s.first_name, s.middle_name, s.last_name) AS student_name,
  sub.subject_code,
  sub.subject_name,
  gp.period_name,
  fg.raw_grade,
  fg.transmuted_grade,
  fg.remarks
FROM final_grades fg
JOIN enrollments e ON e.enrollment_id = fg.enrollment_id
JOIN students s ON s.student_id = e.student_id
JOIN section_subjects ss ON ss.section_subject_id = fg.section_subject_id
JOIN subjects sub ON sub.subject_id = ss.subject_id
JOIN grading_periods gp ON gp.grading_period_id = fg.grading_period_id;

-- ------------------------------------------------------------
-- 8. BASE ROLES AND PERMISSIONS
-- ------------------------------------------------------------

INSERT INTO roles (role_name, description) VALUES
('Administrator', 'Full system administration'),
('Registrar', 'Student records, enrollment, and academic setup'),
('Teacher', 'Assigned classes, attendance, assessments, and grades'),
('Finance Officer', 'Invoices, payments, and financial reporting'),
('Student', 'Read-only access to own profile, grades, attendance, and balances'),
('Parent/Guardian', 'Read-only access to linked student information');

INSERT INTO permissions (permission_key, module_name, description) VALUES
('dashboard.view','admin','View dashboard'),
('users.view','admin','View user accounts'),
('users.manage','admin','Create and manage user accounts'),
('roles.manage','admin','Manage roles and permissions'),
('settings.manage','admin','Manage school settings'),
('audit.view','admin','View audit logs'),
('students.view','students','View student records'),
('students.create','students','Create student records'),
('students.update','students','Update student records'),
('students.archive','students','Archive student records'),
('enrollments.manage','students','Manage student enrollment'),
('academics.view','academics','View academic structure'),
('academics.manage','academics','Manage academic structure'),
('attendance.view','attendance','View attendance'),
('attendance.record','attendance','Record attendance'),
('attendance.lock','attendance','Submit or lock attendance'),
('grades.view','grading','View grades'),
('grades.manage','grading','Create assessments and enter grades'),
('grades.publish','grading','Publish final grades'),
('finance.view','finance','View financial records'),
('finance.manage','finance','Manage fees and invoices'),
('payments.record','finance','Record payments'),
('reports.view','reports','View authorized reports'),
('reports.export','reports','Export authorized reports');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r CROSS JOIN permissions p
WHERE r.role_name = 'Administrator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r JOIN permissions p
WHERE r.role_name = 'Registrar'
  AND p.permission_key IN (
    'dashboard.view','students.view','students.create','students.update',
    'enrollments.manage','academics.view','academics.manage',
    'attendance.view','reports.view','reports.export'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r JOIN permissions p
WHERE r.role_name = 'Teacher'
  AND p.permission_key IN (
    'dashboard.view','students.view','academics.view',
    'attendance.view','attendance.record','attendance.lock',
    'grades.view','grades.manage','grades.publish','reports.view'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r JOIN permissions p
WHERE r.role_name = 'Finance Officer'
  AND p.permission_key IN (
    'dashboard.view','students.view','finance.view','finance.manage',
    'payments.record','reports.view','reports.export'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM roles r JOIN permissions p
WHERE r.role_name IN ('Student','Parent/Guardian')
  AND p.permission_key IN ('dashboard.view','grades.view','attendance.view','finance.view');

INSERT INTO school_settings (setting_key, setting_value) VALUES
('school_name','Sample School'),
('school_address','Philippines'),
('school_email','admin@example.com'),
('school_phone',''),
('timezone','Asia/Manila'),
('currency','PHP'),
('grading_passing_mark','75'),
('academic_year_label','2026-2027');

-- ------------------------------------------------------------
-- 9. SAMPLE DEVELOPMENT DATA
-- These records are for schema testing. They do not include
-- login-capable users because password hashes must be generated
-- securely by PHP password_hash().
-- ------------------------------------------------------------

INSERT INTO school_years (year_name, start_date, end_date, status) VALUES
('2026-2027','2026-06-01','2027-03-31','Active');

INSERT INTO departments (department_code, department_name, description) VALUES
('JHS','Junior High School','Junior high school department'),
('SHS','Senior High School','Senior high school department');

INSERT INTO grade_levels (level_code, level_name, sort_order, department_id) VALUES
('G7','Grade 7',7,1),
('G8','Grade 8',8,1),
('G11','Grade 11',11,2),
('G12','Grade 12',12,2);

INSERT INTO sections (school_year_id, grade_level_id, section_name, room_label, capacity, status) VALUES
(1,1,'Section A','Room 101',40,'Active'),
(1,3,'ICT A','Computer Lab 1',35,'Active');

INSERT INTO subjects (subject_code, subject_name, description, units, department_id) VALUES
('ENG7','English','English language and communication',1.0,1),
('MATH7','Mathematics','Mathematics',1.0,1),
('ICT11','Introduction to Computing','Computing fundamentals',1.0,2);

INSERT INTO section_subjects (section_id, subject_id, school_year_id) VALUES
(1,1,1),
(1,2,1),
(2,3,1);

INSERT INTO grading_periods (school_year_id, period_name, sequence_no, start_date, end_date, status) VALUES
(1,'First Quarter',1,'2026-06-01','2026-08-31','Open'),
(1,'Second Quarter',2,'2026-09-01','2026-11-30','Open'),
(1,'Third Quarter',3,'2026-12-01','2027-01-31','Open'),
(1,'Fourth Quarter',4,'2027-02-01','2027-03-31','Open');

INSERT INTO fee_structures (school_year_id, grade_level_id, fee_code, fee_name, description, amount, is_mandatory, status) VALUES
(1,NULL,'REG','Registration Fee','Annual registration fee',500.00,TRUE,'Active'),
(1,1,'TUITION-G7','Grade 7 Tuition','Tuition fee for Grade 7',12000.00,TRUE,'Active'),
(1,3,'TUITION-G11','Grade 11 Tuition','Tuition fee for Grade 11',16000.00,TRUE,'Active');


-- End of schema.

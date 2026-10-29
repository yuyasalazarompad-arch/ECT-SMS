-- ---------------------------------------------------------------------------
-- Role-based campus access
--
-- Extends the unified campus directory so a single login can carry the
-- Super Admin, per-system administrator, Teacher, and Student roles.
--
-- Run with:
--   C:\xampp\mysql\bin\mysql.exe -u root school_management < databases\20261030_role_based_access.sql
-- ---------------------------------------------------------------------------

USE school_management;

ALTER TABLE campus_users
  MODIFY COLUMN role ENUM(
    'Super Admin',
    'Admin',
    'Academic Admin',
    'Attendance Admin',
    'Assessment Admin',
    'Finance Admin',
    'Enrollment Admin',
    'Teacher',
    'Student'
  ) NOT NULL DEFAULT 'Student';

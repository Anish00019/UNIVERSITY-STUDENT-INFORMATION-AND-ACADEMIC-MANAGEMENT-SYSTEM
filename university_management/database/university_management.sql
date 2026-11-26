-- =====================================================================
--  University Student Information and Academic Management System
--  Database : university_management
--  Usage    : Import this file in phpMyAdmin (Import tab) or run it in
--             the MySQL console:  SOURCE path/to/university_management.sql;
-- =====================================================================

-- 1. Create the database (drops the old copy so the import is repeatable)
DROP DATABASE IF EXISTS university_management;
CREATE DATABASE university_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE university_management;

-- ---------------------------------------------------------------------
-- 2. Table: users  (login & registration details)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100)  NOT NULL,
    username    VARCHAR(30)   NOT NULL,
    email       VARCHAR(100)  NOT NULL,
    password    VARCHAR(255)  NOT NULL COMMENT 'bcrypt hash created by password_hash()',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Table: students  (student academic information)
-- ---------------------------------------------------------------------
CREATE TABLE students (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    register_number VARCHAR(20)      NOT NULL,
    student_name    VARCHAR(100)     NOT NULL,
    course          VARCHAR(50)      NOT NULL,
    semester        TINYINT UNSIGNED NOT NULL,
    email           VARCHAR(100)     NOT NULL,
    contact_number  VARCHAR(10)      NOT NULL,
    created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_register_number (register_number),
    KEY idx_students_name (student_name),
    KEY idx_students_course (course)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Sample data
-- ---------------------------------------------------------------------

-- Default administrator account
--   Username : admin
--   Password : Admin@123
INSERT INTO users (full_name, username, email, password) VALUES
('System Administrator', 'admin', 'admin@university.edu',
 '$2y$12$.1t5exHUVwLdb6pCGnIs7eyeMyBOjGKyLCKyTr9ZolGAINCHjaEBm');

-- Sample students (these match the records in students.xml)
INSERT INTO students (register_number, student_name, course, semester, email, contact_number) VALUES
('BCA23001', 'Aarav Sharma',    'BCA',                    5, 'aarav.sharma@student.edu',    '9876543210'),
('BCA23002', 'Diya Nair',       'BCA',                    5, 'diya.nair@student.edu',       '9845012345'),
('BCA24015', 'Rohan Gowda',     'BCA',                    3, 'rohan.gowda@student.edu',     '9900112233'),
('BBA24008', 'Sneha Reddy',     'BBA',                    3, 'sneha.reddy@student.edu',     '9731234567'),
('BCO25011', 'Kiran Kumar',     'B.Com',                  1, 'kiran.kumar@student.edu',     '9886655443'),
('BSC24021', 'Ananya Iyer',     'B.Sc Computer Science',  3, 'ananya.iyer@student.edu',     '8105566778'),
('MCA25004', 'Mohammed Faizan', 'MCA',                    1, 'mohammed.faizan@student.edu', '7019988776'),
('BCA25030', 'Priya Hegde',     'BCA',                    1, 'priya.hegde@student.edu',     '6361122334');

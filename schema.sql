-- Western Pacific University Student Management System
-- Database Schema

CREATE DATABASE IF NOT EXISTS WPU CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE WPU;

-- Staff table
CREATE TABLE staff (
    staff_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('registrar', 'student_services') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student table
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT,
    contact_number VARCHAR(20),
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Units table
CREATE TABLE units (
    unit_code VARCHAR(20) PRIMARY KEY,
    unit_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Unit offerings table
CREATE TABLE unit_offerings (
    offering_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    FOREIGN KEY (unit_code) REFERENCES units(unit_code) ON DELETE CASCADE,
    UNIQUE KEY unique_offering (unit_code, semester, year)
);

-- Enrollments table
CREATE TABLE enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    offering_id INT NOT NULL,
    mark DECIMAL(5,2),
    grade VARCHAR(2),
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (offering_id) REFERENCES unit_offerings(offering_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, offering_id)
);

-- Dormitories table
CREATE TABLE dormitories (
    dormitory_id INT AUTO_INCREMENT PRIMARY KEY,
    dormitory_name VARCHAR(100) NOT NULL UNIQUE
);

-- Floors table
CREATE TABLE floors (
    floor_id INT AUTO_INCREMENT PRIMARY KEY,
    dormitory_id INT NOT NULL,
    floor_number INT NOT NULL,
    FOREIGN KEY (dormitory_id) REFERENCES dormitories(dormitory_id) ON DELETE CASCADE,
    UNIQUE KEY unique_floor (dormitory_id, floor_number)
);

-- Rooms table
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    floor_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    FOREIGN KEY (floor_id) REFERENCES floors(floor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_room (floor_id, room_number)
);

-- Student dormitory allocations table
CREATE TABLE dormitory_allocations (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    room_id INT NOT NULL,
    allocation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE,
    UNIQUE KEY unique_allocation (student_id)
);

-- Insert sample data
INSERT INTO staff (staff_id, name, gender, address, contact_number, email, password, role) VALUES
('REG001', 'John Registrar', 'Male', '123 Admin St', '555-0101', 'registrar@wpu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'registrar'),
('SS001', 'Jane Services', 'Female', '456 Services Ave', '555-0102', 'services@wpu.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student_services');

-- Default password for staff: password

INSERT INTO units (unit_code, unit_name, description) VALUES
('IS303', 'Information Systems', 'Introduction to information systems and their role in organizations'),
('CS101', 'Introduction to Computer Science', 'Fundamentals of computer science and programming'),
('MATH201', 'Calculus I', 'Differential and integral calculus'),
('ENG101', 'English Composition', 'Basic writing and communication skills'),
('PHYS101', 'Physics I', 'Mechanics and thermodynamics'),
('CHEM101', 'Chemistry I', 'General chemistry principles'),
('BIO101', 'Biology I', 'Cell biology and genetics'),
('ECON101', 'Microeconomics', 'Introduction to microeconomic theory');

INSERT INTO unit_offerings (unit_code, semester, year) VALUES
('IS303', 'Semester 1', 2024),
('IS303', 'Semester 2', 2024),
('CS101', 'Semester 1', 2024),
('CS101', 'Semester 2', 2024),
('MATH201', 'Semester 1', 2024),
('MATH201', 'Semester 2', 2024),
('ENG101', 'Semester 1', 2024),
('ENG101', 'Semester 2', 2024),
('PHYS101', 'Semester 1', 2024),
('CHEM101', 'Semester 1', 2024),
('BIO101', 'Semester 2', 2024),
('ECON101', 'Semester 2', 2024);

INSERT INTO dormitories (dormitory_name) VALUES
('North Hall'),
('South Hall'),
('East Hall'),
('West Hall');

-- Insert floors and rooms for North Hall
INSERT INTO floors (dormitory_id, floor_number) VALUES (1, 1), (1, 2), (1, 3);
INSERT INTO rooms (floor_id, room_number) VALUES
(1, '101'), (1, '102'), (1, '103'), (1, '104'),
(2, '201'), (2, '202'), (2, '203'), (2, '204'),
(3, '301'), (3, '302'), (3, '303'), (3, '304');

-- Insert floors and rooms for South Hall
INSERT INTO floors (dormitory_id, floor_number) VALUES (2, 1), (2, 2);
INSERT INTO rooms (floor_id, room_number) VALUES
(4, '101'), (4, '102'), (4, '103'),
(5, '201'), (5, '202'), (5, '203');

-- Insert floors and rooms for East Hall
INSERT INTO floors (dormitory_id, floor_number) VALUES (3, 1), (3, 2), (3, 3), (3, 4);
INSERT INTO rooms (floor_id, room_number) VALUES
(6, '101'), (6, '102'),
(7, '201'), (7, '202'),
(8, '301'), (8, '302'),
(9, '401'), (9, '402');

-- Insert floors and rooms for West Hall
INSERT INTO floors (dormitory_id, floor_number) VALUES (4, 1), (4, 2);
INSERT INTO rooms (floor_id, room_number) VALUES
(10, '101'), (10, '102'), (10, '103'), (10, '104'), (10, '105'),
(11, '201'), (11, '202'), (11, '203'), (11, '204'), (11, '205');


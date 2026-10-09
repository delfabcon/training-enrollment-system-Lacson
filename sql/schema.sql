-- Database Schema for Training Enrollment System
CREATE DATABASE IF NOT EXISTS training_db;
USE training_db;

-- Table: students
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: courses
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: classes
CREATE TABLE IF NOT EXISTS classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    schedule VARCHAR(100),
    instructor VARCHAR(100),
    slots INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: enrollments
CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Sample Data
INSERT INTO courses (course_code, course_name, description) VALUES
('WD101', 'Web Development Fundamentals', 'Introduction to HTML5, CSS3, JavaScript, PHP, and relational databases.'),
('DB201', 'Relational Database Design & SQL', 'Advanced schema modeling, normalization, indexing, and complex queries.'),
('PY102', 'Python Programming Essentials', 'Fundamentals of Python, data structures, algorithms, and modular design.'),
('CY301', 'Cybersecurity & Secure Coding', 'Practical web application security, SQL injection defense, and authentication.');

INSERT INTO classes (course_id, class_code, schedule, instructor, slots) VALUES
(1, 'WD101-SEC1', 'Mon/Wed 09:00 AM - 11:00 AM', 'Engr. Sarah Jenkins', 14),
(1, 'WD101-SEC2', 'Tue/Thu 01:00 PM - 03:00 PM', 'Prof. Marcus Vance', 0),
(2, 'DB201-SEC1', 'Tue/Thu 10:00 AM - 12:00 PM', 'Dr. Aris Thorne', 8),
(3, 'PY102-SEC1', 'Friday 01:00 PM - 05:00 PM', 'Prof. Elena Rostova', 12);

INSERT INTO students (full_name, email, phone) VALUES
('John Doe', 'john.doe@example.com', '09171234567'),
('Jane Smith', 'jane.smith@example.com', '09187654321'),
('Michael Brown', 'michael.b@example.com', '09223334444');

INSERT INTO enrollments (student_id, class_id, enrollment_date, status) VALUES
(1, 1, NOW(), 'active'),
(2, 3, NOW(), 'active'),
(3, 1, NOW(), 'active');

-- Create database
CREATE DATABASE IF NOT EXISTS extra_class_system;
USE extra_class_system;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'teacher', 'student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modules table
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    year_level VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Student enrollments
CREATE TABLE student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    module_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

-- Insert default courses and modules
INSERT INTO courses (name, description) VALUES 
('Engineering', 'Engineering courses focusing on computing and programming'),
('Operational Management', 'Management courses with programming components'),
('IT', 'Information Technology comprehensive program');

INSERT INTO modules (course_id, name, description, year_level) VALUES 
(1, 'Computing 2', 'Advanced computing concepts and applications', 'General'),
(1, 'Programming', 'Software development principles and practices', 'General'),
(2, 'Programming', 'Technical implementation of management systems', 'General'),
(3, 'Programming logic', 'Fundamental programming concepts', 'First Year'),
(3, 'System software', 'Operating systems and utilities', 'First Year'),
(3, 'Development software', 'Tools for software development', 'First Year'),
(3, 'Accounting for IT', 'Financial principles for IT professionals', 'First Year'),
(3, 'Information system', 'Data management and system design', 'First Year'),
(3, 'Business analysis practical', 'Real-world business analysis techniques', 'Second Year'),
(3, 'Development', 'Intermediate software development', 'Second Year'),
(3, 'Web development', 'Web technologies and frameworks', 'Third Year'),
(3, 'Development 3.1', 'Advanced development concepts', 'Third Year'),
(3, 'Development 3.2', 'Specialized development techniques', 'Third Year'),
(3, 'Business analysis 3.1', 'Strategic business analysis', 'Third Year'),
(3, 'Web development 3.2', 'Advanced web applications', 'Third Year');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@shivambu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample teacher
INSERT INTO users (username, email, password, full_name, role) VALUES 
('teacher1', 'teacher@shivambu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Teacher', 'teacher');

-- Insert sample student
INSERT INTO users (username, email, password, full_name, role) VALUES 
('student1', 'student@shivambu.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Student', 'student');
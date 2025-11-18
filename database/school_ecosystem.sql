 -- School Management Ecosystem Database Schema
-- Database: school_ecosystem
-- Created for PHP PDO integration

CREATE DATABASE IF NOT EXISTS school_ecosystem;
USE school_ecosystem;

-- =============================================
-- CORE USER MANAGEMENT
-- =============================================

-- Centralized users table for all roles
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'parent', 'accountant', 'librarian') NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email)
);

-- =============================================
-- ACADEMIC MANAGEMENT
-- =============================================

-- Classes and sections
CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10) NOT NULL,
    class_teacher_id INT,
    capacity INT DEFAULT 40,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_class_section (class_name, section),
    FOREIGN KEY (class_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_class_name (class_name),
    INDEX idx_status (status)
);

-- Subjects
CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) UNIQUE,
    class_id INT NOT NULL,
    teacher_id INT,
    credits INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_class_id (class_id),
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_subject_code (subject_code)
);

-- Parents information
CREATE TABLE parents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    occupation VARCHAR(100),
    relationship ENUM('father', 'mother', 'guardian', 'other') NOT NULL,
    emergency_contact VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Students information
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    class_id INT NOT NULL,
    parent_id INT,
    admission_date DATE NOT NULL,
    date_of_birth DATE,
    blood_group VARCHAR(5),
    medical_conditions TEXT,
    status ENUM('active', 'graduated', 'transferred', 'dropped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_class_id (class_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Exams
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    exam_type ENUM('midterm', 'final', 'quiz', 'assignment', 'practical') NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_date DATE NOT NULL,
    total_marks INT NOT NULL DEFAULT 100,
    duration_minutes INT DEFAULT 180,
    term ENUM('1st', '2nd', '3rd', 'annual') NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    INDEX idx_class_id (class_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_exam_date (exam_date),
    INDEX idx_academic_year (academic_year)
);

-- Grades and results (per subject with CAT1, CAT2, and Exam)
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    cat1_marks DECIMAL(5,2) DEFAULT 0,
    cat2_marks DECIMAL(5,2) DEFAULT 0,
    exam_marks DECIMAL(5,2) DEFAULT 0,
    total_marks DECIMAL(5,2) GENERATED ALWAYS AS (cat1_marks + cat2_marks + exam_marks) STORED,
    grade VARCHAR(5),
    remarks TEXT,
    term ENUM('1st', '2nd', '3rd', 'annual') NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_subject_term (student_id, subject_id, term, academic_year),
    INDEX idx_student_id (student_id),
    INDEX idx_subject_id (subject_id),
    INDEX idx_grade (grade),
    INDEX idx_term (term),
    INDEX idx_academic_year (academic_year)
);

-- =============================================
-- ATTENDANCE SYSTEM
-- =============================================

-- Attendance for both students and staff
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'half_day', 'excused') NOT NULL,
    role ENUM('student', 'teacher', 'staff') NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_user_id (user_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- =============================================
-- FINANCE & PAYMENT SYSTEM
-- =============================================

-- Student fee balances (what parents need to pay)
CREATE TABLE fee_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type ENUM('tuition', 'transport', 'library', 'exam', 'activity', 'other') NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    term ENUM('1st', '2nd', '3rd', 'annual') NOT NULL,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    INDEX idx_fee_type (fee_type)
);

-- Student fee payments (receipt-based system)
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type ENUM('tuition', 'transport', 'library', 'exam', 'activity', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    due_date DATE,
    receipt_screenshot VARCHAR(255) NOT NULL, -- Required receipt/bank slip image
    bank_reference VARCHAR(100), -- Bank transaction reference
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    remarks TEXT,
    verified_by INT, -- Admin/Accountant who verified the receipt
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status),
    INDEX idx_fee_type (fee_type)
);

-- Staff salaries
CREATE TABLE salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    salary_month VARCHAR(7) NOT NULL, -- YYYY-MM format
    payment_date DATE,
    payment_method ENUM('cash', 'bank_transfer', 'cheque') DEFAULT 'bank_transfer',
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    remarks TEXT,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_month (user_id, salary_month),
    INDEX idx_user_id (user_id),
    INDEX idx_salary_month (salary_month),
    INDEX idx_status (status)
);

-- =============================================
-- LIBRARY MANAGEMENT
-- =============================================

-- Books catalog (for reference only)
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    category VARCHAR(50) NOT NULL,
    publisher VARCHAR(100),
    publication_year YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_isbn (isbn),
    INDEX idx_category (category)
);

-- Student library records (individual tracking)
CREATE TABLE student_library_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_title VARCHAR(200) NOT NULL,
    author VARCHAR(100),
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    fine_amount DECIMAL(6,2) DEFAULT 0,
    status ENUM('issued', 'returned', 'overdue', 'lost') DEFAULT 'issued',
    remarks TEXT,
    recorded_by INT, -- Librarian who recorded this
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_issue_date (issue_date),
    INDEX idx_status (status),
    INDEX idx_book_title (book_title)
);

-- =============================================
-- TRANSPORT MANAGEMENT
-- =============================================

-- Bus fleet
CREATE TABLE buses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bus_number VARCHAR(20) UNIQUE NOT NULL,
    route_name VARCHAR(100) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    driver_contact VARCHAR(20) NOT NULL,
    conductor_name VARCHAR(100),
    conductor_contact VARCHAR(20),
    capacity INT NOT NULL DEFAULT 40,
    monthly_fee DECIMAL(8,2) NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bus_number (bus_number),
    INDEX idx_route_name (route_name),
    INDEX idx_status (status)
);

-- Student transport subscriptions
CREATE TABLE transport_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    bus_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    pickup_point VARCHAR(200) NOT NULL,
    monthly_fee DECIMAL(8,2) NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_bus_id (bus_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- =============================================
-- ANNOUNCEMENTS & NEWS SYSTEM
-- =============================================

-- Announcements
CREATE TABLE announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    audience ENUM('all', 'students', 'teachers', 'parents', 'staff') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    attachment VARCHAR(255),
    published_by INT NOT NULL,
    publish_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('draft', 'published', 'expired', 'archived') DEFAULT 'draft',
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audience (audience),
    INDEX idx_status (status),
    INDEX idx_publish_date (publish_date),
    INDEX idx_priority (priority)
);

-- =============================================
-- MESSAGING & COMMUNICATION
-- =============================================

-- Direct messages between users
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    attachment VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    status ENUM('sent', 'delivered', 'read', 'deleted') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- ACTIVITY LOGGING
-- =============================================

-- System activity logs
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- ADDITIONAL UTILITY TABLES
-- =============================================

-- System settings
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
);

-- Academic years
CREATE TABLE academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year_name VARCHAR(20) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'completed', 'upcoming') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_year_name (year_name),
    INDEX idx_is_current (is_current)
);

-- =============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =============================================

-- No triggers needed for student library records

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Insert default admin user
INSERT INTO users (name, email, password, role, gender, phone, address, status) VALUES
('System Administrator', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'male', '1234567890', 'School Address', 'active');

-- Insert default academic year
INSERT INTO academic_years (year_name, start_date, end_date, is_current, status) VALUES
('2024-2025', '2024-04-01', '2025-03-31', TRUE, 'active');

-- Insert default system settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('school_name', 'ABC School', 'Name of the school'),
('school_address', 'School Address Here', 'School physical address'),
('school_phone', '1234567890', 'School contact number'),
('school_email', 'info@school.com', 'School email address'),
('academic_year', '2024-2025', 'Current academic year'),
('late_fee_per_day', '5.00', 'Late fee charged per day for overdue library books'),
('payment_verification_required', 'true', 'All payments require receipt verification');

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- Student details with class and parent info
CREATE VIEW student_details AS
SELECT 
    s.id as student_id,
    s.student_id as roll_number,
    u.name as student_name,
    u.email,
    u.phone,
    u.gender,
    c.class_name,
    c.section,
    p.user_id as parent_user_id,
    pu.name as parent_name,
    pu.phone as parent_phone,
    pr.relationship,
    s.status
FROM students s
JOIN users u ON s.user_id = u.id
JOIN classes c ON s.class_id = c.id
LEFT JOIN parents pr ON s.parent_id = pr.id
LEFT JOIN users pu ON pr.user_id = pu.id;

-- Teacher subjects view
CREATE VIEW teacher_subjects AS
SELECT 
    u.id as teacher_id,
    u.name as teacher_name,
    s.id as subject_id,
    s.subject_name,
    s.subject_code,
    c.class_name,
    c.section
FROM users u
JOIN subjects s ON u.id = s.teacher_id
JOIN classes c ON s.class_id = c.id
WHERE u.role = 'teacher' AND u.status = 'active';

-- Monthly attendance summary
CREATE VIEW monthly_attendance_summary AS
SELECT 
    user_id,
    YEAR(date) as year,
    MONTH(date) as month,
    role,
    COUNT(*) as total_days,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
    ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
FROM attendance
GROUP BY user_id, YEAR(date), MONTH(date), role;

-- Parent balance summary
CREATE VIEW parent_balance_summary AS
SELECT 
    p.user_id as parent_user_id,
    pu.name as parent_name,
    s.id as student_id,
    u.name as student_name,
    SUM(CASE WHEN fb.status = 'pending' THEN fb.amount_due ELSE 0 END) as total_pending,
    SUM(CASE WHEN fb.status = 'overdue' THEN fb.amount_due ELSE 0 END) as total_overdue,
    COUNT(CASE WHEN fb.status = 'pending' THEN 1 END) as pending_items,
    COUNT(CASE WHEN fb.status = 'overdue' THEN 1 END) as overdue_items
FROM parents p
JOIN users pu ON p.user_id = pu.id
JOIN students s ON p.id = s.parent_id
JOIN users u ON s.user_id = u.id
LEFT JOIN fee_balances fb ON s.id = fb.student_id
GROUP BY p.user_id, s.id;

-- =============================================
-- END OF SCHEMA
-- =============================================
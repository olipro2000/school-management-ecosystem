-- Class promotion tracking table
CREATE TABLE IF NOT EXISTS student_promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    from_class_id INT NOT NULL,
    to_class_id INT,
    academic_year VARCHAR(10) NOT NULL,
    status ENUM('promoted', 'repeated') NOT NULL,
    average_marks DECIMAL(5,2),
    remarks TEXT,
    promoted_by INT,
    promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (from_class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (to_class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (promoted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status)
);

<?php
require_once 'config/db.php';

try {
    $db = getDB();
    
    // Drop old grades table
    $db->exec("DROP TABLE IF EXISTS grades");
    
    // Create new grades table
    $db->exec("
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
        )
    ");
    
    echo "Migration completed successfully! Grades table updated.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

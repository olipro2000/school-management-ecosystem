CREATE TABLE IF NOT EXISTS fee_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category ENUM('nursery', 'primary', 'secondary', 'cambridge', 'special_needs') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_category_year (category, academic_year)
);

ALTER TABLE classes ADD COLUMN category ENUM('nursery', 'primary', 'secondary', 'cambridge', 'special_needs') DEFAULT 'primary' AFTER section;

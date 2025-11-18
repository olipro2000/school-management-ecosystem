<?php
require_once 'config/db.php';
$db = getDB();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS fee_structure (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category ENUM('nursery', 'primary', 'secondary', 'cambridge', 'special_needs') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        academic_year VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_category_year (category, academic_year)
    )");
    
    $db->exec("ALTER TABLE classes ADD COLUMN category ENUM('nursery', 'primary', 'secondary', 'cambridge', 'special_needs') DEFAULT 'primary' AFTER section");
    
    echo "Success! Fee structure tables created.<br>";
    echo "<a href='views/fee_settings.php'>Go to Fee Settings</a>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Tables already exist. <a href='views/fee_settings.php'>Go to Fee Settings</a>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>

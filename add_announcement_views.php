<?php
require_once 'config/db.php';
$db = getDB();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS announcement_views (
        id INT PRIMARY KEY AUTO_INCREMENT,
        announcement_id INT NOT NULL,
        user_id INT NOT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_view (announcement_id, user_id)
    )");
    echo "Success! announcement_views table created.<br>";
    echo "<a href='views/announcements.php'>Go to Announcements</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

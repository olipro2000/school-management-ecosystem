<?php
require_once 'config/db.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE announcements ADD COLUMN class_id INT NULL");
    echo "Success! class_id column added to announcements table.<br>";
    echo "<a href='views/announcements.php'>Go to Announcements</a>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists. <a href='views/announcements.php'>Go to Announcements</a>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>

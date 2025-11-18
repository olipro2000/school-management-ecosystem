<?php
require_once 'config/db.php';
$db = getDB();

echo "<h3>Fee Structure Table:</h3><pre>";
$stmt = $db->query("SELECT * FROM fee_structure");
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h3>Classes with Category:</h3><pre>";
$stmt = $db->query("SELECT id, class_name, section, category FROM classes LIMIT 10");
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h3>Payments:</h3><pre>";
$stmt = $db->query("SELECT id, student_id, amount, status, payment_date FROM payments LIMIT 10");
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h3>Students with Class:</h3><pre>";
$stmt = $db->query("SELECT s.id, s.student_id, u.name, c.class_name, c.category FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id LIMIT 10");
print_r($stmt->fetchAll());
echo "</pre>";
?>

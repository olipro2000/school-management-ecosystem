<?php
require_once 'config/db.php';
$db = getDB();

// Find actual students
echo "<h3>All Students:</h3>";
$stmt = $db->query("SELECT s.id, s.student_id, u.name, c.class_name, c.category FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id LIMIT 10");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

// Get first student
$stmt = $db->query("SELECT id FROM students LIMIT 1");
$first = $stmt->fetch();
$student_id = $first['id'] ?? 0;

if (!$student_id) {
    die("<p style='color:red;'>No students found in database!</p>");
}

echo "<hr><h3>Testing Balance Calculation for Student ID: $student_id</h3>";

// Get student info
$stmt = $db->prepare("SELECT s.*, c.category, u.name FROM students s JOIN classes c ON s.class_id = c.id JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
echo "<p>Student: " . ($student['name'] ?? 'NOT FOUND') . "</p>";
echo "<p>Category: " . ($student['category'] ?? 'NULL') . "</p>";

// Get total fees
$stmt = $db->prepare("SELECT amount FROM fee_structure WHERE category = ? AND academic_year = '2024-2025'");
$stmt->execute([$student['category'] ?? '']);
$fees = $stmt->fetch();
echo "<p>Total Fees: RWF " . ($fees['amount'] ?? 0) . "</p>";

// Get total paid
$stmt = $db->prepare("SELECT SUM(amount) as total FROM payments WHERE student_id = ? AND status = 'verified'");
$stmt->execute([$student_id]);
$paid = $stmt->fetch();
echo "<p>Total Paid: RWF " . ($paid['total'] ?? 0) . "</p>";

// Calculate balance
$balance = ($fees['amount'] ?? 0) - ($paid['total'] ?? 0);
echo "<p><strong>Balance: RWF " . number_format($balance, 0) . "</strong></p>";

// Show all payments
echo "<h4>All Payments:</h4>";
$stmt = $db->prepare("SELECT * FROM payments WHERE student_id = ?");
$stmt->execute([$student_id]);
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>

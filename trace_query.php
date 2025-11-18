<?php
require_once 'config/db.php';
$db = getDB();

echo "<h2>Exact Query from School Fees Page</h2>";

$query = "SELECT 
    s.id as student_id, 
    s.student_id as roll_number, 
    u.name as student_name, 
    c.id as class_id, 
    c.class_name, 
    c.section, 
    COALESCE(c.category, 'primary') as category,
    COALESCE((SELECT SUM(amount) FROM payments WHERE student_id = s.id AND status = 'verified'), 0) as total_paid,
    COALESCE((SELECT amount FROM fee_structure WHERE category = COALESCE(c.category, 'primary') AND academic_year = '2024-2025' LIMIT 1), 0) as total_fees
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'active'";

echo "<pre>$query</pre>";

echo "<h3>Results:</h3>";
$students = $db->query($query)->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Student ID</th><th>Name</th><th>Category</th><th>Total Fees</th><th>Total Paid</th><th>Balance</th></tr>";
foreach ($students as $student) {
    $balance = $student['total_fees'] - $student['total_paid'];
    echo "<tr>";
    echo "<td>{$student['student_id']}</td>";
    echo "<td>{$student['student_name']}</td>";
    echo "<td>{$student['category']}</td>";
    echo "<td>RWF " . number_format($student['total_fees'], 0) . "</td>";
    echo "<td>RWF " . number_format($student['total_paid'], 0) . "</td>";
    echo "<td>RWF " . number_format($balance, 0) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h3>Debug: Check Payment Subquery for Student ID 2</h3>";
$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE student_id = 2 AND status = 'verified'");
$result = $stmt->fetch();
echo "<p>Direct query result: RWF " . number_format($result['total'] ?? 0, 0) . "</p>";

echo "<h3>All Payments for Student ID 2:</h3>";
$stmt = $db->query("SELECT * FROM payments WHERE student_id = 2");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
?>

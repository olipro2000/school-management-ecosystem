<?php
require_once 'config/db.php';
$db = getDB();

echo "<h2>Step 1: Check Fee Structure</h2>";
$fees = $db->query("SELECT * FROM fee_structure")->fetchAll();
if (empty($fees)) {
    echo "<p style='color:red;'>Fee structure is EMPTY! Inserting default fees...</p>";
    $db->exec("
        INSERT INTO fee_structure (category, amount, academic_year) VALUES
        ('nursery', 150000, '2024-2025'),
        ('primary', 200000, '2024-2025'),
        ('secondary', 300000, '2024-2025'),
        ('cambridge', 500000, '2024-2025'),
        ('special_needs', 250000, '2024-2025')
    ");
    echo "<p style='color:green;'>Fees inserted!</p>";
} else {
    echo "<pre>"; print_r($fees); echo "</pre>";
}

echo "<h2>Step 2: Check Classes Have Category</h2>";
$classes = $db->query("SELECT id, class_name, category FROM classes")->fetchAll();
$needsUpdate = false;
foreach ($classes as $class) {
    if (empty($class['category'])) {
        $needsUpdate = true;
        break;
    }
}
if ($needsUpdate) {
    echo "<p style='color:red;'>Some classes missing category! Updating...</p>";
    $db->exec("UPDATE classes SET category = 'primary' WHERE category IS NULL OR category = ''");
    echo "<p style='color:green;'>Categories updated!</p>";
}
echo "<pre>"; print_r($db->query("SELECT id, class_name, category FROM classes")->fetchAll()); echo "</pre>";

echo "<h2>Step 3: Check Payments</h2>";
$payments = $db->query("SELECT id, student_id, amount, status FROM payments")->fetchAll();
echo "<pre>"; print_r($payments); echo "</pre>";

echo "<h2>Step 4: Test Balance for First Student</h2>";
$student = $db->query("SELECT s.id, s.student_id, u.name, c.category FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id LIMIT 1")->fetch();
if ($student) {
    echo "<p>Student: {$student['name']} (ID: {$student['id']})</p>";
    echo "<p>Category: {$student['category']}</p>";
    
    $feeStmt = $db->prepare("SELECT amount FROM fee_structure WHERE category = ? AND academic_year = '2024-2025'");
    $feeStmt->execute([$student['category']]);
    $totalFees = $feeStmt->fetch()['amount'] ?? 0;
    echo "<p>Total Fees: RWF " . number_format($totalFees, 0) . "</p>";
    
    $paidStmt = $db->prepare("SELECT SUM(amount) as total FROM payments WHERE student_id = ? AND status = 'verified'");
    $paidStmt->execute([$student['id']]);
    $totalPaid = $paidStmt->fetch()['total'] ?? 0;
    echo "<p>Total Paid: RWF " . number_format($totalPaid, 0) . "</p>";
    
    $balance = $totalFees - $totalPaid;
    echo "<p><strong style='color:blue;'>Balance: RWF " . number_format($balance, 0) . "</strong></p>";
} else {
    echo "<p style='color:red;'>No students found!</p>";
}

echo "<hr><p><a href='views/payments.php'>Go to School Fees Page</a></p>";
?>

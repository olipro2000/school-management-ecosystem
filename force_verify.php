<?php
require_once 'config/db.php';
$db = getDB();

echo "<h2>Force Verify Payments</h2>";

// Get all payments with empty or null status
$payments = $db->query("SELECT id, student_id, amount, status FROM payments WHERE status IS NULL OR status = '' OR status = 'pending'")->fetchAll();

echo "<p>Found " . count($payments) . " payments to verify</p>";

foreach ($payments as $payment) {
    echo "<p>Payment ID {$payment['id']}: ";
    $stmt = $db->prepare("UPDATE payments SET status = 'verified', verified_by = 1, verified_at = NOW() WHERE id = ?");
    if ($stmt->execute([$payment['id']])) {
        echo "<span style='color:green;'>VERIFIED ✓</span>";
    } else {
        echo "<span style='color:red;'>FAILED ✗</span>";
    }
    echo "</p>";
}

echo "<hr><p><a href='trace_query.php'>Check Balance Now</a></p>";
?>

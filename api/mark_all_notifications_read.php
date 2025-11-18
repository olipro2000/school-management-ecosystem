<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$result = $stmt->execute([$_SESSION['user_id']]);

if ($result) {
    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}
?>

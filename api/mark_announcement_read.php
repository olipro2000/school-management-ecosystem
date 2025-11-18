<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$announcement_id = $_GET['id'] ?? null;

if ($announcement_id && isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("INSERT IGNORE INTO announcement_views (announcement_id, user_id) VALUES (?, ?)");
    $stmt->execute([$announcement_id, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>

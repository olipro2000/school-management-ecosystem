<?php
session_start();
require_once '../config/db.php';

$id = $_GET['id'] ?? 0;
$db = getDB();
$stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>

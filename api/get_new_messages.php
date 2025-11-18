<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$db = getDB();
$contactId = $_GET['contact'] ?? null;
$lastId = $_GET['last_id'] ?? 0;

if ($contactId) {
    $stmt = $db->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND m.id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $contactId, $contactId, $_SESSION['user_id'], $lastId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark as read
    if (!empty($messages)) {
        $db->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
           ->execute([$contactId, $_SESSION['user_id']]);
    }
    
    echo json_encode(['messages' => $messages]);
} else {
    echo json_encode(['messages' => []]);
}

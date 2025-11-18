<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'messages':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            echo json_encode(['count' => $result['count']]);
            break;
            
        case 'announcements':
            $stmt = $db->prepare("SELECT title FROM announcements WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY created_at DESC LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            echo json_encode([
                'count' => $result ? 1 : 0,
                'title' => $result['title'] ?? ''
            ]);
            break;
            
        default:
            echo json_encode(['success' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

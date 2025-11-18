<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'initiate':
        $stmt = $db->prepare("INSERT INTO call_signals (caller_id, receiver_id, signal_type, signal_data, created_at) VALUES (?, ?, 'offer', ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id'], $_POST['offer']]);
        echo json_encode(['success' => true, 'call_id' => $db->lastInsertId()]);
        break;
        
    case 'answer':
        $stmt = $db->prepare("INSERT INTO call_signals (caller_id, receiver_id, signal_type, signal_data, created_at) VALUES (?, ?, 'answer', ?, NOW())");
        $stmt->execute([$_POST['caller_id'], $_SESSION['user_id'], $_POST['answer']]);
        echo json_encode(['success' => true]);
        break;
        
    case 'ice':
        $stmt = $db->prepare("INSERT INTO call_signals (caller_id, receiver_id, signal_type, signal_data, created_at) VALUES (?, ?, 'ice', ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id'], $_POST['candidate']]);
        echo json_encode(['success' => true]);
        break;
        
    case 'poll':
        $stmt = $db->prepare("SELECT * FROM call_signals WHERE receiver_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND) ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $signals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($signals)) {
            $ids = array_column($signals, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $deleteStmt = $db->prepare("DELETE FROM call_signals WHERE id IN ($placeholders)");
            $deleteStmt->execute($ids);
        }
        
        echo json_encode(['signals' => $signals]);
        break;
        
    case 'end':
        $stmt = $db->prepare("DELETE FROM call_signals WHERE (caller_id = ? OR receiver_id = ?)");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        break;
}

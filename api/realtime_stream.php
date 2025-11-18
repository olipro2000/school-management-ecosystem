<?php
session_start();
require_once '../config/db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

set_time_limit(30);
ignore_user_abort(false);

$db = getDB();
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) exit;

$lastCheck = $_GET['last'] ?? time();
$iterations = 0;
$maxIterations = 15;

while ($iterations < $maxIterations && !connection_aborted()) {
    $data = [];
    
    $stmt = $db->prepare("SELECT 
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0 AND created_at > FROM_UNIXTIME(?)) as new_msg,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0) as total_msg,
        (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0) as total_notif");
    $stmt->execute([$userId, $lastCheck, $userId, $userId]);
    $counts = $stmt->fetch();
    
    if ($counts['new_msg'] > 0) $data['new_messages'] = $counts['new_msg'];
    $data['unread_messages'] = $counts['total_msg'];
    $data['unread_count'] = $counts['total_notif'];
    
    if (!empty($data)) {
        echo "data: " . json_encode($data) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }
    
    $lastCheck = time();
    $iterations++;
    sleep(2);
}

echo "data: {\"ping\":1}\n\n";
if (ob_get_level() > 0) ob_flush();
flush();
?>

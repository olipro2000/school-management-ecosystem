<?php
require_once '../config/db.php';

function sendNotification($userId, $type, $title, $content = '', $link = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, content, link) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $title, $content, $link]);
}
?>

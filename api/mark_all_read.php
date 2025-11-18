<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$stmt = $db->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);

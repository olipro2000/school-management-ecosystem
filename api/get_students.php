<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    if (isset($_GET['subject_id'])) {
        $stmt = $db->prepare("
            SELECT s.id, s.student_id, u.name 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            JOIN subjects sub ON s.class_id = sub.class_id 
            WHERE sub.id = ? AND s.status = 'active'
            ORDER BY u.name
        ");
        $stmt->execute([$_GET['subject_id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($students);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

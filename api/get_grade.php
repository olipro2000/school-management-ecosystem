<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM grades WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $grade = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($grade);
    } else {
        echo json_encode(['error' => 'No ID provided']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

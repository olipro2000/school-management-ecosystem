<?php
session_start();
require_once '../config/db.php';
require_once '../utils/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $stmt = $db->prepare("INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['subject_name'],
                $_POST['subject_code'],
                $_POST['class_id'],
                !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null
            ]);
            
            redirectWithSuccess('../views/subjects.php', 'Subject added successfully!');
            break;
            
        case 'edit':
            $stmt = $db->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=?, teacher_id=? WHERE id=?");
            $stmt->execute([
                $_POST['subject_name'],
                $_POST['subject_code'],
                $_POST['class_id'],
                !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null,
                $_POST['subject_id']
            ]);
            
            redirectWithSuccess('../views/subjects.php', 'Subject updated successfully!');
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM subjects WHERE id=?");
            $stmt->execute([$_POST['id']]);
            
            redirectWithSuccess('../views/subjects.php', 'Subject deleted successfully!');
            break;
            
        case 'get':
            $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    redirectWithError('../views/subjects.php', 'Error: ' . $e->getMessage());
}

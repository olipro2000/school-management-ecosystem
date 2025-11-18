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
            $stmt = $db->prepare("INSERT INTO classes (class_name, section, class_teacher_id, capacity) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['class_name'],
                $_POST['section'],
                $_POST['class_teacher_id'] ?: null,
                $_POST['capacity']
            ]);
            
            redirectWithSuccess('../views/classes.php', 'Class added successfully!');
            break;
            
        case 'edit':
            $stmt = $db->prepare("UPDATE classes SET class_name=?, section=?, class_teacher_id=?, capacity=? WHERE id=?");
            $stmt->execute([
                $_POST['class_name'],
                $_POST['section'],
                $_POST['class_teacher_id'] ?: null,
                $_POST['capacity'],
                $_POST['class_id']
            ]);
            
            redirectWithSuccess('../views/classes.php', 'Class updated successfully!');
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM classes WHERE id=?");
            $stmt->execute([$_POST['id']]);
            
            redirectWithSuccess('../views/classes.php', 'Class deleted successfully!');
            break;
            
        case 'get':
            $stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
            break;
            
        case 'assign_teacher':
            $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
            $stmt = $db->prepare("UPDATE classes SET class_teacher_id=? WHERE id=?");
            $result = $stmt->execute([$teacher_id, $_POST['class_id']]);
            
            if ($result) {
                redirectWithSuccess('../views/classes.php', 'Teacher assigned successfully!');
            } else {
                redirectWithError('../views/classes.php', 'Failed to assign teacher!');
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    redirectWithError('../views/classes.php', 'Error: ' . $e->getMessage());
}

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
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, gender, phone, address) VALUES (?, ?, ?, 'parent', ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['gender'],
                $_POST['phone'],
                $_POST['address']
            ]);
            
            redirectWithSuccess('../views/parents.php', 'Parent added successfully!');
            break;
            
        case 'edit':
            if (!empty($_POST['password'])) {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, password=?, gender=?, phone=?, address=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['parent_id']
                ]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, gender=?, phone=?, address=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['parent_id']
                ]);
            }
            
            redirectWithSuccess('../views/parents.php', 'Parent updated successfully!');
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM users WHERE id=? AND role='parent'");
            $stmt->execute([$_POST['id']]);
            
            redirectWithSuccess('../views/parents.php', 'Parent deleted successfully!');
            break;
            
        case 'get':
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role='parent'");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
            break;
            
        case 'link_child':
            // First check if parent record exists
            $check = $db->prepare("SELECT id FROM parents WHERE user_id = ?");
            $check->execute([$_POST['parent_id']]);
            $parent_record = $check->fetch();
            
            if (!$parent_record) {
                // Create parent record first
                $stmt = $db->prepare("INSERT INTO parents (user_id, relationship) VALUES (?, ?)");
                $stmt->execute([$_POST['parent_id'], $_POST['relationship']]);
                $parent_id = $db->lastInsertId();
            } else {
                $parent_id = $parent_record['id'];
            }
            
            // Update student with parent_id
            $stmt = $db->prepare("UPDATE students SET parent_id = ? WHERE id = ?");
            $stmt->execute([$parent_id, $_POST['student_id']]);
            
            redirectWithSuccess('../views/parents.php', 'Child linked successfully!');
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    redirectWithError('../views/parents.php', 'Error: ' . $e->getMessage());
}

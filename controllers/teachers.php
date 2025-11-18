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
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, gender, phone, address) VALUES (?, ?, ?, 'teacher', ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT),
                $_POST['gender'],
                $_POST['phone'],
                $_POST['address']
            ]);
            
            redirectWithSuccess('../views/teachers.php', 'Teacher added successfully!');
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
                    $_POST['teacher_id']
                ]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, gender=?, phone=?, address=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['teacher_id']
                ]);
            }
            
            redirectWithSuccess('../views/teachers.php', 'Teacher updated successfully!');
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM users WHERE id=? AND role='teacher'");
            $stmt->execute([$_POST['id']]);
            
            redirectWithSuccess('../views/teachers.php', 'Teacher deleted successfully!');
            break;
            
        case 'get':
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role='teacher'");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    redirectWithError('../views/teachers.php', 'Error: ' . $e->getMessage());
}

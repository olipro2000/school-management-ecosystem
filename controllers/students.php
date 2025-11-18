<?php
session_start();
require_once '../config/db.php';
require_once '../utils/helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, gender, phone, address) VALUES (?, ?, ?, 'student', ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                password_hash('student123', PASSWORD_DEFAULT),
                $_POST['gender'],
                $_POST['phone'],
                $_POST['address']
            ]);
            
            $user_id = $db->lastInsertId();
            
            // Generate student ID: YY + MM + sequential number
            $year = date('y');
            $month = date('m');
            $prefix = $year . $month;
            
            $stmt = $db->prepare("SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
            $stmt->execute([$prefix . '%']);
            $lastStudent = $stmt->fetch();
            
            if ($lastStudent) {
                $lastNum = intval(substr($lastStudent['student_id'], 4));
                $newNum = $lastNum + 1;
            } else {
                $newNum = 1;
            }
            
            $studentId = $prefix . str_pad($newNum, 4, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO students (user_id, student_id, class_id, admission_date, date_of_birth, blood_group) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $studentId,
                $_POST['class_id'],
                $_POST['admission_date'],
                $_POST['date_of_birth'],
                $_POST['blood_group']
            ]);
            
            redirectWithSuccess('../views/students.php', 'Student added successfully!');
            break;
            
        case 'edit':
            $getUser = $db->prepare("SELECT user_id FROM students WHERE id=?");
            $getUser->execute([$_POST['student_id']]);
            $user_id = $getUser->fetch()['user_id'];
            
            if (!empty($_POST['password'])) {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, gender=?, phone=?, address=?, password=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $user_id
                ]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, gender=?, phone=?, address=? WHERE id=?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['gender'],
                    $_POST['phone'],
                    $_POST['address'],
                    $user_id
                ]);
            }
            
            $stmt = $db->prepare("UPDATE students SET class_id=?, date_of_birth=?, blood_group=? WHERE id=?");
            $stmt->execute([
                $_POST['class_id'],
                $_POST['date_of_birth'],
                $_POST['blood_group'],
                $_POST['student_id']
            ]);
            
            redirectWithSuccess('../views/students.php', 'Student updated successfully!');
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM students WHERE id=?");
            $stmt->execute([$_POST['id']]);
            
            redirectWithSuccess('../views/students.php', 'Student deleted successfully!');
            break;
            
        case 'get':
            $stmt = $db->prepare("
                SELECT s.*, u.name, u.email, u.phone, u.gender, u.address, c.class_name, c.section 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN classes c ON s.class_id = c.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    redirectWithError('../views/students.php', 'Error: ' . $e->getMessage());
}

<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkAuth(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/class_promotion.php');
    exit;
}

$db = getDB();
$from_class_id = $_POST['from_class_id'];
$to_class_id = $_POST['to_class_id'];
$academic_year = $_POST['academic_year'];
$pass_mark = $_POST['pass_mark'] ?? 50;
$admin_id = $_SESSION['user_id'];

try {
    $db->beginTransaction();
    
    // Get all students in the from_class
    $stmt = $db->prepare("SELECT id FROM students WHERE class_id = ? AND status = 'active'");
    $stmt->execute([$from_class_id]);
    $students = $stmt->fetchAll();
    
    $promoted = 0;
    $repeated = 0;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        // Calculate average marks for annual term
        $stmt = $db->prepare("
            SELECT AVG(total_marks) as average 
            FROM grades 
            WHERE student_id = ? AND term = 'annual' AND academic_year = ?
        ");
        $stmt->execute([$student_id, $academic_year]);
        $result = $stmt->fetch();
        $average = $result['average'] ?? 0;
        
        // Determine promotion status
        if ($average >= $pass_mark) {
            // Promote student
            $stmt = $db->prepare("UPDATE students SET class_id = ? WHERE id = ?");
            $stmt->execute([$to_class_id, $student_id]);
            
            $stmt = $db->prepare("
                INSERT INTO student_promotions 
                (student_id, from_class_id, to_class_id, academic_year, status, average_marks, promoted_by) 
                VALUES (?, ?, ?, ?, 'promoted', ?, ?)
            ");
            $stmt->execute([$student_id, $from_class_id, $to_class_id, $academic_year, $average, $admin_id]);
            $promoted++;
        } else {
            // Student repeats
            $stmt = $db->prepare("
                INSERT INTO student_promotions 
                (student_id, from_class_id, to_class_id, academic_year, status, average_marks, promoted_by) 
                VALUES (?, ?, ?, ?, 'repeated', ?, ?)
            ");
            $stmt->execute([$student_id, $from_class_id, $from_class_id, $academic_year, $average, $admin_id]);
            $repeated++;
        }
    }
    
    $db->commit();
    $_SESSION['message'] = "Promotion completed: $promoted promoted, $repeated repeated";
    $_SESSION['message_type'] = 'success';
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['message'] = 'Error: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

header('Location: ../views/class_promotion.php');
exit;

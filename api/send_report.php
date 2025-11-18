<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$student_id = $_GET['student_id'];
$class_id = $_GET['class_id'];
$term = $_GET['term'];
$year = $_GET['year'];

$student = $db->prepare("SELECT s.*, u.id as student_user_id, u.name, u.email as student_email, p.user_id as parent_user_id, pu.email as parent_email, pu.name as parent_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN parents p ON s.parent_id = p.id LEFT JOIN users pu ON p.user_id = pu.id WHERE s.id = ?");
$student->execute([$student_id]);
$student = $student->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$report_url = "http://" . $_SERVER['HTTP_HOST'] . "/skl/api/generate_student_report.php?student_id=$student_id&class_id=$class_id&term=$term&year=$year";

$message = "Your report card for $term term, $year is ready. View it here: $report_url";

$stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");

$stmt->execute([$_SESSION['user_id'], $student['student_user_id'], "Report Card - $term $year", $message]);

if ($student['parent_user_id']) {
    $stmt->execute([$_SESSION['user_id'], $student['parent_user_id'], "Report Card for " . $student['name'] . " - $term $year", $message]);
}

echo json_encode(['success' => true, 'message' => 'Report sent to student and parent']);
?>

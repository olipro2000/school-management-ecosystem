<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$class_id = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

$data = $db->prepare("SELECT u.name, s.student_id, a.status, a.date FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN attendance a ON u.id = a.user_id AND a.date = ? WHERE s.class_id = ? ORDER BY u.name");
$data->execute([$date, $class_id]);
$records = $data->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_' . $date . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Student ID', 'Status', 'Date']);

foreach ($records as $record) {
    fputcsv($output, [$record['name'], $record['student_id'], $record['status'] ?: 'Not Marked', $record['date']]);
}

fclose($output);
exit;
?>

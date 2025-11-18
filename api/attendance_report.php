<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$class_id = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

$class = $db->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$class_id]);
$class = $class->fetch();

$stats = $db->prepare("SELECT COUNT(DISTINCT s.id) as total, COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present, COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent, COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN attendance a ON u.id = a.user_id AND a.date = ? WHERE s.class_id = ?");
$stats->execute([$date, $class_id]);
$stats = $stats->fetch();

$records = $db->prepare("SELECT u.name, s.student_id, a.status FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN attendance a ON u.id = a.user_id AND a.date = ? WHERE s.class_id = ? ORDER BY u.name");
$records->execute([$date, $class_id]);
$records = $records->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #667eea; color: white; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { padding: 15px; border-radius: 5px; color: white; flex: 1; }
        .present { background: #15803d; }
        .absent { background: #b91c1c; }
        .late { background: #ca8a04; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>
    <p><strong>Class:</strong> <?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></p>
    <p><strong>Date:</strong> <?= date('F d, Y', strtotime($date)) ?></p>
    
    <div class="stats">
        <div class="stat-box present">
            <h3><?= $stats['present'] ?></h3>
            <p>Present</p>
        </div>
        <div class="stat-box absent">
            <h3><?= $stats['absent'] ?></h3>
            <p>Absent</p>
        </div>
        <div class="stat-box late">
            <h3><?= $stats['late'] ?></h3>
            <p>Late</p>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Student ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
            <tr>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['student_id']) ?></td>
                <td><?= htmlspecialchars($record['status'] ?: 'Not Marked') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <button onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Report</button>
</body>
</html>

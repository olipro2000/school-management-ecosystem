<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$class_id = $_GET['class_id'];
$term = $_GET['term'];
$year = $_GET['year'];

$class = $db->prepare("SELECT * FROM classes WHERE id = ?");
$class->execute([$class_id]);
$class = $class->fetch();

$students = $db->prepare("SELECT s.id, s.student_id, u.name, 
    (SELECT SUM(total_marks) FROM grades WHERE student_id = s.id AND term = ? AND academic_year = ?) as total_marks,
    (SELECT COUNT(*) FROM grades WHERE student_id = s.id AND term = ? AND academic_year = ?) as subject_count
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.class_id = ? AND s.status = 'active'
    ORDER BY total_marks DESC");
$students->execute([$term, $year, $term, $year, $class_id]);
$students = $students->fetchAll();

$position = 1;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class Report - <?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #667eea; color: white; }
        .position { font-weight: bold; color: #667eea; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>Class Report</h1>
    <p><strong>Class:</strong> <?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></p>
    <p><strong>Term:</strong> <?= htmlspecialchars($term) ?> | <strong>Year:</strong> <?= htmlspecialchars($year) ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Total Marks</th>
                <th>Average</th>
                <?php if ($term == 'annual'): ?>
                <th>Status</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): 
                $average = $student['subject_count'] > 0 ? round($student['total_marks'] / $student['subject_count'], 2) : 0;
            ?>
            <tr>
                <td class="position"><?= $position++ ?></td>
                <td><?= htmlspecialchars($student['student_id']) ?></td>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <td><?= number_format($student['total_marks'], 2) ?></td>
                <td><?= $average ?>%</td>
                <?php if ($term == 'annual'): ?>
                <td><?= $average >= 50 ? '<span style="color: green;">PASS</span>' : '<span style="color: red;">REPEAT</span>' ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <button onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Report</button>
</body>
</html>

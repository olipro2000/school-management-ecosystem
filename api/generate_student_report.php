<?php
session_start();
require_once '../config/db.php';

$db = getDB();
$student_id = $_GET['student_id'];
$class_id = $_GET['class_id'];
$term = $_GET['term'];
$year = $_GET['year'];

$student = $db->prepare("SELECT s.*, u.name, c.class_name, c.section FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id WHERE s.id = ?");
$student->execute([$student_id]);
$student = $student->fetch();

$grades = $db->prepare("SELECT sub.subject_name, g.cat1_marks, g.cat2_marks, g.exam_marks, g.total_marks, g.grade FROM grades g JOIN subjects sub ON g.subject_id = sub.id WHERE g.student_id = ? AND g.term = ? AND g.academic_year = ? ORDER BY sub.subject_name");
$grades->execute([$student_id, $term, $year]);
$grades = $grades->fetchAll();

$total = array_sum(array_column($grades, 'total_marks'));
$average = count($grades) > 0 ? round($total / count($grades), 2) : 0;

$all_students = $db->prepare("SELECT s.id, SUM(g.total_marks) as total FROM students s JOIN grades g ON s.id = g.student_id WHERE s.class_id = ? AND g.term = ? AND g.academic_year = ? GROUP BY s.id ORDER BY total DESC");
$all_students->execute([$class_id, $term, $year]);
$all_students = $all_students->fetchAll();

$position = 1;
foreach ($all_students as $st) {
    if ($st['id'] == $student_id) break;
    $position++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Report - <?= htmlspecialchars($student['name']) ?></title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h1 { color: #333; text-align: center; }
        .info { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #667eea; color: white; }
        .summary { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <h1>Student Report Card</h1>
    
    <div class="info">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
        <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name'] . ' ' . $student['section']) ?></p>
        <p><strong>Term:</strong> <?= htmlspecialchars($term) ?> | <strong>Year:</strong> <?= htmlspecialchars($year) ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>CAT 1 (30)</th>
                <th>CAT 2 (30)</th>
                <th>Exam (40)</th>
                <th>Total (100)</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grades as $grade): ?>
            <tr>
                <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                <td><?= $grade['cat1_marks'] ?></td>
                <td><?= $grade['cat2_marks'] ?></td>
                <td><?= $grade['exam_marks'] ?></td>
                <td><strong><?= $grade['total_marks'] ?></strong></td>
                <td><?= htmlspecialchars($grade['grade']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="summary">
        <p><strong>Total Marks:</strong> <?= $total ?></p>
        <p><strong>Average:</strong> <?= $average ?>%</p>
        <p><strong>Position:</strong> <?= $position ?> out of <?= count($all_students) ?></p>
        <?php if ($term == 'annual'): ?>
        <p><strong>Status:</strong> <?= $average >= 50 ? '<span style="color: green; font-weight: bold;">PROMOTED</span>' : '<span style="color: red; font-weight: bold;">REPEAT</span>' ?></p>
        <?php endif; ?>
    </div>
    
    <button onclick="window.print()" style="margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Report</button>
</body>
</html>

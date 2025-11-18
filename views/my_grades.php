<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'student') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$student = $db->prepare("SELECT id FROM students WHERE user_id = ?");
$student->execute([$_SESSION['user_id']]);
$student_id = $student->fetch()['id'];

$grades = $db->prepare("SELECT s.subject_name, g.cat1_marks, g.cat2_marks, g.exam_marks, g.total_marks, g.grade, g.term, g.academic_year FROM grades g JOIN subjects s ON g.subject_id = s.id WHERE g.student_id = ? ORDER BY g.academic_year DESC, g.term DESC, s.subject_name");
$grades->execute([$student_id]);
$grades = $grades->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-chart-line me-2"></i>My Grades</h1>
        </div>
        
        <?php if ($grades): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>CAT 1 (30)</th>
                                <th>CAT 2 (30)</th>
                                <th>Exam (40)</th>
                                <th>Total (100)</th>
                                <th>Grade</th>
                                <th>Term</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($grade['subject_name']) ?></strong></td>
                                <td><?= htmlspecialchars($grade['cat1_marks']) ?></td>
                                <td><?= htmlspecialchars($grade['cat2_marks']) ?></td>
                                <td><?= htmlspecialchars($grade['exam_marks']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($grade['total_marks']) ?></td>
                                <td><span class="badge bg-<?= $grade['total_marks'] >= 70 ? 'success' : ($grade['total_marks'] >= 50 ? 'warning' : 'danger') ?>"><?= htmlspecialchars($grade['grade'] ?: 'N/A') ?></span></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($grade['term']) ?></span></td>
                                <td><?= htmlspecialchars($grade['academic_year']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                <h5>No Grades Available</h5>
                <p class="text-muted">Your grades will appear here once they are entered by your teachers.</p>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

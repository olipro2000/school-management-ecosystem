<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$teacher_classes = $db->prepare("SELECT DISTINCT c.* FROM classes c JOIN subjects s ON c.id = s.class_id WHERE s.teacher_id = ? OR c.class_teacher_id = ?");
$teacher_classes->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$teacher_classes = $teacher_classes->fetchAll();

$selected_class = $_GET['class_id'] ?? null;
$selected_term = $_GET['term'] ?? '1st';
$selected_year = $_GET['year'] ?? '2024-2025';

if ($selected_class) {
    $class_info = $db->prepare("SELECT * FROM classes WHERE id = ?");
    $class_info->execute([$selected_class]);
    $class_info = $class_info->fetch();
}
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-file-alt me-2"></i>Class Reports</h1>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">Choose Class</option>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Term</label>
                        <select name="term" class="form-select" onchange="this.form.submit()">
                            <option value="1st" <?= $selected_term == '1st' ? 'selected' : '' ?>>1st Term</option>
                            <option value="2nd" <?= $selected_term == '2nd' ? 'selected' : '' ?>>2nd Term</option>
                            <option value="3rd" <?= $selected_term == '3rd' ? 'selected' : '' ?>>3rd Term</option>
                            <option value="annual" <?= $selected_term == 'annual' ? 'selected' : '' ?>>Annual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="year" class="form-control" value="<?= htmlspecialchars($selected_year) ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="generateClassReport()" <?= !$selected_class ? 'disabled' : '' ?>>
                            <i class="fas fa-file-pdf me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($selected_class): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Students in <?= htmlspecialchars($class_info['class_name'] . ' ' . $class_info['section']) ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $students = $db->prepare("SELECT s.id, s.student_id, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.class_id = ? AND s.status = 'active' ORDER BY u.name");
                            $students->execute([$selected_class]);
                            $students = $students->fetchAll();
                            
                            foreach ($students as $student):
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($student['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($student['student_id']) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="generateStudentReport(<?= $student['id'] ?>)">
                                            <i class="fas fa-file-pdf"></i> View Report
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="sendReport(<?= $student['id'] ?>)">
                                            <i class="fas fa-paper-plane"></i> Send Report
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
function generateClassReport() {
    const classId = new URLSearchParams(window.location.search).get('class_id');
    const term = new URLSearchParams(window.location.search).get('term') || '1st';
    const year = new URLSearchParams(window.location.search).get('year') || '2024-2025';
    window.open(`../api/generate_class_report.php?class_id=${classId}&term=${term}&year=${year}`, '_blank');
}

function generateStudentReport(studentId) {
    const classId = new URLSearchParams(window.location.search).get('class_id');
    const term = new URLSearchParams(window.location.search).get('term') || '1st';
    const year = new URLSearchParams(window.location.search).get('year') || '2024-2025';
    window.open(`../api/generate_student_report.php?student_id=${studentId}&class_id=${classId}&term=${term}&year=${year}`, '_blank');
}

function sendReport(studentId) {
    const classId = new URLSearchParams(window.location.search).get('class_id');
    const term = new URLSearchParams(window.location.search).get('term') || '1st';
    const year = new URLSearchParams(window.location.search).get('year') || '2024-2025';
    
    if (confirm('Send report to student and parent?')) {
        fetch(`../api/send_report.php?student_id=${studentId}&class_id=${classId}&term=${term}&year=${year}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Report sent successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }
}
</script>

<?php include '../includes/footer.php'; ?>

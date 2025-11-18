<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$db = getDB();

if ($_POST && isset($_POST['add_grade'])) {
    try {
        $total = $_POST['cat1_marks'] + $_POST['cat2_marks'] + $_POST['exam_marks'];
        $grade = $total >= 90 ? 'A+' : ($total >= 80 ? 'A' : ($total >= 70 ? 'B' : ($total >= 60 ? 'C' : 'F')));
        
        if (isset($_POST['grade_id'])) {
            $stmt = $db->prepare("UPDATE grades SET cat1_marks = ?, cat2_marks = ?, exam_marks = ?, grade = ?, term = ?, academic_year = ?, remarks = ?, teacher_id = ? WHERE id = ?");
            if ($stmt->execute([$_POST['cat1_marks'], $_POST['cat2_marks'], $_POST['exam_marks'], $grade, $_POST['term'], $_POST['academic_year'], $_POST['remarks'], $_SESSION['user_id'], $_POST['grade_id']])) {
                $_SESSION['success'] = "Grade updated successfully!";
                header('Location: grades.php?subject_id=' . $_POST['subject_id']);
                exit;
            }
        } else {
            $checkStmt = $db->prepare("SELECT id FROM students WHERE id = ?");
            $checkStmt->execute([$_POST['student_id']]);
            if (!$checkStmt->fetch()) {
                $_SESSION['error'] = "Invalid student selected";
                header('Location: grades.php?subject_id=' . $_POST['subject_id']);
                exit;
            }
            
            $checkDuplicate = $db->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ? AND term = ? AND academic_year = ? AND id != ?");
            $checkDuplicate->execute([$_POST['student_id'], $_POST['subject_id'], $_POST['term'], $_POST['academic_year'], $_POST['grade_id'] ?? 0]);
            if ($checkDuplicate->fetch()) {
                $_SESSION['error'] = "Grade already exists for this student, subject, and term. Please edit the existing grade instead.";
                header('Location: grades.php?subject_id=' . $_POST['subject_id']);
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO grades (student_id, subject_id, cat1_marks, cat2_marks, exam_marks, grade, term, academic_year, remarks, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_POST['student_id'], $_POST['subject_id'], $_POST['cat1_marks'], $_POST['cat2_marks'], $_POST['exam_marks'], $grade, $_POST['term'], $_POST['academic_year'], $_POST['remarks'], $_SESSION['user_id']])) {
                $_SESSION['success'] = "Grade added successfully!";
                header('Location: grades.php?subject_id=' . $_POST['subject_id']);
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: grades.php?subject_id=' . $_POST['subject_id']);
        exit;
    }
}

require_once '../includes/header.php';

$subjects = $db->query("SELECT s.*, c.class_name, c.section FROM subjects s JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.subject_name")->fetchAll();
$selectedSubject = $_GET['subject_id'] ?? ($subjects[0]['id'] ?? null);

$grades = [];
if ($selectedSubject) {
    $grades = $db->prepare("
        SELECT g.*, st.student_id, u.name as student_name, s.subject_name
        FROM grades g
        JOIN students st ON g.student_id = st.id
        JOIN users u ON st.user_id = u.id
        JOIN subjects s ON g.subject_id = s.id
        WHERE g.subject_id = ?
        ORDER BY g.total_marks DESC
    ");
    $grades->execute([$selectedSubject]);
    $grades = $grades->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-graduation-cap me-3"></i>Grade Management</h1>
                    <button class="btn btn-primary morph-btn neon-blue" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                        <i class="fas fa-plus me-2"></i>Add Grade
                    </button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); endif; ?>
            
            <!-- Grade Analytics -->
            <div class="stats-grid mb-4">
                <div class="advanced-card stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Average Score</h6>
                            <h2 class="mb-0"><?= count($grades) > 0 ? round(array_sum(array_column($grades, 'total_marks')) / count($grades), 1) : 0 ?></h2>
                        </div>
                        <canvas id="gradeChart" width="80" height="80"></canvas>
                    </div>
                </div>
                
                <div class="advanced-card stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Total Grades</h6>
                            <h2 class="mb-0"><?= count($grades) ?></h2>
                        </div>
                        <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">A+ Students</h6>
                            <h2 class="mb-0"><?= count(array_filter($grades, fn($g) => $g['grade'] == 'A+')) ?></h2>
                        </div>
                        <i class="fas fa-trophy fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Pass Rate</h6>
                            <h2 class="mb-0"><?= count($grades) > 0 ? round((count(array_filter($grades, fn($g) => $g['total_marks'] >= 60)) / count($grades)) * 100, 1) : 0 ?>%</h2>
                        </div>
                        <div class="progress-advanced mt-2">
                            <div class="progress-bar" style="width: <?= count($grades) > 0 ? (count(array_filter($grades, fn($g) => $g['total_marks'] >= 60)) / count($grades)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Subject Filter -->
            <div class="card advanced-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Select Subject</label>
                            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= $selectedSubject == $subject['id'] ? 'selected' : '' ?>>
                                        <?= $subject['subject_name'] ?> (<?= $subject['class_name'] ?> <?= $subject['section'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary w-100 morph-btn" onclick="generateReport()">
                                <i class="fas fa-chart-line me-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Grades Table -->
            <div class="card advanced-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Student Grades</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-trophy me-2"></i>Rank</th>
                                    <th><i class="fas fa-user me-2"></i>Student</th>
                                    <th>CAT 1</th>
                                    <th>CAT 2</th>
                                    <th>Exam</th>
                                    <th>Total</th>
                                    <th><i class="fas fa-medal me-2"></i>Grade</th>
                                    <th>Term</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $index => $grade): ?>
                                <tr class="grade-row" data-grade="<?= $grade['grade'] ?>">
                                    <td>
                                        <div class="rank-badge rank-<?= $index + 1 ?>">
                                            <?php if ($index == 0): ?>
                                                <i class="fas fa-crown text-warning"></i>
                                            <?php elseif ($index == 1): ?>
                                                <i class="fas fa-medal text-secondary"></i>
                                            <?php elseif ($index == 2): ?>
                                                <i class="fas fa-award text-warning"></i>
                                            <?php else: ?>
                                                <?= $index + 1 ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($grade['student_name']) ?></h6>
                                                <small class="text-muted"><?= $grade['student_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $grade['cat1_marks'] ?></td>
                                    <td><?= $grade['cat2_marks'] ?></td>
                                    <td><?= $grade['exam_marks'] ?></td>
                                    <td class="fw-bold"><?= $grade['total_marks'] ?></td>
                                    <td>
                                        <span class="grade-badge grade-<?= strtolower($grade['grade']) ?>">
                                            <?= $grade['grade'] ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-info"><?= $grade['term'] ?></span></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary morph-btn" onclick="editGrade(<?= $grade['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger morph-btn" onclick="deleteGrade(<?= $grade['id'] ?>)">
                                                <i class="fas fa-trash"></i>
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
        </main>
    </div>
</div>

<!-- Add Grade Modal -->
<div class="modal fade advanced-modal" id="addGradeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" id="subjectSelect" class="form-select" required>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>">
                                    <?= $subject['subject_name'] ?> (<?= $subject['class_name'] ?> <?= $subject['section'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select name="student_id" id="studentSelect" class="form-select" required>
                            <option value="">Select Student</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">CAT 1 (30)</label>
                                <input type="number" name="cat1_marks" class="form-control" required min="0" max="30" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">CAT 2 (30)</label>
                                <input type="number" name="cat2_marks" class="form-control" required min="0" max="30" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Exam (40)</label>
                                <input type="number" name="exam_marks" class="form-control" required min="0" max="40" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Term</label>
                                <select name="term" class="form-select" required>
                                    <option value="1st">1st Term</option>
                                    <option value="2nd">2nd Term</option>
                                    <option value="3rd">3rd Term</option>
                                    <option value="annual">Annual</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control" required value="2024-2025">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_grade" class="btn btn-primary morph-btn">
                        <i class="fas fa-save me-2"></i>Add Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateReport() {
    window.advancedSMS.showAdvancedNotification('Report', 'Generating grade report...', 'info');
}

function editGrade(id) {
    fetch(`../api/get_grade.php?id=${id}`)
        .then(response => response.json())
        .then(grade => {
            const modal = document.getElementById('addGradeModal');
            const form = modal.querySelector('form');
            
            form.querySelector('input[name="cat1_marks"]').value = grade.cat1_marks;
            form.querySelector('input[name="cat2_marks"]').value = grade.cat2_marks;
            form.querySelector('input[name="exam_marks"]').value = grade.exam_marks;
            form.querySelector('select[name="term"]').value = grade.term;
            form.querySelector('input[name="academic_year"]').value = grade.academic_year;
            form.querySelector('textarea[name="remarks"]').value = grade.remarks || '';
            form.querySelector('select[name="subject_id"]').value = grade.subject_id;
            
            window.loadStudents(grade.subject_id);
            setTimeout(() => {
                form.querySelector('select[name="student_id"]').value = grade.student_id;
            }, 500);
            
            form.querySelector('button[name="add_grade"]').innerHTML = '<i class="fas fa-save me-2"></i>Update Grade';
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="grade_id" value="${id}">`);
            
            new bootstrap.Modal(modal).show();
        })
        .catch(error => console.error('Error loading grade:', error));
}

function deleteGrade(id) {
    if (confirm('Are you sure you want to delete this grade?')) {
        window.advancedSMS.showAdvancedNotification('Grade Deleted', 'Grade has been removed', 'success');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.getElementById('subjectSelect');
    const studentSelect = document.getElementById('studentSelect');
    const modal = document.getElementById('addGradeModal');
    const form = modal.querySelector('form');
    const submitBtn = form.querySelector('button[name="add_grade"]');
    
    // Reset form when modal closes
    modal.addEventListener('hidden.bs.modal', function() {
        form.querySelectorAll('input[name="grade_id"]').forEach(input => input.remove());
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Add Grade';
        form.reset();
    });
    
    // Load students when modal opens for new grade
    modal.addEventListener('show.bs.modal', function() {
        if (!form.querySelector('input[name="grade_id"]') && subjectSelect.value) {
            loadStudents(subjectSelect.value);
        }
    });
    
    // Load students when subject changes
    subjectSelect.addEventListener('change', function() {
        loadStudents(this.value);
    });
    
    function loadStudents(subjectId) {
        studentSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`../api/get_students.php?subject_id=${subjectId}`)
            .then(response => response.json())
            .then(students => {
                studentSelect.innerHTML = '<option value="">Select Student</option>';
                if (students.error) {
                    console.error('Error:', students.error);
                    studentSelect.innerHTML = '<option value="">Error loading students</option>';
                } else {
                    students.forEach(student => {
                        studentSelect.innerHTML += `<option value="${student.id}">${student.name} (${student.student_id})</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading students:', error);
                studentSelect.innerHTML = '<option value="">Error loading students</option>';
            });
    }
    
    window.loadStudents = loadStudents;
});
</script>

<style>
.rank-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.marks-display .obtained {
    font-size: 1.2rem;
    font-weight: bold;
    color: #667eea;
}

.marks-display .total {
    color: #999;
}

.grade-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 1.1rem;
}

.grade-a\+ { background: linear-gradient(135deg, #00ff88, #00cc6a); color: white; }
.grade-a { background: linear-gradient(135deg, #4ecdc4, #44a08d); color: white; }
.grade-b { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); color: #333; }
.grade-c { background: linear-gradient(135deg, #fab1a0, #e17055); color: white; }
.grade-f { background: linear-gradient(135deg, #ff7675, #d63031); color: white; }

.percentage-circle {
    animation: rotateIn 1s ease;
}

@keyframes rotateIn {
    from { transform: rotate(-180deg); opacity: 0; }
    to { transform: rotate(0deg); opacity: 1; }
}

.grade-row:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    transform: scale(1.01);
}
</style>

<?php include '../includes/footer.php'; ?>
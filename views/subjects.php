<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$subjects = $db->query("
    SELECT s.*, c.class_name, c.section, COALESCE(NULLIF(u.name, ''), u.email) as teacher_name
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY c.class_name, s.subject_name
")->fetchAll();

$classes = $db->query("SELECT id, class_name, section FROM classes WHERE status = 'active'")->fetchAll();
$teachers = $db->query("SELECT id, COALESCE(NULLIF(name, ''), email) as name FROM users WHERE role = 'teacher'")->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-book me-2"></i>Subjects</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-2"></i>Add Subject
                    </button>
                </div>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-book me-3"></i>Subject Management</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Subjects</h5>
                            <h2 class="mb-0"><?= count($subjects) ?></h2>
                        </div>
                        <i class="fas fa-book fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Classes</h5>
                            <h2 class="mb-0"><?= count($classes) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Teachers</h5>
                            <h2 class="mb-0"><?= count($teachers) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Assigned</h5>
                            <h2 class="mb-0"><?= count(array_filter($subjects, fn($s) => $s['teacher_id'])) ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Subjects Directory</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Subject</th>
                                    <th><i class="fas fa-code me-2"></i>Code</th>
                                    <th><i class="fas fa-chalkboard me-2"></i>Class</th>
                                    <th><i class="fas fa-user me-2"></i>Teacher</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-book text-white"></i>
                                            </div>
                                            <?= htmlspecialchars($subject['subject_name']) ?>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($subject['subject_code']) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= $subject['class_name'] ?> - <?= $subject['section'] ?></span></td>
                                    <td><?= $subject['teacher_name'] ?: 'Not Assigned' ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success" onclick="editSubject(<?= $subject['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSubject(<?= $subject['id'] ?>)">
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

<div class="modal fade" id="addSubjectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/subjects.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?> - <?= $class['section'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher (Optional)</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">No Teacher Assigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubjectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/subjects.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="subject_id" id="edit_subject_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" id="edit_class_id" class="form-select" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?> - <?= $class['section'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher (Optional)</label>
                        <select name="teacher_id" id="edit_teacher_id" class="form-select">
                            <option value="">No Teacher Assigned</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSubject(id) {
    fetch('../controllers/subjects.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_subject_id').value = data.data.id;
                document.getElementById('edit_subject_name').value = data.data.subject_name;
                document.getElementById('edit_subject_code').value = data.data.subject_code;
                document.getElementById('edit_class_id').value = data.data.class_id;
                document.getElementById('edit_teacher_id').value = data.data.teacher_id || '';
                new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
            }
        });
}

function deleteSubject(id) {
    if (confirm('Are you sure you want to delete this subject?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/subjects.php';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

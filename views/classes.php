<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$classes = $db->query("
    SELECT c.*, COALESCE(NULLIF(u.name, ''), u.email) as teacher_name,
    (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count
    FROM classes c 
    LEFT JOIN users u ON c.class_teacher_id = u.id 
    ORDER BY c.class_name, c.section
")->fetchAll();

$teachers = $db->query("SELECT id, COALESCE(NULLIF(name, ''), email) as name, email FROM users WHERE role = 'teacher'")->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chalkboard me-2"></i>Classes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="fas fa-plus me-2"></i>Add Class
                    </button>
                </div>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-chalkboard me-3"></i>Class Management</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Classes</h5>
                            <h2 class="mb-0"><?= count($classes) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Students</h5>
                            <h2 class="mb-0"><?= array_sum(array_column($classes, 'student_count')) ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Average Size</h5>
                            <h2 class="mb-0"><?= count($classes) > 0 ? round(array_sum(array_column($classes, 'student_count')) / count($classes), 1) : 0 ?></h2>
                        </div>
                        <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Teachers</h5>
                            <h2 class="mb-0"><?= count($teachers) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Classes Directory</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-chalkboard me-2"></i>Class</th>
                                    <th><i class="fas fa-user me-2"></i>Teacher</th>
                                    <th><i class="fas fa-users me-2"></i>Students</th>
                                    <th><i class="fas fa-chart-bar me-2"></i>Capacity</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($class['class_name']) ?></h6>
                                            <small class="text-muted">Section <?= htmlspecialchars($class['section']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <?= $class['teacher_name'] ?: 'Not Assigned' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $class['student_count'] ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?= $class['student_count'] ?>/<?= $class['capacity'] ?></span>
                                            <div class="progress" style="width: 60px; height: 6px;">
                                                <div class="progress-bar" style="width: <?= ($class['student_count'] / $class['capacity']) * 100 ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $class['status'] == 'active' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($class['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-warning" onclick="assignTeacher(<?= $class['id'] ?>)">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="editClass(<?= $class['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?= $class['id'] ?>)">
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

<div class="modal fade" id="addClassModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/classes.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" name="class_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class Teacher (Optional)</label>
                        <select name="class_teacher_id" class="form-select">
                            <option value="">No Teacher Assigned</option>
                            <?php if (count($teachers) > 0): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No teachers available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="40" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assignTeacherModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Assign Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/classes.php" method="POST">
                <input type="hidden" name="action" value="assign_teacher">
                <input type="hidden" name="class_id" id="assign_class_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="" disabled selected>Choose a teacher...</option>
                            <?php if (count($teachers) > 0): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No teachers available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editClassModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/classes.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="class_id" id="edit_class_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Class Name</label>
                        <input type="text" name="class_name" id="edit_class_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" id="edit_section" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Class Teacher (Optional)</label>
                        <select name="class_teacher_id" id="edit_class_teacher_id" class="form-select">
                            <option value="">No Teacher Assigned</option>
                            <?php if (count($teachers) > 0): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No teachers available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
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
function assignTeacher(classId) {
    document.getElementById('assign_class_id').value = classId;
    new bootstrap.Modal(document.getElementById('assignTeacherModal')).show();
}

function editClass(id) {
    fetch('../controllers/classes.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_class_id').value = data.data.id;
                document.getElementById('edit_class_name').value = data.data.class_name;
                document.getElementById('edit_section').value = data.data.section;
                document.getElementById('edit_class_teacher_id').value = data.data.class_teacher_id || '';
                document.getElementById('edit_capacity').value = data.data.capacity;
                new bootstrap.Modal(document.getElementById('editClassModal')).show();
            }
        });
}

function deleteClass(id) {
    if (confirm('Are you sure you want to delete this class?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/classes.php';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

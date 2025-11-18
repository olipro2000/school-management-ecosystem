<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$teachers = $db->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM classes WHERE class_teacher_id = u.id) as class_count,
    (SELECT COUNT(*) FROM subjects WHERE teacher_id = u.id) as subject_count
    FROM users u 
    WHERE u.role = 'teacher' 
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chalkboard-teacher me-2"></i>Teachers</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                        <i class="fas fa-plus me-2"></i>Add Teacher
                    </button>
                </div>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-chalkboard-teacher me-3"></i>Teacher Management</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Teachers</h5>
                            <h2 class="mb-0"><?= count($teachers) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Active</h5>
                            <h2 class="mb-0"><?= count(array_filter($teachers, fn($t) => $t['status'] == 'active')) ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Classes</h5>
                            <h2 class="mb-0"><?= array_sum(array_column($teachers, 'class_count')) ?></h2>
                        </div>
                        <i class="fas fa-chalkboard fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Subjects</h5>
                            <h2 class="mb-0"><?= array_sum(array_column($teachers, 'subject_count')) ?></h2>
                        </div>
                        <i class="fas fa-book fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Teachers Directory</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Teacher</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-phone me-2"></i>Phone</th>
                                    <th><i class="fas fa-chalkboard me-2"></i>Classes</th>
                                    <th><i class="fas fa-book me-2"></i>Subjects</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <?= htmlspecialchars($teacher['name'] ?: $teacher['email']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td><?= htmlspecialchars($teacher['phone'] ?: 'N/A') ?></td>
                                    <td><span class="badge bg-primary"><?= $teacher['class_count'] ?></span></td>
                                    <td><span class="badge bg-info"><?= $teacher['subject_count'] ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $teacher['status'] == 'active' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($teacher['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success" onclick="editTeacher(<?= $teacher['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTeacher(<?= $teacher['id'] ?>)">
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

<div class="modal fade" id="addTeacherModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/teachers.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Teacher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editTeacherModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/teachers.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="teacher_id" id="edit_teacher_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="edit_gender" class="form-select" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
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
function editTeacher(id) {
    fetch('../controllers/teachers.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_teacher_id').value = data.data.id;
                document.getElementById('edit_name').value = data.data.name || '';
                document.getElementById('edit_email').value = data.data.email;
                document.getElementById('edit_phone').value = data.data.phone || '';
                document.getElementById('edit_gender').value = data.data.gender;
                document.getElementById('edit_address').value = data.data.address || '';
                new bootstrap.Modal(document.getElementById('editTeacherModal')).show();
            }
        });
}

function deleteTeacher(id) {
    if (confirm('Are you sure you want to delete this teacher?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/teachers.php';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

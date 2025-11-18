<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

// Get students with class info
$students = $db->query("
    SELECT s.*, u.name, u.email, u.phone, u.gender, u.status, c.class_name, c.section 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    ORDER BY s.created_at DESC
")->fetchAll();

// Get classes for dropdown
$classes = $db->query("SELECT * FROM classes WHERE status = 'active'")->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-user-graduate me-2"></i>Students</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-plus me-2"></i>Add Student
                    </button>
                </div>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-user-graduate me-3"></i>Student Management</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Students</h5>
                            <h2 class="mb-0"><?= count($students) ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Active Students</h5>
                            <h2 class="mb-0"><?= count(array_filter($students, fn($s) => $s['status'] == 'active')) ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">New This Month</h5>
                            <h2 class="mb-0"><?= count(array_filter($students, fn($s) => date('Y-m', strtotime($s['created_at'])) == date('Y-m'))) ?></h2>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Classes</h5>
                            <h2 class="mb-0"><?= count($classes) ?></h2>
                        </div>
                        <i class="fas fa-school fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Students Directory</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Student</th>
                                    <th><i class="fas fa-id-card me-2"></i>Student ID</th>
                                    <th><i class="fas fa-school me-2"></i>Class</th>
                                    <th><i class="fas fa-envelope me-2"></i>Contact</th>
                                    <th><i class="fas fa-calendar me-2"></i>Admission</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($student['name']) ?></h6>
                                                <small class="text-muted"><?= ucfirst($student['gender']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $student['student_id'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($student['class_name']): ?>
                                            <span class="badge bg-secondary"><?= $student['class_name'] ?> - <?= $student['section'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <small class="d-block"><?= htmlspecialchars($student['email']) ?></small>
                                            <small class="text-muted"><?= htmlspecialchars($student['phone']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($student['admission_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $student['status'] == 'active' ? 'success' : ($student['status'] == 'graduated' ? 'info' : 'warning') ?>">
                                            <?= ucfirst($student['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-success" onclick="editStudent(<?= $student['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(<?= $student['id'] ?>)">
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

<div class="modal fade" id="addStudentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/students.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student ID</label>
                            <input type="text" class="form-control" value="Auto-generated" readonly style="background: #e9ecef;">
                            <small class="text-muted">Format: YYMM0001 (e.g., 25090001 for Sept 2025)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?> - <?= $class['section'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Admission Date</label>
                            <input type="date" name="admission_date" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/students.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="edit_gender" class="form-select" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" id="edit_class_id" class="form-select" required>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?> - <?= $class['section'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Blood Group</label>
                            <input type="text" name="blood_group" id="edit_blood_group" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">New Password (Leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                        </div>
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
function editStudent(id) {
    fetch('../controllers/students.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_student_id').value = data.data.id;
                document.getElementById('edit_name').value = data.data.name;
                document.getElementById('edit_email').value = data.data.email;
                document.getElementById('edit_phone').value = data.data.phone || '';
                document.getElementById('edit_gender').value = data.data.gender;
                document.getElementById('edit_class_id').value = data.data.class_id;
                document.getElementById('edit_blood_group').value = data.data.blood_group || '';
                document.getElementById('edit_date_of_birth').value = data.data.date_of_birth || '';
                document.getElementById('edit_address').value = data.data.address || '';
                new bootstrap.Modal(document.getElementById('editStudentModal')).show();
            }
        });
}

function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/students.php';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$parents = $db->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM parents WHERE user_id = u.id) as children_count
    FROM users u 
    WHERE u.role = 'parent' 
    ORDER BY u.created_at DESC
")->fetchAll();

$students = $db->query("SELECT s.id, u.name, s.student_id FROM students s JOIN users u ON s.user_id = u.id WHERE u.status = 'active'")->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-users me-2"></i>Parents</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                        <i class="fas fa-plus me-2"></i>Add Parent
                    </button>
                </div>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-users me-3"></i>Parent Management</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Total Parents</h5>
                            <h2 class="mb-0"><?= count($parents) ?></h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Active</h5>
                            <h2 class="mb-0"><?= count(array_filter($parents, fn($p) => $p['status'] == 'active')) ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Students</h5>
                            <h2 class="mb-0"><?= count($students) ?></h2>
                        </div>
                        <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Linked</h5>
                            <h2 class="mb-0"><?= array_sum(array_column($parents, 'children_count')) ?></h2>
                        </div>
                        <i class="fas fa-link fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Parents Directory</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Parent</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-phone me-2"></i>Phone</th>
                                    <th><i class="fas fa-child me-2"></i>Children</th>
                                    <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parents as $parent): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <?= htmlspecialchars($parent['name'] ?: $parent['email']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($parent['email']) ?></td>
                                    <td><?= htmlspecialchars($parent['phone'] ?: 'N/A') ?></td>
                                    <td><span class="badge bg-primary"><?= $parent['children_count'] ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $parent['status'] == 'active' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($parent['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="linkChild(<?= $parent['id'] ?>)">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="editParent(<?= $parent['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteParent(<?= $parent['id'] ?>)">
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

<div class="modal fade" id="addParentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Parent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/parents.php" method="POST">
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Parent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editParentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Parent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/parents.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="parent_id" id="edit_parent_id">
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
function editParent(id) {
    fetch('../controllers/parents.php?action=get&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_parent_id').value = data.data.id;
                document.getElementById('edit_name').value = data.data.name || '';
                document.getElementById('edit_email').value = data.data.email;
                document.getElementById('edit_phone').value = data.data.phone || '';
                document.getElementById('edit_gender').value = data.data.gender;
                document.getElementById('edit_address').value = data.data.address || '';
                new bootstrap.Modal(document.getElementById('editParentModal')).show();
            }
        });
}

function deleteParent(id) {
    if (confirm('Are you sure you want to delete this parent?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controllers/parents.php';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function linkChild(parentId) {
    document.getElementById('link_parent_id').value = parentId;
    new bootstrap.Modal(document.getElementById('linkChildModal')).show();
}
</script>

<div class="modal fade" id="linkChildModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-link me-2"></i>Link Child to Parent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/parents.php" method="POST">
                <input type="hidden" name="action" value="link_child">
                <input type="hidden" name="parent_id" id="link_parent_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Choose Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name'] ?: $student['student_id']) ?> (<?= $student['student_id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Relationship</label>
                        <select name="relationship" class="form-select" required>
                            <option value="">Select Relationship</option>
                            <option value="father">Father</option>
                            <option value="mother">Mother</option>
                            <option value="guardian">Guardian</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-link me-2"></i>Link Child</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

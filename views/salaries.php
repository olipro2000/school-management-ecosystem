<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$salaries = $db->query("SELECT s.*, u.name, u.email FROM salaries s JOIN users u ON s.user_id = u.id ORDER BY s.salary_month DESC")->fetchAll();
$staff = $db->query("SELECT id, name, email FROM users WHERE role IN ('teacher', 'accountant', 'librarian') AND status = 'active'")->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-wallet me-2"></i>Salary Management</h1>
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
                <i class="fas fa-plus me-2"></i>Process Salary
            </button>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Month</th>
                                <th>Basic Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salaries as $salary): ?>
                            <tr>
                                <td><?= htmlspecialchars($salary['name']) ?></td>
                                <td><?= date('F Y', strtotime($salary['salary_month'] . '-01')) ?></td>
                                <td>$<?= number_format($salary['basic_salary'], 2) ?></td>
                                <td>$<?= number_format($salary['allowances'], 2) ?></td>
                                <td>$<?= number_format($salary['deductions'], 2) ?></td>
                                <td class="fw-bold">$<?= number_format($salary['net_salary'], 2) ?></td>
                                <td><span class="badge bg-<?= $salary['status'] == 'paid' ? 'success' : 'warning' ?>"><?= ucfirst($salary['status']) ?></span></td>
                                <td><?= $salary['payment_date'] ? date('M d, Y', strtotime($salary['payment_date'])) : 'N/A' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addSalaryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-wallet me-2"></i>Process Salary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../controllers/salaries.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Staff Member</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="salary_month" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Basic Salary</label>
                        <input type="number" name="basic_salary" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Allowances</label>
                        <input type="number" name="allowances" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deductions</label>
                        <input type="number" name="deductions" class="form-control" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Process</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

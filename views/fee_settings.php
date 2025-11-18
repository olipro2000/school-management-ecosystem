<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$db = getDB();

if ($_POST && isset($_POST['add_fee'])) {
    $stmt = $db->prepare("INSERT INTO fee_structure (category, amount, academic_year) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?");
    $stmt->execute([$_POST['category'], $_POST['amount'], $_POST['academic_year'], $_POST['amount']]);
    header('Location: fee_settings.php');
    exit;
}

require_once '../includes/header.php';

$fees = $db->query("SELECT * FROM fee_structure ORDER BY academic_year DESC, category")->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-cog me-2"></i>Fee Settings</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
                <i class="fas fa-plus me-2"></i>Set Fee
            </button>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-money-bill me-2"></i>Fee Structure</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount (RWF)</th>
                                <th>Academic Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fees as $fee): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= ucfirst(str_replace('_', ' ', $fee['category'])) ?></span></td>
                                <td class="fw-bold">RWF <?= number_format($fee['amount'], 0) ?></td>
                                <td><?= htmlspecialchars($fee['academic_year']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addFeeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-money-bill me-2"></i>Set Fee Amount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="nursery">Nursery</option>
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="cambridge">Cambridge</option>
                            <option value="special_needs">Special Needs</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (RWF)</label>
                        <input type="number" name="amount" class="form-control" step="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="2024-2025" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_fee" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

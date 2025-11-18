<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

// Get fee summary for parent's children
$stmt = $db->prepare("
    SELECT 
        s.id as student_id,
        s.student_id as roll_number,
        u.name as student_name,
        c.class_name,
        c.section,
        COALESCE(c.category, 'primary') as category,
        COALESCE((SELECT amount FROM fee_structure WHERE category = COALESCE(c.category, 'primary') AND academic_year = '2024-2025' LIMIT 1), 0) as total_fees,
        COALESCE((SELECT SUM(amount) FROM payments WHERE student_id = s.id AND status = 'verified'), 0) as total_paid
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN parents pr ON s.parent_id = pr.id
    JOIN classes c ON s.class_id = c.id
    WHERE pr.user_id = ? AND s.status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$students = $stmt->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Fee Balance</h1>
                <a href="submit_payment.php" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Submit Payment
                </a>
            </div>
            
            <?php if (empty($students)): ?>
                <div class="alert alert-info">
                    <h4>No Students Found</h4>
                    <p>No student records found for your account.</p>
                </div>
            <?php else: ?>
                <?php foreach ($students as $student): 
                    $balance = $student['total_fees'] - $student['total_paid'];
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><?= htmlspecialchars($student['student_name']) ?> (<?= $student['roll_number'] ?>)</h5>
                        <small class="text-muted"><?= htmlspecialchars($student['class_name'] . ' ' . $student['section']) ?> - <?= ucfirst(str_replace('_', ' ', $student['category'])) ?></small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-2">Total Fees</h6>
                                    <h3 class="mb-0">RWF <?= number_format($student['total_fees'], 0) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                    <h6 class="text-muted mb-2">Paid</h6>
                                    <h3 class="mb-0 text-success">RWF <?= number_format($student['total_paid'], 0) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-<?= $balance > 0 ? 'danger' : 'success' ?> bg-opacity-10 rounded">
                                    <h6 class="text-muted mb-2">Balance</h6>
                                    <h3 class="mb-0 text-<?= $balance > 0 ? 'danger' : 'success' ?>">RWF <?= number_format($balance, 0) ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                    <h6 class="text-muted mb-2">Status</h6>
                                    <h3 class="mb-0">
                                        <?php if ($balance <= 0): ?>
                                            <span class="badge bg-success fs-6">PAID</span>
                                        <?php elseif ($student['total_paid'] > 0): ?>
                                            <span class="badge bg-warning fs-6">PARTIAL</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger fs-6">UNPAID</span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Payment Instructions</h5>
                    <ul class="mb-0">
                        <li>Make payment via bank transfer or cash deposit</li>
                        <li>Take a screenshot of the payment receipt</li>
                        <li>Upload the receipt using the "Submit Payment" button</li>
                        <li>Wait for admin verification (usually 1-2 business days)</li>
                    </ul>
                </div>
            <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
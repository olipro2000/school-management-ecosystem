<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'accountant'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../models/Payment.php';
require_once '../includes/header.php';

$db = getDB();
$payment = new Payment($db);

if ($_POST && isset($_POST['verify_payment'])) {
    $payment_id = $_POST['payment_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    
    if ($payment->verifyPayment($payment_id, $status, $_SESSION['user_id'], $remarks)) {
        $success = "Payment " . $status . " successfully!";
    } else {
        $error = "Failed to update payment status.";
    }
}

$search = $_GET['search'] ?? '';
$class_filter = $_GET['class_id'] ?? '';
$sort = $_GET['sort'] ?? 'student';

$query = "SELECT 
    s.id as student_id, 
    s.student_id as roll_number, 
    u.name as student_name, 
    c.id as class_id, 
    c.class_name, 
    c.section, 
    COALESCE(c.category, 'primary') as category,
    COALESCE((SELECT SUM(amount) FROM payments WHERE student_id = s.id AND status = 'verified'), 0) as total_paid,
    COALESCE((SELECT amount FROM fee_structure WHERE category = COALESCE(c.category, 'primary') AND academic_year = '2024-2025' LIMIT 1), 0) as total_fees
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
    WHERE s.status = 'active'";

if ($search) {
    $query .= " AND (u.name LIKE '%$search%' OR s.student_id LIKE '%$search%')";
}

if ($class_filter) {
    $query .= " AND c.id = $class_filter";
}


$query .= " ORDER BY " . ($sort == 'student' ? 'u.name' : ($sort == 'class' ? 'c.class_name' : 'total_balance')) . " DESC";

$students = $db->query($query)->fetchAll();
$classes = $db->query("SELECT * FROM classes WHERE status = 'active'")->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-money-bill me-2"></i>School Fees</h1>
            </div>
            
            <div class="page-header">
                <h1><i class="fas fa-money-bill-wave me-3"></i>Fee Payment Verification</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Student Fee Records</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search student name or ID..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="class_id" class="form-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="sort" class="form-select">
                                <option value="student" <?= $sort == 'student' ? 'selected' : '' ?>>Sort by Student</option>
                                <option value="class" <?= $sort == 'class' ? 'selected' : '' ?>>Sort by Class</option>
                                <option value="balance" <?= $sort == 'balance' ? 'selected' : '' ?>>Sort by Balance</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Total Fees</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $balance = $student['total_fees'] - $student['total_paid'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($student['student_name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($student['roll_number']) ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($student['class_name'] . ' ' . $student['section']) ?></span></td>
                            <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $student['category'])) ?></span></td>
                            <td class="fw-bold">RWF <?= number_format($student['total_fees'], 0) ?></td>
                            <td class="text-success fw-bold">RWF <?= number_format($student['total_paid'], 0) ?></td>
                            <td class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?> fw-bold">
                                RWF <?= number_format($balance, 0) ?>
                                <?php if ($balance <= 0): ?>
                                    <span class="badge bg-success ms-2">PAID</span>
                                <?php elseif ($student['total_paid'] > 0): ?>
                                    <span class="badge bg-warning ms-2">PARTIAL</span>
                                <?php else: ?>
                                    <span class="badge bg-danger ms-2">UNPAID</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="student_payments.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
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

<?php include '../includes/footer.php'; ?>
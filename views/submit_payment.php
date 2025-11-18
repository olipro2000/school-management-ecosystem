<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'parent') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../models/Payment.php';
require_once '../includes/header.php';

$db = getDB();
$payment = new Payment($db);

// Get parent's children
$stmt = $db->prepare("SELECT s.id, s.student_id, u.name FROM students s 
                      JOIN users u ON s.user_id = u.id 
                      JOIN parents p ON s.parent_id = p.id 
                      WHERE p.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$children = $stmt->fetchAll();

if ($_POST) {
    $upload_dir = '../uploads/receipts/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . $_FILES['receipt']['name'];
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $file_path)) {
        $data = [
            'student_id' => $_POST['student_id'],
            'fee_type' => $_POST['fee_type'],
            'amount' => $_POST['amount'],
            'payment_date' => $_POST['payment_date'],
            'receipt_screenshot' => $file_name,
            'bank_reference' => $_POST['bank_reference'],
            'remarks' => $_POST['remarks']
        ];
        
        if ($payment->submitPayment($data)) {
            $success = "Payment submitted successfully! Awaiting verification.";
        } else {
            $error = "Failed to submit payment.";
        }
    } else {
        $error = "Failed to upload receipt.";
    }
}
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="page-header">
                <h1><i class="fas fa-upload me-3"></i>Submit Payment Receipt</h1>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">Student</label>
                                    <select name="student_id" class="form-select" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ($children as $child): ?>
                                            <option value="<?= $child['id'] ?>"><?= htmlspecialchars($child['name']) ?> (<?= $child['student_id'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Fee Type</label>
                                    <select name="fee_type" class="form-select" required>
                                        <option value="tuition">Tuition Fees</option>
                                        <option value="transport">Transport Fees</option>
                                        <option value="library">Library Fees</option>
                                        <option value="exam">Exam Fees</option>
                                        <option value="activity">Activity Fees</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Amount (RWF)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">RWF</span>
                                        <input type="number" name="amount" class="form-control" step="0.01" min="0" placeholder="Enter amount" required>
                                    </div>
                                    <small class="text-muted">Enter any amount you wish to pay</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" name="payment_date" class="form-control" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Receipt/Bank Slip <span class="text-danger">*</span></label>
                                    <input type="file" name="receipt" class="form-control" accept="image/*" required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Upload a clear screenshot of your payment receipt or bank slip (JPG, PNG)
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bank Reference (Optional)</label>
                                    <input type="text" name="bank_reference" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Remarks (Optional)</label>
                                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Payment Receipt
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Payment Instructions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <span class="text-white fw-bold small">1</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Make Payment</h6>
                                    <p class="small text-muted mb-0">Pay via bank transfer, online banking, or cash deposit</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <span class="text-white fw-bold small">2</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Take Screenshot</h6>
                                    <p class="small text-muted mb-0">Capture receipt or bank confirmation</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <span class="text-white fw-bold small">3</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Upload Here</h6>
                                    <p class="small text-muted mb-0">Submit through this form</p>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-start">
                                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                    <span class="text-white fw-bold small">4</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Wait for Verification</h6>
                                    <p class="small text-muted mb-0">Admin will verify within 1-2 business days</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <strong>Secure:</strong> Your payment information is protected and only visible to authorized staff.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
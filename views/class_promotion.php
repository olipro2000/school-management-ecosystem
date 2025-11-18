<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$db = getDB();
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Get current academic year
$stmt = $db->query("SELECT year_name FROM academic_years WHERE is_current = 1");
$current_year = $stmt->fetch()['year_name'] ?? '';

// Get all classes
$classes = $db->query("SELECT id, class_name, section FROM classes WHERE status = 'active' ORDER BY class_name, section")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Promotion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/advanced.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Class Promotion</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Promote Students</h5>
                        <form method="POST" action="../controllers/promote_students.php">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">From Class</label>
                                    <select name="from_class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">To Class</label>
                                    <select name="to_class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Academic Year</label>
                                    <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($current_year) ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Pass Mark (%)</label>
                                    <input type="number" name="pass_mark" class="form-control" placeholder="Enter pass mark" min="0" max="100" required>
                                    <small class="text-muted">Students with average ≥ this mark will be promoted</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-up"></i> Promote Students
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Promotion History</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>From Class</th>
                                        <th>To Class</th>
                                        <th>Academic Year</th>
                                        <th>Status</th>
                                        <th>Average</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $db->query("
                                        SELECT sp.*, u.name as student_name, 
                                        c1.class_name as from_class, c1.section as from_section,
                                        c2.class_name as to_class, c2.section as to_section
                                        FROM student_promotions sp
                                        JOIN students s ON sp.student_id = s.id
                                        JOIN users u ON s.user_id = u.id
                                        JOIN classes c1 ON sp.from_class_id = c1.id
                                        LEFT JOIN classes c2 ON sp.to_class_id = c2.id
                                        ORDER BY sp.promoted_at DESC
                                        LIMIT 50
                                    ");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                                        <td><?= htmlspecialchars($row['from_class'] . ' - ' . $row['from_section']) ?></td>
                                        <td><?= $row['to_class'] ? htmlspecialchars($row['to_class'] . ' - ' . $row['to_section']) : '-' ?></td>
                                        <td><?= htmlspecialchars($row['academic_year']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] == 'promoted' ? 'success' : 'warning' ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($row['average_marks'], 2) ?>%</td>
                                        <td><?= date('d M Y', strtotime($row['promoted_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

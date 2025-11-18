<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

$classes = $db->prepare("
    SELECT c.*, 
    (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count
    FROM classes c 
    WHERE c.class_teacher_id = ?
    ORDER BY c.class_name, c.section
");
$classes->execute([$_SESSION['user_id']]);
$classes = $classes->fetchAll();

$subjects = $db->prepare("
    SELECT s.*, c.class_name, c.section 
    FROM subjects s
    JOIN classes c ON s.class_id = c.id
    WHERE s.teacher_id = ?
    ORDER BY c.class_name, s.subject_name
");
$subjects->execute([$_SESSION['user_id']]);
$subjects = $subjects->fetchAll();
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chalkboard-teacher me-2"></i>My Classes</h1>
            </div>
            
            <?php include '../includes/alerts.php'; ?>
            
            <div class="page-header">
                <h1><i class="fas fa-chalkboard-teacher me-3"></i>My Teaching Schedule</h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">My Classes</h5>
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
                            <h5 class="mb-1">Subjects</h5>
                            <h2 class="mb-0"><?= count($subjects) ?></h2>
                        </div>
                        <i class="fas fa-book fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1">Avg Class Size</h5>
                            <h2 class="mb-0"><?= count($classes) > 0 ? round(array_sum(array_column($classes, 'student_count')) / count($classes), 1) : 0 ?></h2>
                        </div>
                        <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chalkboard me-2"></i>My Classes</h5>
                </div>
                <div class="card-body">
                    <?php if (count($classes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-chalkboard me-2"></i>Class</th>
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
                                            <a href="attendance.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-calendar-check"></i>
                                            </a>
                                            <a href="grades.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chalkboard fa-3x text-muted mb-3"></i>
                        <h5>No Classes Assigned</h5>
                        <p class="text-muted">You haven't been assigned to any classes yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>My Subjects</h5>
                </div>
                <div class="card-body">
                    <?php if (count($subjects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book me-2"></i>Subject</th>
                                    <th><i class="fas fa-chalkboard me-2"></i>Class</th>
                                    <th><i class="fas fa-code me-2"></i>Subject Code</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <i class="fas fa-book text-white"></i>
                                            </div>
                                            <?= htmlspecialchars($subject['subject_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $subject['class_name'] ?> - <?= $subject['section'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $subject['subject_code'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="grades.php?subject_id=<?= $subject['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-graduation-cap"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h5>No Subjects Assigned</h5>
                        <p class="text-muted">You haven't been assigned to teach any subjects yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

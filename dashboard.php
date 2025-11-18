<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/header.php';

$role = $_SESSION['user_role'];
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2" style="color: var(--dark); font-weight: 700;">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
            </div>
            
            <?php if ($role == 'admin'): ?>
                <div class="page-header">
                    <h1><i class="fas fa-tachometer-alt me-3"></i>Dashboard Overview</h1>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Total Students</h5>
                                <?php
                                $db = getDB();
                                $stmt = $db->query("SELECT COUNT(*) as count FROM students");
                                $result = $stmt->fetch();
                                echo '<h2 class="mb-0">' . ($result ? $result['count'] : 0) . '</h2>';
                                ?>
                            </div>
                            <i class="fas fa-user-graduate fa-2x opacity-75"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Total Teachers</h5>
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
                                $result = $stmt->fetch();
                                echo '<h2 class="mb-0">' . ($result ? $result['count'] : 0) . '</h2>';
                                ?>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Pending Payments</h5>
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM payments");
                                $result = $stmt->fetch();
                                echo '<h2 class="mb-0">' . ($result ? $result['count'] : 0) . '</h2>';
                                ?>
                            </div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-1">Total Classes</h5>
                                <?php
                                $stmt = $db->query("SELECT COUNT(*) as count FROM classes");
                                $result = $stmt->fetch();
                                echo '<h2 class="mb-0">' . ($result ? $result['count'] : 0) . '</h2>';
                                ?>
                            </div>
                            <i class="fas fa-school fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            <?php elseif ($role == 'parent'): ?>
                <div class="page-header">
                    <h1><i class="fas fa-child me-3"></i>My Children</h1>
                </div>
                
                <?php
                $db = getDB();
                $stmt = $db->prepare("SELECT s.id, s.student_id, u.name, u.email, u.phone, c.class_name, c.section FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id JOIN parents p ON s.parent_id = p.id WHERE p.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $children = $stmt->fetchAll();
                
                if ($children):
                    foreach ($children as $child):
                        $stmt = $db->prepare("SELECT s.subject_name, g.cat1_marks, g.cat2_marks, g.exam_marks, g.total_marks, g.grade, g.term FROM grades g JOIN subjects s ON g.subject_id = s.id WHERE g.student_id = ? ORDER BY g.term DESC, s.subject_name");
                        $stmt->execute([$child['id']]);
                        $grades = $stmt->fetchAll();
                ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i><?= htmlspecialchars($child['name']) ?> - <?= htmlspecialchars($child['class_name']) ?> <?= htmlspecialchars($child['section']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($child['student_id']) ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($child['email']) ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($child['phone'] ?: 'N/A') ?></p>
                            </div>
                        </div>
                        
                        <h6 class="mt-4 mb-3"><i class="fas fa-chart-line me-2"></i>Grades</h6>
                        <?php if ($grades): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>CAT 1</th>
                                        <th>CAT 2</th>
                                        <th>Exam</th>
                                        <th>Total</th>
                                        <th>Grade</th>
                                        <th>Term</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($grade['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($grade['cat1_marks']) ?></td>
                                        <td><?= htmlspecialchars($grade['cat2_marks']) ?></td>
                                        <td><?= htmlspecialchars($grade['exam_marks']) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($grade['total_marks']) ?></td>
                                        <td><span class="badge bg-success"><?= htmlspecialchars($grade['grade'] ?: 'N/A') ?></span></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($grade['term']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No grades available yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                    endforeach;
                else:
                ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                        <h5>No Children Linked</h5>
                        <p class="text-muted">Please contact the administrator to link your children to your account.</p>
                    </div>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="page-header">
                    <h1><i class="fas fa-home me-3"></i>Your Dashboard</h1>
                </div>
                
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-rocket fa-4x text-primary mb-4"></i>
                        <h4>Ready to get started?</h4>
                        <p class="text-muted mb-4">Use the navigation menu to access your modules and manage your tasks efficiently.</p>
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-center gap-3 flex-wrap">
                                    <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                                        <a href="views/my_classes.php" class="btn btn-primary"><i class="fas fa-chalkboard me-2"></i>My Classes</a>
                                        <a href="views/attendance.php" class="btn btn-outline-primary"><i class="fas fa-calendar-check me-2"></i>Attendance</a>
                                    <?php elseif ($_SESSION['user_role'] == 'librarian'): ?>
                                        <a href="views/books.php" class="btn btn-primary"><i class="fas fa-book me-2"></i>Books</a>
                                        <a href="views/library_records.php" class="btn btn-outline-primary"><i class="fas fa-list me-2"></i>Records</a>
                                    <?php elseif ($_SESSION['user_role'] == 'accountant'): ?>
                                        <a href="views/payments.php" class="btn btn-primary"><i class="fas fa-money-bill me-2"></i>Payments</a>
                                    <?php endif; ?>
                                    <a href="views/messages.php" class="btn btn-outline-secondary"><i class="fas fa-envelope me-2"></i>Messages</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
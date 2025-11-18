<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'teacher'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../includes/header.php';

$db = getDB();

// Get classes for teacher or all for admin
if ($_SESSION['user_role'] == 'teacher') {
    $classes = $db->prepare("SELECT DISTINCT c.* FROM classes c JOIN subjects s ON c.id = s.class_id WHERE s.teacher_id = ?");
    $classes->execute([$_SESSION['user_id']]);
    $classes = $classes->fetchAll();
} else {
    $classes = $db->query("SELECT * FROM classes WHERE status = 'active'")->fetchAll();
}

$selectedClass = $_GET['class_id'] ?? ($classes[0]['id'] ?? null);
$selectedDate = $_GET['date'] ?? date('Y-m-d');

if ($_POST && isset($_POST['mark_attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        $stmt = $db->prepare("INSERT INTO attendance (user_id, date, status, role, marked_by) VALUES (?, ?, ?, 'student', ?) ON DUPLICATE KEY UPDATE status = ?, marked_by = ?");
        $stmt->execute([$student_id, $selectedDate, $status, $_SESSION['user_id'], $status, $_SESSION['user_id']]);
    }
    $success = "Attendance marked successfully!";
}

// Get students for selected class
$students = [];
if ($selectedClass) {
    $students = $db->prepare("
        SELECT s.*, u.name, u.email,
        (SELECT status FROM attendance WHERE user_id = u.id AND date = ? LIMIT 1) as attendance_status
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.class_id = ? AND s.status = 'active'
        ORDER BY u.name
    ");
    $students->execute([$selectedDate, $selectedClass]);
    $students = $students->fetchAll();
}

// Get attendance statistics
$stats = [];
if ($selectedClass) {
    $stats = $db->prepare("
        SELECT 
            COUNT(DISTINCT s.id) as total_students,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN attendance a ON u.id = a.user_id AND a.date = ?
        WHERE s.class_id = ? AND s.status = 'active'
    ");
    $stats->execute([$selectedDate, $selectedClass]);
    $stats = $stats->fetch();
}
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h1><i class="fas fa-calendar-check me-3"></i>Attendance Management</h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary morph-btn" onclick="generateReport()">
                            <i class="fas fa-chart-line me-2"></i>Reports
                        </button>
                        <button class="btn btn-outline-success morph-btn" onclick="exportAttendance()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Advanced Filters -->
            <div class="card advanced-card mb-4">
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Select Class</label>
                            <select name="class_id" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= $selectedClass == $class['id'] ? 'selected' : '' ?>>
                                        <?= $class['class_name'] ?> - <?= $class['section'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Select Date</label>
                            <input type="date" name="date" class="form-control" value="<?= $selectedDate ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary morph-btn w-100" onclick="markAllPresent()">
                                <i class="fas fa-check-double me-2"></i>Mark All Present
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Attendance Statistics -->
            <?php if ($stats): ?>
            <div class="stats-grid mb-4">
                <div class="advanced-card stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Total Students</h6>
                            <h2 class="mb-0"><?= $stats['total_students'] ?></h2>
                        </div>
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Present</h6>
                            <h2 class="mb-0"><?= $stats['present'] ?></h2>
                        </div>
                        <div class="progress-advanced mt-2">
                            <div class="progress-bar" style="width: <?= $stats['total_students'] > 0 ? ($stats['present'] / $stats['total_students']) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="advanced-card stat-card danger">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Absent</h6>
                            <h2 class="mb-0"><?= $stats['absent'] ?></h2>
                        </div>
                        <i class="fas fa-user-times fa-2x opacity-75"></i>
                    </div>
                </div>
                
                <div class="advanced-card stat-card warning">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1">Late</h6>
                            <h2 class="mb-0"><?= $stats['late'] ?></h2>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Attendance Form -->
            <?php if ($students): ?>
            <div class="card advanced-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Mark Attendance - <?= date('F d, Y', strtotime($selectedDate)) ?></h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkMark('present')">
                                <i class="fas fa-check me-1"></i>All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkMark('absent')">
                                <i class="fas fa-times me-1"></i>All Absent
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form method="POST" id="attendanceForm">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user me-2"></i>Student</th>
                                        <th><i class="fas fa-id-card me-2"></i>Student ID</th>
                                        <th><i class="fas fa-check-circle me-2"></i>Present</th>
                                        <th><i class="fas fa-times-circle me-2"></i>Absent</th>
                                        <th><i class="fas fa-clock me-2"></i>Late</th>
                                        <th><i class="fas fa-info-circle me-2"></i>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                    <tr class="attendance-row" data-student-id="<?= $student['user_id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($student['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $student['student_id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input attendance-radio" type="radio" 
                                                       name="attendance[<?= $student['user_id'] ?>]" value="present" 
                                                       <?= $student['attendance_status'] == 'present' ? 'checked' : '' ?>
                                                       onchange="updateAttendanceStatus(this)">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input attendance-radio" type="radio" 
                                                       name="attendance[<?= $student['user_id'] ?>]" value="absent" 
                                                       <?= $student['attendance_status'] == 'absent' ? 'checked' : '' ?>
                                                       onchange="updateAttendanceStatus(this)">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input attendance-radio" type="radio" 
                                                       name="attendance[<?= $student['user_id'] ?>]" value="late" 
                                                       <?= $student['attendance_status'] == 'late' ? 'checked' : '' ?>
                                                       onchange="updateAttendanceStatus(this)">
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge badge bg-secondary">Not Marked</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <?= count($students) ?> students in this class
                                </div>
                                <button type="submit" name="mark_attendance" class="btn btn-primary morph-btn">
                                    <i class="fas fa-save me-2"></i>Save Attendance
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card advanced-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h5>No Students Found</h5>
                    <p class="text-muted">Please select a class to mark attendance.</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function updateAttendanceStatus(radio) {
    const row = radio.closest('.attendance-row');
    const statusBadge = row.querySelector('.status-badge');
    const status = radio.value;
    
    // Update status badge
    statusBadge.className = `status-badge badge bg-${getStatusColor(status)}`;
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    // Add animation
    row.style.transform = 'scale(1.02)';
    setTimeout(() => {
        row.style.transform = 'scale(1)';
    }, 200);
    
    // Update statistics
    updateStats();
}

function getStatusColor(status) {
    const colors = {
        'present': 'success',
        'absent': 'danger',
        'late': 'warning'
    };
    return colors[status] || 'secondary';
}

function bulkMark(status) {
    const radios = document.querySelectorAll(`input[value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
        updateAttendanceStatus(radio);
    });
    
    window.advancedSMS.showAdvancedNotification(
        'Bulk Update', 
        `All students marked as ${status}`, 
        'success'
    );
}

function updateStats() {
    const present = document.querySelectorAll('input[value="present"]:checked').length;
    const absent = document.querySelectorAll('input[value="absent"]:checked').length;
    const late = document.querySelectorAll('input[value="late"]:checked').length;
    
    // Update stat cards if they exist
    const statCards = document.querySelectorAll('.stat-card h2');
    if (statCards.length >= 4) {
        window.advancedSMS.animateNumber(statCards[1], parseInt(statCards[1].textContent), present, 500);
        window.advancedSMS.animateNumber(statCards[2], parseInt(statCards[2].textContent), absent, 500);
        window.advancedSMS.animateNumber(statCards[3], parseInt(statCards[3].textContent), late, 500);
    }
}

function generateReport() {
    const classId = new URLSearchParams(window.location.search).get('class_id');
    const date = new URLSearchParams(window.location.search).get('date') || '<?= date('Y-m-d') ?>';
    window.open(`../api/attendance_report.php?class_id=${classId}&date=${date}`, '_blank');
}

function exportAttendance() {
    const classId = new URLSearchParams(window.location.search).get('class_id');
    const date = new URLSearchParams(window.location.search).get('date') || '<?= date('Y-m-d') ?>';
    window.location.href = `../api/export_attendance.php?class_id=${classId}&date=${date}`;
}

// Initialize attendance status on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.attendance-radio:checked').forEach(radio => {
        updateAttendanceStatus(radio);
    });
});
</script>

<style>
.attendance-row {
    transition: all 0.3s ease;
}

.attendance-row:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.status-badge {
    transition: all 0.3s ease;
}
</style>

<?php include '../includes/footer.php'; ?>
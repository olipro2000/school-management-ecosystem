<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$db = getDB();

if ($_POST && isset($_POST['create_announcement'])) {
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = '../uploads/announcements/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
            $attachment = $fileName;
        }
    }
    $audience = ($_SESSION['user_role'] == 'teacher') ? 'students' : $_POST['audience'];
    $stmt = $db->prepare("INSERT INTO announcements (title, content, audience, priority, attachment, published_by, publish_date, status) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'published')");
    $stmt->execute([$_POST['title'], $_POST['content'], $audience, $_POST['priority'], $attachment, $_SESSION['user_id']]);
    header('Location: announcements.php');
    exit;
}

require_once '../includes/header.php';

if (isset($_GET['view'])) {
    $db->prepare("INSERT IGNORE INTO announcement_views (announcement_id, user_id) VALUES (?, ?)")->execute([$_GET['view'], $_SESSION['user_id']]);
}

try {
    if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'teacher') {
        $announcements = $db->prepare("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.published_by = u.id WHERE a.status = 'published' ORDER BY a.publish_date DESC");
        $announcements->execute();
    } else {
        $announcements = $db->prepare("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.published_by = u.id WHERE a.status = 'published' AND (a.audience = 'all' OR a.audience = ?) AND (a.expiry_date IS NULL OR a.expiry_date >= CURDATE()) ORDER BY a.publish_date DESC");
        $announcements->execute([$_SESSION['user_role']]);
    }
    $announcements = $announcements->fetchAll();
} catch (Exception $e) {
    $announcements = [];
    $error = $e->getMessage();
}

if ($_SESSION['user_role'] == 'teacher') {
    $teacher_classes = $db->prepare("SELECT DISTINCT c.id, c.class_name, c.section FROM classes c JOIN subjects s ON c.id = s.class_id WHERE s.teacher_id = ?");
    $teacher_classes->execute([$_SESSION['user_id']]);
    $teacher_classes = $teacher_classes->fetchAll();
}
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-bullhorn me-2"></i>Announcements</h1>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'teacher'): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                <i class="fas fa-plus me-2"></i>New Announcement
            </button>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <?php if ($announcements): ?>
                <?php foreach ($announcements as $ann): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($ann['title']) ?></h5>
                            <span class="badge bg-<?= $ann['priority'] == 'urgent' ? 'danger' : ($ann['priority'] == 'high' ? 'warning' : 'info') ?>"><?= ucfirst($ann['priority']) ?></span>
                        </div>
                        <div class="card-body">
                            <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                            <?php if (!empty($ann['attachment'])): ?>
                                <div class="mt-2">
                                    <a href="../uploads/announcements/<?= htmlspecialchars($ann['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-paperclip"></i> View Attachment
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-muted small">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($ann['author']) ?> | 
                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($ann['publish_date'])) ?>
                            <?php if ($ann['expiry_date']): ?>
                                | <i class="fas fa-clock"></i> Expires: <?= date('M d, Y', strtotime($ann['expiry_date'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                            <h5>No Announcements</h5>
                            <p class="text-muted">There are no announcements at this time.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="modal fade" id="newAnnouncementModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                    <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                    <div class="mb-3">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name'] . ' ' . $class['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                    <div class="mb-3">
                        <label class="form-label">Audience</label>
                        <select name="audience" class="form-select" required>
                            <option value="all">All</option>
                            <option value="students">Students</option>
                            <option value="teachers">Teachers</option>
                            <option value="parents">Parents</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_announcement" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Publish
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

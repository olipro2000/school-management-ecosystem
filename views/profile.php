<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';

$db = getDB();

if ($_POST && isset($_POST['update_profile'])) {
    $profile_pic = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $profile_pic = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], '../uploads/profiles/' . $profile_pic);
        }
    }
    
    if ($profile_pic) {
        $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$profile_pic, $_SESSION['user_id']]);
    }
    
    if (isset($_POST['name'])) {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['address'], $_SESSION['user_id']]);
        $_SESSION['user_name'] = $_POST['name'];
    }
    
    header('Location: profile.php');
    exit;
}

if ($_POST && isset($_POST['change_password'])) {
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (password_verify($_POST['current_password'], $user['password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']])) {
                $_SESSION['success'] = "Password changed successfully!";
                header('Location: profile.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "New passwords do not match!";
            header('Location: profile.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect!";
        header('Location: profile.php');
        exit;
    }
}

require_once '../includes/header.php';

$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$activities = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$activities->execute([$_SESSION['user_id']]);
$activities = $activities->fetchAll();
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-user-circle me-2"></i>Profile</h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php unset($_SESSION['success']); endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php unset($_SESSION['error']); endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <form method="POST" enctype="multipart/form-data" id="profilePicForm">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="profile-avatar mb-4 position-relative d-inline-block">
                                    <div class="avatar-xl rounded-circle d-flex align-items-center justify-content-center mx-auto overflow-hidden" style="background: var(--primary); width: 120px; height: 120px;">
                                        <?php if (!empty($user['profile_picture']) && file_exists('../uploads/profiles/' . $user['profile_picture'])): ?>
                                            <img src="../uploads/profiles/<?= $user['profile_picture'] ?>?t=<?= time() ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                                        <?php else: ?>
                                            <span class="text-white fw-bold" style="font-size: 3rem;"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <label for="profilePicInput" class="btn btn-sm btn-primary rounded-circle position-absolute" style="bottom: 0; right: 0; cursor: pointer;">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="profilePicInput" name="profile_picture" accept="image/*" style="display: none;" onchange="this.form.submit()">
                                </div>
                            </form>
                            
                            <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                            <p class="text-muted mb-3"><?= ucfirst($user['role']) ?></p>
                            
                            <div class="profile-badges">
                                <span class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'teacher' ? 'success' : 'primary') ?> me-1">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                                <span class="badge bg-success">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Gender</label>
                                        <input type="text" class="form-control" value="<?= ucfirst($user['gender']) ?>" readonly>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

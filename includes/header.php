<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once (strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '') . 'config/db.php';
require_once (strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '') . 'utils/helpers.php';
require_once (strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '') . 'includes/alerts.php';

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M d', $time);
}

$db = getDB();
$user_data = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
$user_data->execute([$_SESSION['user_id']]);
$user = $user_data->fetch();
$profile_pic = $user['profile_picture'] ?? null;

$unread_messages = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$unread_messages->execute([$_SESSION['user_id']]);
$unread_count = $unread_messages->fetch()['count'] ?? 0;

$all_notifications = $db->prepare("SELECT id, type, title, content, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$all_notifications->execute([$_SESSION['user_id']]);
$notifications = $all_notifications->fetchAll();
$unread_notif_count = count(array_filter($notifications, fn($n) => !$n['is_read']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= ucfirst($_SESSION['user_role']) ?> - School Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/css/advanced.css" rel="stylesheet">
    <link href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/css/responsive.css" rel="stylesheet">
    <link href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/css/mobile-advanced.css" rel="stylesheet">
    <link href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/css/modern-ui.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <script src="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/js/notifications.js" defer></script>
    <script>
    function markNotifRead(id) {
        fetch('<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>api/mark_notification_read.php?id=' + id);
    }
    
    function markAllRead() {
        fetch('<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>api/mark_all_notifications_read.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to mark notifications as read');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error marking notifications as read');
            });
    }
    
    setInterval(() => {
        const now = new Date();
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const dateStr = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear() + ' ' + 
                       String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
        const elem = document.getElementById('currentDateTime');
        if (elem) elem.textContent = dateStr;
    }, 1000);
    
    // Real-time updates via SSE with auto-reconnect
    let eventSource;
    let reconnectDelay = 1000;
    
    function connectSSE() {
        const lastCheck = Math.floor(Date.now() / 1000);
        eventSource = new EventSource('<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>api/realtime_stream.php?last=' + lastCheck);
        
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.ping) return;
            
            if (data.unread_count !== undefined) {
                const badge = document.querySelector('.fa-bell')?.nextElementSibling;
                if (data.unread_count > 0) {
                    if (badge) {
                        badge.textContent = data.unread_count;
                    } else {
                        document.querySelector('.fa-bell')?.insertAdjacentHTML('afterend', 
                            `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">${data.unread_count}</span>`);
                    }
                } else if (badge) {
                    badge.remove();
                }
            }
            
            if (data.unread_messages !== undefined) {
                const msgBadge = document.querySelector('.fa-envelope')?.nextElementSibling;
                if (data.unread_messages > 0) {
                    if (msgBadge) {
                        msgBadge.textContent = data.unread_messages;
                    } else {
                        document.querySelector('.fa-envelope')?.insertAdjacentHTML('afterend', 
                            `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">${data.unread_messages}</span>`);
                    }
                } else if (msgBadge) {
                    msgBadge.remove();
                }
            }
        };
        
        eventSource.onerror = function() {
            eventSource.close();
            setTimeout(connectSSE, reconnectDelay);
            reconnectDelay = Math.min(reconnectDelay * 2, 30000);
        };
        
        eventSource.onopen = function() {
            reconnectDelay = 1000;
        };
    }
    
    connectSSE();
    
    window.addEventListener('beforeunload', () => eventSource?.close());
    </script>
</head>
<body>
    <style>
        body {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-transition {
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                <span class="d-none d-sm-inline">School Management</span>
                <span class="d-inline d-sm-none">SMS</span>
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <div class="nav-item me-3 d-none d-lg-block">
                    <small class="text-white-50">
                        <i class="fas fa-clock me-1"></i><span id="currentDateTime"><?= date('M d, Y H:i') ?></span>
                    </small>
                </div>
                
                <div class="nav-item me-3">
                    <a class="nav-link position-relative" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>messages.php">
                        <i class="fas fa-envelope fa-lg"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                            <?= $unread_count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($unread_notif_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $unread_notif_count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <h6 class="mb-0">Notifications</h6>
                            <a href="#" onclick="markAllRead(); return false;" class="small text-primary">Mark all read</a>
                        </li>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notif): ?>
                            <li>
                                <a class="dropdown-item <?= $notif['is_read'] ? 'text-muted' : '' ?>" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?><?= $notif['link'] ?: 'dashboard.php' ?>" onclick="markNotifRead(<?= $notif['id'] ?>)">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-<?= $notif['type'] == 'message' ? 'envelope' : ($notif['type'] == 'announcement' ? 'bullhorn' : 'bell') ?> text-<?= $notif['type'] == 'message' ? 'info' : ($notif['type'] == 'announcement' ? 'warning' : 'primary') ?> me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="small fw-semibold"><?= htmlspecialchars(substr($notif['title'], 0, 40)) ?></div>
                                            <?php if ($notif['content']): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars(substr($notif['content'], 0, 50)) ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted" style="font-size: 0.7rem;"><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                        <span class="badge bg-primary rounded-pill">New</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><a class="dropdown-item text-muted" href="#">No notifications</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <?php if ($profile_pic && file_exists((strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '') . 'uploads/profiles/' . $profile_pic)): ?>
                                <img src="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>uploads/profiles/<?= $profile_pic ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <span class="text-white fw-bold"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="small fw-semibold"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                            <div class="text-white-50" style="font-size: 0.7rem;"><?= ucfirst($_SESSION['user_role']) ?></div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li><h6 class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                    <?php if ($profile_pic && file_exists((strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '') . 'uploads/profiles/' . $profile_pic)): ?>
                                        <img src="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>uploads/profiles/<?= $profile_pic ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-white fw-bold"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                                    <small class="text-muted"><?= ucfirst($_SESSION['user_role']) ?></small>
                                </div>
                            </div>
                        </h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '' : 'views/' ?>messages.php"><i class="fas fa-envelope me-2"></i>Messages</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>controllers/auth.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div id="alert-container">
        <?php if (isset($_SESSION['alert'])): ?>
            <?php showAlert($_SESSION['alert']['message'], $_SESSION['alert']['type']); ?>
        <?php endif; ?>
    </div>
    
    <!-- Mobile More Menu Modal -->
    <div class="modal fade" id="mobileMenuModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 1.5rem; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 1.5rem 1.5rem 0 0;">
                    <h5 class="modal-title"><i class="fas fa-bars me-2"></i>All Menu Options</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeMobileMenu()"></button>
                </div>
                <div class="modal-body" style="padding: 1rem;" id="mobileMenuContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="<?= strpos($_SERVER['PHP_SELF'], '/views/') !== false ? '../' : '' ?>assets/js/mobile-menu.js"></script>
    <script>
    function openMobileMenu() {
        const modal = document.getElementById('mobileMenuModal');
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'menuBackdrop';
        backdrop.onclick = closeMobileMenu;
        document.body.appendChild(backdrop);
    }
    
    function closeMobileMenu() {
        const modal = document.getElementById('mobileMenuModal');
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.getElementById('menuBackdrop')?.remove();
    }
    </script>

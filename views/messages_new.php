<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
$db = getDB();

if ($_POST && isset($_POST['send_message'])) {
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = '../uploads/messages/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
            $attachment = $fileName;
        }
    }
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, attachment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id'], $_POST['subject'] ?? '', $_POST['message'], $attachment]);
    header('Location: messages_new.php?contact=' . $_POST['receiver_id']);
    exit;
}

$conversations = $db->prepare("SELECT CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as contact_id, u.name as contact_name, u.role as contact_role, m.message as last_message, m.created_at as last_message_time, (SELECT COUNT(*) FROM messages WHERE sender_id = contact_id AND receiver_id = ? AND is_read = 0) as unread_count FROM messages m JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END WHERE (m.sender_id = ? OR m.receiver_id = ?) AND m.id IN (SELECT MAX(id) FROM messages WHERE (sender_id = ? OR receiver_id = ?) GROUP BY CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END) ORDER BY m.created_at DESC LIMIT 20");
$conversations->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$conversations = $conversations->fetchAll();

$users = $db->prepare("SELECT id, name, role FROM users WHERE id != ? AND status = 'active' ORDER BY name LIMIT 50");
$users->execute([$_SESSION['user_id']]);
$users = $users->fetchAll();

$selectedContact = $_GET['contact'] ?? ($conversations[0]['contact_id'] ?? null);

$messages = [];
if ($selectedContact) {
    $messages = $db->prepare("SELECT m.id, m.sender_id, m.subject, m.message, m.attachment, m.created_at, m.is_read FROM messages m WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 50");
    $messages->execute([$_SESSION['user_id'], $selectedContact, $selectedContact, $_SESSION['user_id']]);
    $messages = array_reverse($messages->fetchAll());
    $db->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$selectedContact, $_SESSION['user_id']]);
    $contactInfo = $db->prepare("SELECT * FROM users WHERE id = ?");
    $contactInfo->execute([$selectedContact]);
    $contactInfo = $contactInfo->fetch();
}

require_once '../includes/header.php';
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="d-flex flex-column" style="flex: 1; height: 100%;">
        <div class="p-3 border-bottom" style="flex-shrink: 0;">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Messages</h4>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                    <i class="fas fa-plus me-2"></i>New
                </button>
            </div>
        </div>
        
        <div class="d-flex" style="flex: 1; overflow: hidden;">
            <div class="d-flex flex-column border-end bg-light" style="width: 300px;">
                <div class="p-2" style="flex-shrink: 0;">
                    <input type="text" class="form-control form-control-sm" placeholder="Search..." id="conversationSearch">
                </div>
                <div style="flex: 1; overflow-y: auto;">
                    <?php foreach ($conversations as $conv): ?>
                    <div class="p-2 border-bottom <?= $selectedContact == $conv['contact_id'] ? 'bg-primary bg-opacity-10' : '' ?>" onclick="location.href='messages_new.php?contact=<?= $conv['contact_id'] ?>'" style="cursor: pointer;">
                        <div class="d-flex">
                            <div class="me-2"><i class="fas fa-user"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold small"><?= htmlspecialchars($conv['contact_name']) ?></div>
                                <div class="text-muted small text-truncate"><?= htmlspecialchars(substr($conv['last_message'], 0, 30)) ?></div>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                            <span class="badge bg-primary"><?= $conv['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($selectedContact): ?>
            <div class="d-flex flex-column" style="flex: 1;">
                <div class="p-2 border-bottom bg-white" style="flex-shrink: 0;">
                    <div class="d-flex align-items-center">
                        <div class="me-2"><i class="fas fa-user"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?= htmlspecialchars($contactInfo['name']) ?></div>
                            <small class="text-muted"><?= ucfirst($contactInfo['role']) ?></small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="callUser()"><i class="fas fa-phone"></i></button>
                        <button class="btn btn-sm btn-outline-success" onclick="videoCall()"><i class="fas fa-video"></i></button>
                    </div>
                </div>
                
                <div class="p-3" style="flex: 1; overflow-y: auto;" id="messagesArea">
                    <?php foreach ($messages as $msg): ?>
                    <div class="mb-2 d-flex <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : '' ?>" data-id="<?= $msg['id'] ?>">
                        <div class="p-2 rounded <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                            <?php if ($msg['subject']): ?><div class="fw-bold small"><?= htmlspecialchars($msg['subject']) ?></div><?php endif; ?>
                            <div class="small"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <?php if ($msg['attachment']): ?><div class="small mt-1"><a href="../uploads/messages/<?= $msg['attachment'] ?>" target="_blank" class="<?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white' : '' ?>"><i class="fas fa-paperclip"></i> <?= htmlspecialchars($msg['attachment']) ?></a></div><?php endif; ?>
                            <div class="text-end" style="font-size: 0.7rem; opacity: 0.7;"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-top bg-white" style="flex-shrink: 0;">
                    <div id="fileName" class="small text-muted px-2 pt-1" style="min-height: 20px;"></div>
                    <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 p-2">
                        <input type="hidden" name="receiver_id" value="<?= $selectedContact ?>">
                        <input type="file" id="fileInput" name="attachment" style="display: none;" onchange="document.getElementById('fileName').textContent = this.files[0]?.name || ''; document.querySelector('textarea[name=message]').removeAttribute('required');">
                        <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Type..." required style="resize: none; flex: 1;"></textarea>
                        <div class="d-flex flex-column gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('fileInput').click()"><i class="fas fa-paperclip"></i></button>
                            <button type="submit" name="send_message" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="d-flex align-items-center justify-content-center" style="flex: 1;">
                <div class="text-center">
                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                    <h5>Select a conversation</h5>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.showIncomingCall = function() {};
function callUser() { alert('Call feature'); }
function videoCall() { alert('Video call feature'); }
</script>

<?php include '../includes/footer.php'; ?>

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
    $messageType = 'text';
    
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = '../uploads/messages/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['attachment']['name']);
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
            $attachment = $fileName;
            $messageType = 'file';
        }
    }
    
    if (isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] == 0) {
        $uploadDir = '../uploads/messages/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_voice.webm';
        if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $uploadDir . $fileName)) {
            $attachment = $fileName;
            $messageType = 'voice';
        }
    }
    
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, message_type, attachment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['receiver_id'], $_POST['subject'] ?? '', $_POST['message'] ?? 'Voice message', $messageType, $attachment]);
    
    require_once '../api/send_notification.php';
    $senderName = $_SESSION['user_name'];
    sendNotification($_POST['receiver_id'], 'message', "New message from $senderName", substr($_POST['message'] ?? 'Voice message', 0, 100), 'views/messages.php?contact=' . $_SESSION['user_id']);
    
    header('Location: messages.php?contact=' . $_POST['receiver_id']);
    exit;
}

require_once '../includes/header.php';

// Get conversations
$conversations = $db->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END as contact_id,
        u.name as contact_name,
        u.role as contact_role,
        u.profile_picture,
        (SELECT message FROM messages 
         WHERE (sender_id = ? AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages 
         WHERE (sender_id = ? AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = contact_id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    WHERE sender_id = ? OR receiver_id = ?
    ORDER BY last_message_time DESC
");
$conversations->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$conversations = $conversations->fetchAll();

// Get all users for new message
if ($_SESSION['user_role'] == 'teacher') {
    // Teachers can message: other teachers, admin, and parents of students in their classes
    $users = $db->prepare("
        SELECT DISTINCT u.id, u.name, u.role, u.email 
        FROM users u
        WHERE u.id != ? AND u.status = 'active'
        AND (
            u.role IN ('admin', 'teacher')
            OR (u.role = 'parent' AND u.id IN (
                SELECT p.user_id FROM parents p
                JOIN students s ON p.id = s.parent_id
                JOIN subjects sub ON s.class_id = sub.class_id
                WHERE sub.teacher_id = ?
            ))
        )
        ORDER BY u.role, u.name
    ");
    $users->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
} else {
    $users = $db->prepare("SELECT id, name, role, email FROM users WHERE id != ? AND status = 'active' ORDER BY role, name");
    $users->execute([$_SESSION['user_id']]);
}
$users = $users->fetchAll();

$selectedContact = $_GET['contact'] ?? null;

// Get messages for selected contact
$messages = [];
if ($selectedContact) {
    $messages = $db->prepare("
        SELECT m.*, u.name as sender_name, u.role as sender_role
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $messages->execute([$_SESSION['user_id'], $selectedContact, $selectedContact, $_SESSION['user_id']]);
    $messages = $messages->fetchAll();
    
    // Mark messages as read
    $db->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE sender_id = ? AND receiver_id = ?")->execute([$selectedContact, $_SESSION['user_id']]);
}
?>

<div class="d-flex" style="height: calc(100vh - 56px); position: fixed; width: 100%; top: 56px; left: 0;">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-grow-1 page-transition" style="overflow-y: auto; height: 100%; padding: 1rem;">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-comments me-2"></i>Messages</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="fas fa-plus me-2"></i>New Message
                    </button>
                </div>
            </div>
            
            <div class="p-0">
            <div class="row" style="height: calc(100vh - 200px);">
                <!-- Conversations Sidebar -->
                <div class="col-md-4 border-end bg-light p-0 h-100 d-flex flex-column">
                    <div class="p-3">
                        <button class="btn btn-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="fas fa-plus me-2"></i>New Message
                        </button>
                        <div class="search-container position-relative mb-3">
                            <input type="text" class="form-control search-input" placeholder="Search conversations..." id="conversationSearch">
                            <i class="fas fa-search position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                        </div>
                    </div>
                    
                    <div class="conversations-list flex-grow-1" style="overflow-y: auto;">
                        <?php foreach ($conversations as $conv): ?>
                        <a href="messages.php?contact=<?= $conv['contact_id'] ?>" class="conversation-item p-3 border-bottom <?= $selectedContact == $conv['contact_id'] ? 'active' : '' ?>" style="cursor: pointer; text-decoration: none; color: inherit; display: block;">
                            <div class="d-flex align-items-start">
                                <div class="avatar-sm bg-<?= $conv['contact_role'] == 'admin' ? 'danger' : ($conv['contact_role'] == 'teacher' ? 'success' : 'primary') ?> rounded-circle d-flex align-items-center justify-content-center me-3 overflow-hidden">
                                    <?php if ($conv['profile_picture'] && file_exists('../uploads/profiles/' . $conv['profile_picture'])): ?>
                                        <img src="../uploads/profiles/<?= $conv['profile_picture'] ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user text-white"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($conv['contact_name']) ?></h6>
                                        <small class="text-muted"><?= date('H:i', strtotime($conv['last_message_time'])) ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="mb-0 text-muted small text-truncate" style="max-width: 200px;">
                                            <?= htmlspecialchars(substr($conv['last_message'], 0, 50)) ?>...
                                        </p>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= ucfirst($conv['contact_role']) ?></small>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($conversations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h6>No conversations yet</h6>
                            <p class="text-muted small">Start a new conversation</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="col-md-8 p-0 d-flex flex-column h-100">
                    <?php if ($selectedContact): ?>
                        <?php 
                        $contactInfo = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $contactInfo->execute([$selectedContact]);
                        $contactInfo = $contactInfo->fetch();
                        ?>
                        
                        <!-- Chat Header -->
                        <div class="p-3 border-bottom bg-white">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-<?= $contactInfo['role'] == 'admin' ? 'danger' : ($contactInfo['role'] == 'teacher' ? 'success' : 'primary') ?> rounded-circle d-flex align-items-center justify-content-center me-3 overflow-hidden">
                                    <?php if ($contactInfo['profile_picture'] && file_exists('../uploads/profiles/' . $contactInfo['profile_picture'])): ?>
                                        <img src="../uploads/profiles/<?= $contactInfo['profile_picture'] ?>" alt="Profile" class="w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user text-white"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($contactInfo['name']) ?></h6>
                                    <small class="text-muted">
                                        <div class="status-indicator online"></div>
                                        <?= ucfirst($contactInfo['role']) ?>
                                    </small>
                                </div>
                                <div class="ms-auto">
                                    <button class="btn btn-outline-primary btn-sm morph-btn" onclick="callUser()">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="btn btn-outline-success btn-sm morph-btn" onclick="videoCall()">
                                        <i class="fas fa-video"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Messages Area -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; -webkit-overflow-scrolling: touch;" id="messagesArea">
                            <?php foreach ($messages as $msg): ?>
                            <div class="message-bubble <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?> mb-3" data-id="<?= $msg['id'] ?>">
                                <div class="d-flex <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'justify-content-end' : '' ?>">
                                    <div class="message-content <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?> p-3 rounded-3" style="max-width: 70%;">
                                        <?php if ($msg['subject']): ?>
                                            <h6 class="mb-2 <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white' : '' ?>"><?= htmlspecialchars($msg['subject']) ?></h6>
                                        <?php endif; ?>
                                        <?php if ($msg['message_type'] == 'voice'): ?>
                                            <div class="voice-note-player">
                                                <audio controls style="max-width: 100%;">
                                                    <source src="../uploads/messages/<?= $msg['attachment'] ?>" type="audio/webm">
                                                </audio>
                                            </div>
                                        <?php else: ?>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                                        <?php endif; ?>
                                        <?php if ($msg['attachment'] && $msg['message_type'] != 'voice'): 
                                            $ext = strtolower(pathinfo($msg['attachment'], PATHINFO_EXTENSION));
                                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                            $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
                                        ?>
                                            <?php if ($isImage): ?>
                                                <div class="mt-2">
                                                    <img src="../uploads/messages/<?= $msg['attachment'] ?>" class="img-fluid rounded" style="max-width: 300px; cursor: pointer;" onclick="window.open(this.src, '_blank')">
                                                    <div><a href="../uploads/messages/<?= $msg['attachment'] ?>" download class="small <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white' : '' ?>"><i class="fas fa-download"></i> Download</a></div>
                                                </div>
                                            <?php elseif ($isVideo): ?>
                                                <div class="mt-2">
                                                    <video controls class="rounded" style="max-width: 300px;"><source src="../uploads/messages/<?= $msg['attachment'] ?>" type="video/<?= $ext ?>"></video>
                                                    <div><a href="../uploads/messages/<?= $msg['attachment'] ?>" download class="small <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white' : '' ?>"><i class="fas fa-download"></i> Download</a></div>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2"><a href="../uploads/messages/<?= $msg['attachment'] ?>" download class="<?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white' : '' ?>"><i class="fas fa-file"></i> <?= htmlspecialchars($msg['attachment']) ?></a></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <small class="<?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-white-50' : 'text-muted' ?>">
                                            <?= date('M d, H:i', strtotime($msg['created_at'])) ?>
                                            <?php if ($msg['sender_id'] == $_SESSION['user_id']): ?>
                                                <i class="fas fa-check-double ms-1 <?= $msg['is_read'] ? 'text-info' : '' ?>"></i>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="p-3 border-top bg-white" style="flex-shrink: 0;">
                            <div id="fileName" class="small text-muted" style="min-height: 18px;"></div>
                            <div id="recordingStatus" class="small text-danger" style="min-height: 18px; display: none;">
                                <i class="fas fa-circle" style="animation: blink 1s infinite;"></i> Recording...
                            </div>
                            <form method="POST" enctype="multipart/form-data" class="d-flex align-items-end gap-2" id="messageForm" onsubmit="return sendMessage(event)">
                                <input type="hidden" name="receiver_id" value="<?= $selectedContact ?>">
                                <input type="file" id="fileInput" name="attachment" style="display: none;" onchange="document.getElementById('fileName').textContent = this.files[0]?.name || ''; document.querySelector('textarea[name=message]').removeAttribute('required');">
                                <input type="file" id="voiceInput" name="voice_note" style="display: none;">
                                <div class="flex-grow-1">
                                    <textarea name="message" id="messageText" class="form-control" rows="2" placeholder="Type your message..." required style="resize: none;"></textarea>
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('fileInput').click()" title="Attach file">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="voiceBtn" onmousedown="startRecording()" onmouseup="stopRecording()" ontouchstart="startRecording()" ontouchend="stopRecording()" title="Hold to record">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                    <button type="submit" name="send_message" class="btn btn-primary morph-btn" id="sendBtn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- No Chat Selected -->
                        <div class="d-flex align-items-center justify-content-center h-100" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);">
                            <div class="text-center p-4">
                                <div class="mb-4" style="font-size: 5rem; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3 class="mb-3" style="font-weight: 700; color: #333;">Welcome to Messages</h3>
                                <p class="text-muted mb-4">Select a conversation from the list or start a new one</p>
                                <button class="btn btn-primary btn-lg morph-btn" data-bs-toggle="modal" data-bs-target="#newMessageModal" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; padding: 0.875rem 2rem; border-radius: 2rem; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
                                    <i class="fas fa-plus me-2"></i>Start New Conversation
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </main>
</div>

<!-- Incoming Call Modal -->
<link href="../assets/css/call.css" rel="stylesheet">
<div class="modal fade incoming-call-modal" id="incomingCallModal" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="position-relative d-inline-block mb-4">
                    <div class="incoming-avatar">
                        <i class="fas fa-phone fa-3x"></i>
                    </div>
                    <div class="ringtone-animation"></div>
                    <div class="ringtone-animation" style="animation-delay: 0.5s;"></div>
                </div>
                <h3 class="caller-name mb-2">Incoming Call</h3>
                <p class="call-status mb-4"><i class="fas fa-phone-volume me-2"></i>Ringing...</p>
                
                <div class="call-actions">
                    <button class="call-btn btn-end" onclick="rejectCall()" title="Decline">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                    <button class="call-btn btn-answer" onclick="acceptCall()" title="Accept">
                        <i class="fas fa-phone"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call Modal -->
<div class="modal fade call-modal" id="callModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="call-container">
                <div class="network-indicator good" id="networkIndicator">
                    <i class="fas fa-signal"></i>
                    <span>Excellent</span>
                </div>
                
                <div class="call-quality-indicator" id="qualityIndicator">
                    <div class="quality-bar active"></div>
                    <div class="quality-bar active"></div>
                    <div class="quality-bar active"></div>
                    <div class="quality-bar"></div>
                    <div class="quality-bar"></div>
                </div>
                
                <div class="video-grid" id="videoGrid">
                    <div class="video-container" id="remoteVideoContainer" style="display: none;">
                        <video id="remoteVideo" autoplay playsinline></video>
                        <div class="video-label"><i class="fas fa-user me-2"></i><span id="remoteName">Contact</span></div>
                    </div>
                    <div class="video-container local" id="localVideoContainer" style="display: none;">
                        <video id="localVideo" autoplay muted playsinline></video>
                        <div class="video-label"><i class="fas fa-user me-2"></i>You</div>
                    </div>
                </div>
                
                <div id="audioCallView">
                    <div class="call-avatar">
                        <i class="fas fa-user fa-4x text-white"></i>
                    </div>
                    <div class="call-info">
                        <h3 id="callContactName">Contact</h3>
                        <p class="call-status" id="callStatus">Connecting...</p>
                        <div class="call-timer" id="callTimer" style="display: none;">00:00</div>
                    </div>
                </div>
                
                <div class="call-controls">
                    <button class="call-btn btn-mute" id="muteBtn" onclick="toggleMute()" title="Mute">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button class="call-btn btn-video" id="videoBtn" onclick="toggleVideo()" title="Video" style="display: none;">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="call-btn btn-speaker" id="speakerBtn" onclick="toggleSpeaker()" title="Speaker">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <button class="call-btn btn-end" onclick="endCall()" title="End Call">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade advanced-modal" id="newMessageModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <select name="receiver_id" class="form-select" required>
                            <option value="">Select recipient</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= ucfirst($user['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="floating-label">
                        <input type="text" name="subject" placeholder=" ">
                        <label>Subject (Optional)</label>
                    </div>
                    <div class="floating-label">
                        <textarea name="message" placeholder=" " rows="4" required></textarea>
                        <label>Message</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="send_message" class="btn btn-primary morph-btn">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectConversation(contactId) {
    if (window.innerWidth <= 768) {
        const sidebar = document.querySelector('.col-md-4');
        if (sidebar) sidebar.classList.add('chat-active');
    }
    window.location.href = `messages.php?contact=${contactId}`;
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 768) {
        const chatHeader = document.querySelector('.p-3.border-bottom.bg-white');
        if (chatHeader) {
            chatHeader.addEventListener('click', function(e) {
                const rect = chatHeader.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                if (clickX < 60) {
                    const sidebar = document.querySelector('.col-md-4');
                    if (sidebar) {
                        sidebar.classList.remove('chat-active');
                        setTimeout(() => {
                            window.location.href = 'messages.php';
                        }, 300);
                    }
                }
            });
        }
        
        const hasContact = <?= $selectedContact ? 'true' : 'false' ?>;
        if (hasContact) {
            const sidebar = document.querySelector('.col-md-4');
            if (sidebar) sidebar.classList.add('chat-active');
        }
        

    }
});

let peerConnection = null;
let localStream = null;
let receiverId = null;
let signalInterval = null;

const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' }
    ]
};

function callUser() {
    receiverId = <?= $selectedContact ?? 'null' ?>;
    if (!receiverId) return alert('No contact selected');
    
    document.getElementById('callContactName').textContent = <?= json_encode($contactInfo['name'] ?? 'Contact') ?>;
    document.getElementById('audioCallView').style.display = 'block';
    document.getElementById('videoGrid').style.display = 'none';
    document.getElementById('videoBtn').style.display = 'inline-flex';
    new bootstrap.Modal(document.getElementById('callModal')).show();
    startCall('audio');
}

function videoCall() {
    receiverId = <?= $selectedContact ?? 'null' ?>;
    if (!receiverId) return alert('No contact selected');
    
    document.getElementById('remoteName').textContent = <?= json_encode($contactInfo['name'] ?? 'Contact') ?>;
    document.getElementById('audioCallView').style.display = 'none';
    document.getElementById('videoGrid').style.display = 'grid';
    document.getElementById('videoBtn').style.display = 'inline-flex';
    new bootstrap.Modal(document.getElementById('callModal')).show();
    startCall('video');
}

async function startCall(type) {
    const constraints = type === 'video' ? { video: { width: 1280, height: 720 }, audio: { echoCancellation: true, noiseSuppression: true } } : { audio: { echoCancellation: true, noiseSuppression: true } };
    
    try {
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        if (type === 'video') {
            document.getElementById('localVideo').srcObject = localStream;
            document.getElementById('localVideoContainer').style.display = 'block';
        }
        
        peerConnection = new RTCPeerConnection(rtcConfig);
        localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
        
        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                fetch('../api/call_signal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=ice&receiver_id=${receiverId}&candidate=${encodeURIComponent(JSON.stringify(event.candidate))}`
                });
            }
        };
        
        peerConnection.ontrack = event => {
            document.getElementById('callStatus').textContent = 'Connected';
            document.getElementById('callTimer').style.display = 'block';
            if (event.streams[0]) {
                document.getElementById('remoteVideo').srcObject = event.streams[0];
                document.getElementById('remoteVideoContainer').style.display = 'block';
            }
            startTimer();
            updateNetworkQuality();
        };
        
        peerConnection.onconnectionstatechange = () => {
            const state = peerConnection.connectionState;
            if (state === 'connected') {
                document.getElementById('callStatus').textContent = 'Connected';
            } else if (state === 'disconnected' || state === 'failed') {
                document.getElementById('callStatus').textContent = 'Connection lost';
            }
        };
        
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        
        await fetch('../api/call_signal.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=initiate&receiver_id=${receiverId}&offer=${encodeURIComponent(JSON.stringify(offer))}`
        });
        
        pollSignals();
    } catch (error) {
        alert('Could not access camera/microphone: ' + error.message);
        bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
    }
}

async function pollSignals() {
    signalInterval = setInterval(async () => {
        const response = await fetch('../api/call_signal.php?action=poll');
        const data = await response.json();
        
        for (const signal of data.signals || []) {
            if (signal.signal_type === 'offer') {
                showIncomingCall(signal.caller_id, signal.signal_data);
            } else if (signal.signal_type === 'answer') {
                await peerConnection.setRemoteDescription(JSON.parse(signal.signal_data));
            } else if (signal.signal_type === 'ice') {
                await peerConnection.addIceCandidate(JSON.parse(signal.signal_data));
            }
        }
    }, 1000);
}

function showIncomingCall(callerId, offerData, callerName) {
    const modal = document.getElementById('incomingCallModal');
    if (!modal) return;
    
    modal.querySelector('.caller-name').textContent = callerName || 'Incoming Call';
    modal.dataset.callerId = callerId;
    modal.dataset.offer = offerData;
    new bootstrap.Modal(modal).show();
    
    const ringtone = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGGS57OihUBELTKXh8bllHAU2jdXzzn0vBSh+zPLaizsKGGO56+mjUhELTKXh8bllHAU2jdXzzn0vBSh+zPLaizsKGGO56+mjUhELTKXh8bllHAU2jdXzzn0vBQ==');
    ringtone.loop = true;
    ringtone.play();
    modal.dataset.ringtone = 'playing';
}

async function acceptCall() {
    const modal = document.getElementById('incomingCallModal');
    const callerId = modal.dataset.callerId;
    const offer = JSON.parse(modal.dataset.offer);
    
    bootstrap.Modal.getInstance(modal).hide();
    
    document.getElementById('callContactName').textContent = modal.querySelector('.caller-name').textContent;
    document.getElementById('audioCallView').style.display = 'block';
    document.getElementById('videoGrid').style.display = 'none';
    new bootstrap.Modal(document.getElementById('callModal')).show();
    
    localStream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
    peerConnection = new RTCPeerConnection(rtcConfig);
    localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
    
    peerConnection.ontrack = event => {
        document.getElementById('callStatus').textContent = 'Connected';
        document.getElementById('callTimer').style.display = 'block';
        if (event.streams[0]) {
            document.getElementById('remoteVideo').srcObject = event.streams[0];
        }
        startTimer();
        updateNetworkQuality();
    };
    
    await peerConnection.setRemoteDescription(offer);
    const answer = await peerConnection.createAnswer();
    await peerConnection.setLocalDescription(answer);
    
    await fetch('../api/call_signal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=answer&caller_id=${callerId}&answer=${encodeURIComponent(JSON.stringify(answer))}`
    });
    
    receiverId = callerId;
    pollSignals();
}

function rejectCall() {
    bootstrap.Modal.getInstance(document.getElementById('incomingCallModal')).hide();
}

let callSeconds = 0;
let callInterval;

function startTimer() {
    callSeconds = 0;
    callInterval = setInterval(() => {
        callSeconds++;
        const mins = Math.floor(callSeconds / 60);
        const secs = callSeconds % 60;
        document.getElementById('callTimer').textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }, 1000);
}

function endCall() {
    clearInterval(callInterval);
    clearInterval(signalInterval);
    
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    document.getElementById('localVideoContainer').style.display = 'none';
    document.getElementById('remoteVideoContainer').style.display = 'none';
    document.getElementById('callTimer').style.display = 'none';
    document.getElementById('callTimer').textContent = '00:00';
    document.getElementById('muteBtn').classList.remove('active');
    document.getElementById('videoBtn').classList.remove('active');
    
    fetch('../api/call_signal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=end'
    });
    
    bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
}

function toggleMute() {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            const btn = document.getElementById('muteBtn');
            btn.innerHTML = audioTrack.enabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
            btn.classList.toggle('active', !audioTrack.enabled);
        }
    }
}

function toggleVideo() {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            const btn = document.getElementById('videoBtn');
            btn.innerHTML = videoTrack.enabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
            btn.classList.toggle('active', !videoTrack.enabled);
            document.getElementById('localVideoContainer').style.display = videoTrack.enabled ? 'block' : 'none';
        }
    }
}

let speakerEnabled = true;
function toggleSpeaker() {
    speakerEnabled = !speakerEnabled;
    const btn = document.getElementById('speakerBtn');
    btn.innerHTML = speakerEnabled ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
    btn.classList.toggle('active', speakerEnabled);
    const remoteVideo = document.getElementById('remoteVideo');
    if (remoteVideo) remoteVideo.muted = !speakerEnabled;
}

function updateNetworkQuality() {
    if (!peerConnection) return;
    
    setInterval(async () => {
        const stats = await peerConnection.getStats();
        let packetsLost = 0;
        let packetsReceived = 0;
        
        stats.forEach(report => {
            if (report.type === 'inbound-rtp') {
                packetsLost += report.packetsLost || 0;
                packetsReceived += report.packetsReceived || 0;
            }
        });
        
        const lossRate = packetsReceived > 0 ? (packetsLost / packetsReceived) * 100 : 0;
        const indicator = document.getElementById('networkIndicator');
        const bars = document.querySelectorAll('.quality-bar');
        
        if (lossRate < 2) {
            indicator.className = 'network-indicator good';
            indicator.innerHTML = '<i class="fas fa-signal"></i><span>Excellent</span>';
            bars.forEach((bar, i) => bar.classList.toggle('active', i < 5));
        } else if (lossRate < 5) {
            indicator.className = 'network-indicator medium';
            indicator.innerHTML = '<i class="fas fa-signal"></i><span>Good</span>';
            bars.forEach((bar, i) => bar.classList.toggle('active', i < 3));
        } else {
            indicator.className = 'network-indicator poor';
            indicator.innerHTML = '<i class="fas fa-signal"></i><span>Poor</span>';
            bars.forEach((bar, i) => bar.classList.toggle('active', i < 1));
        }
    }, 2000);
}



// Voice recording
let mediaRecorder;
let audioChunks = [];

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        
        mediaRecorder.ondataavailable = event => {
            audioChunks.push(event.data);
        };
        
        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const file = new File([audioBlob], 'voice_note.webm', { type: 'audio/webm' });
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('voiceInput').files = dataTransfer.files;
            
            document.getElementById('messageText').removeAttribute('required');
            document.getElementById('fileName').textContent = '🎤 Voice message ready';
            document.getElementById('messageForm').requestSubmit();
        };
        
        mediaRecorder.start();
        document.getElementById('recordingStatus').style.display = 'block';
        document.getElementById('voiceBtn').classList.add('btn-danger');
    } catch (error) {
        alert('Could not access microphone: ' + error.message);
    }
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        document.getElementById('recordingStatus').style.display = 'none';
        document.getElementById('voiceBtn').classList.remove('btn-danger');
    }
}

window.showIncomingCall = showIncomingCall;

let isSending = false;

function sendMessage(e) {
    e.preventDefault();
    
    if (isSending) return false;
    
    const form = document.getElementById('messageForm');
    const messageText = document.getElementById('messageText').value.trim();
    
    if (!messageText) return false;
    
    isSending = true;
    const formData = new FormData(form);
    formData.append('send_message', '1');
    const sendBtn = document.getElementById('sendBtn');
    
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            document.getElementById('messageText').value = '';
            document.getElementById('fileName').textContent = '';
            document.getElementById('fileInput').value = '';
            
            setTimeout(() => {
                const messagesArea = document.getElementById('messagesArea');
                if (messagesArea) messagesArea.scrollTop = messagesArea.scrollHeight;
            }, 50);
        }
        return response.text();
    })
    .then(() => {
        isSending = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    })
    .catch(error => {
        console.error('Error:', error);
        isSending = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
    
    return false;
}

// Real-time message updates
let lastMessageId = 0;
let messageCheckInterval;

function startMessagePolling() {
    if (messageCheckInterval) clearInterval(messageCheckInterval);
    
    messageCheckInterval = setInterval(() => {
        const contactId = <?= $selectedContact ?? 'null' ?>;
        if (!contactId) return;
        
        fetch(`../api/get_new_messages.php?contact=${contactId}&last_id=${lastMessageId}`, {
            cache: 'no-cache',
            priority: 'high'
        })
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    const messagesArea = document.getElementById('messagesArea');
                    const wasAtBottom = messagesArea.scrollHeight - messagesArea.scrollTop <= messagesArea.clientHeight + 100;
                    
                    data.messages.forEach(msg => {
                        if (document.querySelector(`[data-id="${msg.id}"]`)) return;
                        
                        const isSent = msg.sender_id == <?= $_SESSION['user_id'] ?>;
                        const messageHtml = `
                            <div class="message-bubble ${isSent ? 'sent' : 'received'} mb-3" data-id="${msg.id}">
                                <div class="d-flex ${isSent ? 'justify-content-end' : ''}">
                                    <div class="message-content ${isSent ? 'bg-primary text-white' : 'bg-light'} p-3 rounded-3" style="max-width: 70%;">
                                        ${msg.subject ? `<h6 class="mb-2 ${isSent ? 'text-white' : ''}">${msg.subject}</h6>` : ''}
                                        <p class="mb-1">${msg.message.replace(/\n/g, '<br>')}</p>
                                        <small class="${isSent ? 'text-white-50' : 'text-muted'}">
                                            ${new Date(msg.created_at).toLocaleString('en-US', {month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'})}
                                            ${isSent ? '<i class="fas fa-check-double ms-1"></i>' : ''}
                                        </small>
                                    </div>
                                </div>
                            </div>`;
                        messagesArea.insertAdjacentHTML('beforeend', messageHtml);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    
                    if (wasAtBottom) {
                        messagesArea.scrollTop = messagesArea.scrollHeight;
                    }
                }
            })
            .catch(err => console.error('Message polling error:', err));
    }, 200);
}

if (<?= $selectedContact ? 'true' : 'false' ?>) {
    startMessagePolling();
}

// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesArea = document.getElementById('messagesArea');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
        const lastMsg = messagesArea.querySelector('.message-bubble:last-child');
        if (lastMsg && lastMsg.dataset && lastMsg.dataset.id) {
            lastMessageId = parseInt(lastMsg.dataset.id) || 0;
        }
    }
    
    if (<?= $selectedContact ? 'true' : 'false' ?>) {
        startMessagePolling();
    }
    
    // Search conversations
    const searchInput = document.getElementById('conversationSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const conversations = document.querySelectorAll('.conversation-item');
            
            conversations.forEach(conv => {
                const text = conv.textContent.toLowerCase();
                conv.style.display = text.includes(filter) ? 'block' : 'none';
            });
        });
    }
});

// Real-time message updates (simulate)
setInterval(() => {
    // In a real application, this would check for new messages via AJAX
    const unreadBadges = document.querySelectorAll('.badge.bg-primary');
    unreadBadges.forEach(badge => {
        if (Math.random() > 0.9) { // 10% chance
            const count = parseInt(badge.textContent) + 1;
            badge.textContent = count;
            badge.classList.add('pulse');
            setTimeout(() => badge.classList.remove('pulse'), 1000);
        }
    });
}, 10000);
</script>

<link href="../assets/css/mobile-chat.css" rel="stylesheet">
<style>
.conversation-item {
    transition: all 0.3s ease;
    cursor: pointer;
}

.conversation-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.conversation-item.active {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
    border-left: 4px solid #667eea;
}

.message-bubble {
    animation: messageSlideIn 0.3s ease;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-content {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.message-content:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sent .message-content {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
}

@media (prefers-color-scheme: dark) {
    .conversation-item {
        background: #2a2a2a !important;
        border-color: rgba(255,255,255,0.1) !important;
    }
    
    .conversation-item * {
        color: #ffffff !important;
    }
    
    .conversation-item:hover {
        background: #333 !important;
    }
    
    .conversation-item.active {
        background: rgba(102, 126, 234, 0.3) !important;
    }
    
    .received .message-content {
        background: #333 !important;
        color: #ffffff !important;
    }
    
    #messagesArea {
        background: #1a1a1a !important;
    }
    
    .search-input {
        background: #2a2a2a !important;
        color: #ffffff !important;
        border-color: #444 !important;
    }
    
    .p-3.border-bottom.bg-white, .p-3.border-top.bg-white {
        background: #2a2a2a !important;
        border-color: rgba(255,255,255,0.1) !important;
    }
    
    .col-md-4.bg-light {
        background: #1a1a1a !important;
    }
    
    textarea.form-control {
        background: #2a2a2a !important;
        color: #ffffff !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
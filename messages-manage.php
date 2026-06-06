<?php
// Messages Management Admin Page
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'mark_read':
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt = $db->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Marked as read']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed']);
            }
            exit;
            
        case 'mark_chat_read':
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt = $db->prepare("UPDATE user_messages SET status = 'read' WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Marked as read']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed']);
            }
            exit;
            
        case 'reply_chat':
            $messageId = intval($_POST['message_id'] ?? 0);
            $replyText = sanitize($_POST['reply'] ?? '');
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($messageId <= 0 || empty($replyText)) {
                echo json_encode(['success' => false, 'message' => 'Reply cannot be empty']);
                exit;
            }
            
            try {
                // Get the original message to find sender
                $getMsg = $db->prepare("SELECT * FROM user_messages WHERE id = ?");
                $getMsg->execute([$messageId]);
                $originalMsg = $getMsg->fetch();
                
                if ($originalMsg) {
                    // Save admin reply to user_messages
                    $senderId = $originalMsg['sender_id'];
                    $subject = "Re: " . ($originalMsg['subject'] ?: 'Your message');
                    $stmt = $db->prepare("INSERT INTO user_messages (sender_id, receiver_id, subject, message, is_from_admin) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([0, $senderId, $subject, $replyText]);
                    
                    echo json_encode(['success' => true, 'message' => 'Reply sent successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Message not found']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send reply: ' . $e->getMessage()]);
            }
            exit;
            
        case 'reply':
            $messageId = intval($_POST['message_id'] ?? 0);
            $replyText = sanitize($_POST['reply'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $userId = intval($_POST['user_id'] ?? 0);
            
            if ($messageId <= 0 || empty($replyText)) {
                echo json_encode(['success' => false, 'message' => 'Reply cannot be empty']);
                exit;
            }
            
            try {
                // Save reply to messages table
                $stmt = $db->prepare("INSERT INTO message_replies (message_id, reply_text, admin_email) VALUES (?, ?, ?)");
                $stmt->execute([$messageId, $replyText, $_SESSION['admin_email']]);
                
                // Update message status
                $updateStmt = $db->prepare("UPDATE messages SET status = 'replied' WHERE id = ?");
                $updateStmt->execute([$messageId]);
                
                // If sender is registered user, also save to user_messages
                if ($userId > 0) {
                    $userMsgStmt = $db->prepare("INSERT INTO user_messages (sender_id, receiver_id, subject, message, is_from_admin) VALUES (?, ?, ?, ?, 1)");
                    $subject = "Re: Your message to Kayunga District Youth Council";
                    $userMsgStmt->execute([0, $userId, $subject, $replyText]);
                }
                
                // Store notification
                $notifStmt = $db->prepare("INSERT INTO reply_notifications (email, message_id, reply_text) VALUES (?, ?, ?)");
                $notifStmt->execute([$email, $messageId, $replyText]);
                
                echo json_encode(['success' => true, 'message' => 'Reply sent successfully!']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send reply']);
            }
            exit;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            try {
                $db->prepare("DELETE FROM message_replies WHERE message_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM reply_notifications WHERE message_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Message deleted']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
            exit;
    }
}

$filter = sanitize($_GET['filter'] ?? '');

// Get contact form messages
$sql = "SELECT m.*, u.id as user_id, 'contact' as source FROM messages m LEFT JOIN users u ON m.email = u.email";
$params = [];
if ($filter) {
    $sql .= " WHERE m.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY m.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$contactMessages = $stmt->fetchAll();

// Get user_messages (messages sent to admin) with sender name
$chatSql = "SELECT m.*, m.sender_id as user_id, 'chat' as source, m.status as message_status, 
    COALESCE(p.first_name, u.email, 'Unknown') as sender_name
    FROM user_messages m 
    LEFT JOIN users u ON m.sender_id = u.id
    LEFT JOIN user_profiles p ON m.sender_id = p.user_id
    WHERE m.receiver_id = 0 
    ORDER BY m.created_at DESC";
$chatStmt = $db->query($chatSql);
$chatMessages = $chatStmt->fetchAll();

// Merge both
$messages = array_merge($contactMessages, $chatMessages);

// Sort by date
usort($messages, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Kayunga Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .msg-list { display: flex; flex-direction: column; gap: 15px; }
        .msg-card { background: white; border-radius: 12px; padding: 20px; cursor: pointer; transition: box-shadow 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .msg-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        .msg-card.unread { border-left: 4px solid #2563eb; background: #f0f7ff; }
        .msg-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .msg-from { font-weight: 600; }
        .msg-date { font-size: 0.85rem; color: #666; }
        .msg-subject { font-weight: 500; margin-bottom: 5px; }
        .msg-preview { color: #666; font-size: 0.9rem; }
        .msg-actions { display: flex; gap: 8px; margin-top: 15px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 95%; max-width: 700px; max-height: 90vh; overflow-y: auto; }
        
        .reply-thread { max-height: 400px; overflow-y: auto; margin-bottom: 20px; }
        .original-msg { background: #f5f7fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .original-msg .label { font-size: 0.75rem; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .reply-item { padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .reply-item.admin { background: #e8f4fd; }
        .reply-item.admin .reply-header { color: #1877F2; }
        .reply-item.subscriber { background: #f0f0f0; }
        .reply-header { font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; }
        .reply-body { line-height: 1.6; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-tabs a { padding: 8px 16px; border-radius: 8px; text-decoration: none; color: #666; }
        .filter-tabs a.active { background: #2563eb; color: white; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>Messages</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="filter-tabs">
                    <a href="messages-manage.php" class="<?php echo !$filter ? 'active' : ''; ?>">All</a>
                    <a href="?filter=unread" class="<?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
                    <a href="?filter=read" class="<?php echo $filter === 'read' ? 'active' : ''; ?>">Read</a>
                    <a href="?filter=replied" class="<?php echo $filter === 'replied' ? 'active' : ''; ?>">Replied</a>
                </div>
                
                <div class="msg-list">
                    <?php foreach ($messages as $msg): $source = $msg['source'] ?? 'contact'; ?>
                    <div class="msg-card <?php echo ($msg['status'] ?? $msg['message_status']) === 'unread' ? 'unread' : ''; ?>" onclick="openMessage(<?php echo $msg['id']; ?>, '<?php echo $source; ?>')">
                        <div class="msg-header">
                            <div>
                                <div class="msg-from">
                                    <?php if ($source === 'chat'): ?>
                                        <?php echo htmlspecialchars($msg['sender_name'] ?? 'User ' . $msg['user_id']); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($msg['name']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="msg-date">
                                    <?php if ($source === 'chat'): ?>
                                        ID: <?php echo $msg['user_id']; ?> | <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($msg['email']); ?> | <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $msg['status'] ?? $msg['message_status']; ?>"><?php echo $source === 'chat' ? 'chat' : ($msg['status'] ?? 'unread'); ?></span>
                        </div>
                        <div class="msg-subject"><?php echo htmlspecialchars($msg['subject'] ?? $msg['message']); ?></div>
                        <div class="msg-preview"><?php echo htmlspecialchars(substr($msg['message'] ?? '', 0, 100)); ?>...</div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No messages found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Message Detail Modal -->
    <div class="modal" id="msgModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:white; padding:30px; border-radius:12px; width:95%; max-width:700px; max-height:90vh; overflow-y:auto;">
            <h2 id="msgSubject">Message</h2>
            <div id="msgContent" class="original-msg">
                <div class="label">From</div>
                <div id="msgFrom"></div>
                <div class="label" style="margin-top:15px;">Message</div>
                <div id="msgBody"></div>
            </div>
            
            <div class="reply-thread" id="replyThread"></div>
            
            <form id="replyForm">
                <input type="hidden" id="replyMsgId" value="">
                <input type="hidden" id="replyEmail" value="">
                <input type="hidden" id="replyUserId" value="">
                <div class="form-group">
                    <label>Your Reply</label>
                    <textarea id="replyText" rows="4" placeholder="Type your reply here..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Reply</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
    let currentMessages = <?php echo json_encode($messages); ?>;
    let currentMessageSource = 'contact';
    
    async function openMessage(id, source) {
        currentMessageSource = source || 'contact';
        const msg = currentMessages.find(m => m.id == id && (m.source || 'contact') == source);
        if (!msg) {
            alert('Message not found');
            return;
        }
        
        const isChat = source === 'chat';
        
        document.getElementById('replyMsgId').value = id;
        document.getElementById('replyEmail').value = msg.email || '';
        document.getElementById('replyUserId').value = msg.user_id || 0;
        document.getElementById('msgSubject').textContent = isChat ? 'Chat Message' : (msg.subject || 'No Subject');
        
        if (isChat) {
            document.getElementById('msgFrom').textContent = 'User ID: ' + msg.user_id;
        } else {
            document.getElementById('msgFrom').textContent = (msg.name || 'Unknown') + ' (' + (msg.email || 'No email') + ')';
        }
        
        document.getElementById('msgBody').textContent = msg.message || 'No message';
        
        // Show modal immediately
        document.getElementById('msgModal').style.display = 'flex';
        
        // Mark as read in background (for chat messages only)
        if (isChat) {
            try {
                const formData = new FormData();
                formData.append('ajax_action', 'mark_chat_read');
                formData.append('id', id);
                await fetch('messages-manage.php', { method: 'POST', body: formData });
            } catch(e) { console.log(e); }
        }
        
        // Load replies - for chat messages, load from user_messages
        const replyThread = document.getElementById('replyThread');
        replyThread.innerHTML = '<p style="color:#999;">Loading replies...</p>';
        
        try {
            let replyUrl = isChat ? '../api/get-chat-replies.php?message_id=' + id : '../api/get-replies.php?id=' + id;
            const replyRes = await fetch(replyUrl);
            const replyData = await replyRes.json();
            
            console.log('Reply API response:', replyData);
            
            if (replyData.success && replyData.replies && replyData.replies.length > 0) {
                replyThread.innerHTML = '';
                replyData.replies.forEach(r => {
                    replyThread.innerHTML += '<div style="background:#e8f4fd; padding:15px; border-radius:8px; margin-bottom:10px;"><div style="color:#1877F2; font-size:0.85rem; font-weight:500;">Admin Response - ' + r.admin_email + ' (' + r.created_at + ')</div><div style="margin-top:5px; line-height:1.6;">' + r.reply_text + '</div></div>';
                });
            } else {
                replyThread.innerHTML = '<p style="color:#999;">No previous replies for this message. Send a reply below.</p>';
            }
        } catch(e) {
            console.log('Error:', e);
            replyThread.innerHTML = '<p style="color:red;">Error loading replies: ' + e.message + '</p>';
        }
    }
    
    function closeModal() {
        document.getElementById('msgModal').style.display = 'none';
        location.reload();
    }
    
    document.getElementById('replyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData();
        
        // Use different action based on message source
        const action = currentMessageSource === 'chat' ? 'reply_chat' : 'reply';
        
        formData.append('ajax_action', action);
        formData.append('message_id', document.getElementById('replyMsgId').value);
        formData.append('reply', document.getElementById('replyText').value);
        formData.append('email', document.getElementById('replyEmail').value);
        formData.append('user_id', document.getElementById('replyUserId').value);
        
        const res = await fetch('messages-manage.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        if (data.success) {
            document.getElementById('replyText').value = '';
            openMessage(document.getElementById('replyMsgId').value, currentMessageSource);
        }
    });
    </script>
</body>
</html>
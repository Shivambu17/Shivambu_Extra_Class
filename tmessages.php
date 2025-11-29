<?php
include_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $receiver_id = $_POST['receiver_id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!empty($receiver_id) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $receiver_id, $subject, $message]);
        $_SESSION['message'] = "Message sent successfully!";
        header("Location: messages.php");
        exit;
    }
}

// Handle message actions (delete, mark as read/unread)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] != 'send_message') {
    $message_id = $_POST['message_id'] ?? '';
    
    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$message_id, $user['id'], $user['id']]);
        $_SESSION['message'] = "Message deleted successfully!";
    } elseif ($_POST['action'] === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$message_id, $user['id']]);
    } elseif ($_POST['action'] === 'mark_unread') {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$message_id, $user['id']]);
    }
    
    header("Location: messages.php");
    exit;
}

// Get user's received messages
$stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name, u.role as sender_role
                      FROM messages m 
                      JOIN users u ON m.sender_id = u.id 
                      WHERE m.receiver_id = ? 
                      ORDER BY m.created_at DESC");
$stmt->execute([$user['id']]);
$received_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's sent messages
$stmt = $pdo->prepare("SELECT m.*, u.full_name as receiver_name, u.role as receiver_role
                      FROM messages m 
                      JOIN users u ON m.receiver_id = u.id 
                      WHERE m.sender_id = ? 
                      ORDER BY m.created_at DESC");
$stmt->execute([$user['id']]);
$sent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread messages
$unread_count = 0;
foreach ($received_messages as $message) {
    if (!$message['is_read']) {
        $unread_count++;
    }
}

// Get users for dropdown based on role
if ($user['role'] == 'teacher') {
    // Teachers can message students and admins
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE (role = 'student' OR role = 'admin') AND status = 'approved' AND id != ?");
} elseif ($user['role'] == 'student') {
    // Students can message teachers and admins
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE (role = 'teacher' OR role = 'admin') AND status = 'approved' AND id != ?");
} else {
    // Admin can message everyone
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE status = 'approved' AND id != ?");
}
$users_stmt->execute([$user['id']]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?= ucfirst($user['role']) ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: none;
        }
        
        .message-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }
        
        .message-card.unread {
            border-left-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
        }
        
        .message-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .message-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .message-card.expanded .message-content {
            max-height: 500px;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
            margin-top: 1rem;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-envelope me-2"></i>Messages</h2>
                        <p class="text-muted mb-0">Communicate with <?= $user['role'] == 'teacher' ? 'students and admins' : ($user['role'] == 'student' ? 'teachers and admins' : 'all users') ?></p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="fas fa-plus me-2"></i>New Message
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-inbox fa-2x text-primary mb-2"></i>
                    <h4><?= count($received_messages) ?></h4>
                    <p class="text-muted mb-0">Total Messages</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-envelope fa-2x text-warning mb-2"></i>
                    <h4><?= $unread_count ?></h4>
                    <p class="text-muted mb-0">Unread Messages</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-paper-plane fa-2x text-success mb-2"></i>
                    <h4><?= count($sent_messages) ?></h4>
                    <p class="text-muted mb-0">Sent Messages</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card p-3 text-center">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h4><?= count($users) ?></h4>
                    <p class="text-muted mb-0">Available Contacts</p>
                </div>
            </div>
        </div>

        <!-- Messages Tabs -->
        <div class="dashboard-card">
            <div class="card-header bg-transparent">
                <ul class="nav nav-tabs card-header-tabs" id="messagesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button">
                            <i class="fas fa-inbox me-2"></i>Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-primary ms-1"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                            <i class="fas fa-paper-plane me-2"></i>Sent
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="messagesTabContent">
                    <!-- Inbox Tab -->
                    <div class="tab-pane fade show active" id="inbox" role="tabpanel">
                        <?php if (empty($received_messages)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No Messages</h4>
                                <p class="text-muted">Your inbox is empty. Send a message to get started!</p>
                            </div>
                        <?php else: ?>
                            <div class="p-3">
                                <?php foreach ($received_messages as $message): ?>
                                    <div class="message-card <?= !$message['is_read'] ? 'unread' : '' ?>" data-message-id="<?= $message['id'] ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="message-avatar me-3">
                                                <?= strtoupper(substr($message['sender_name'], 0, 2)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($message['sender_name']) ?></h6>
                                                        <span class="badge bg-secondary"><?= ucfirst($message['sender_role']) ?></span>
                                                        <?php if (!$message['is_read']): ?>
                                                            <span class="badge bg-primary ms-1">Unread</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <h6 class="mt-2 mb-2"><?= htmlspecialchars($message['subject'] ?: 'No Subject') ?></h6>
                                                <p class="text-muted mb-2"><?= nl2br(htmlspecialchars(substr($message['message'], 0, 150))) ?>...</p>
                                                
                                                <div class="action-buttons mt-2">
                                                    <?php if (!$message['is_read']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                            <button type="submit" name="action" value="mark_read" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-envelope-open me-1"></i>Mark Read
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                            <button type="submit" name="action" value="mark_unread" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-envelope me-1"></i>Mark Unread
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-sm btn-outline-info reply-btn" 
                                                            data-sender-id="<?= $message['sender_id'] ?>" 
                                                            data-sender-name="<?= htmlspecialchars($message['sender_name']) ?>"
                                                            data-subject="<?= htmlspecialchars($message['subject']) ?>">
                                                        <i class="fas fa-reply me-1"></i>Reply
                                                    </button>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <div class="message-content">
                                                    <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sent Tab -->
                    <div class="tab-pane fade" id="sent" role="tabpanel">
                        <?php if (empty($sent_messages)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-paper-plane fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No Sent Messages</h4>
                                <p class="text-muted">You haven't sent any messages yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="p-3">
                                <?php foreach ($sent_messages as $message): ?>
                                    <div class="message-card" data-message-id="<?= $message['id'] ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="message-avatar me-3">
                                                <?= strtoupper(substr($message['receiver_name'], 0, 2)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">To: <?= htmlspecialchars($message['receiver_name']) ?></h6>
                                                        <span class="badge bg-secondary"><?= ucfirst($message['receiver_role']) ?></span>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <h6 class="mt-2 mb-2"><?= htmlspecialchars($message['subject'] ?: 'No Subject') ?></h6>
                                                <p class="text-muted mb-2"><?= nl2br(htmlspecialchars(substr($message['message'], 0, 150))) ?>...</p>
                                                
                                                <div class="action-buttons mt-2">
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                        <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </button>
                                                    </form>
                                                </div>
                                                
                                                <div class="message-content">
                                                    <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="messages.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_message">
                        <div class="mb-3">
                            <label for="receiver_id" class="form-label">To</label>
                            <select class="form-select" id="receiver_id" name="receiver_id" required>
                                <option value="">Select Recipient</option>
                                <?php foreach ($users as $recipient): ?>
                                    <option value="<?= $recipient['id'] ?>">
                                        <?= htmlspecialchars($recipient['full_name']) ?> (<?= ucfirst($recipient['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Message subject">
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="8" placeholder="Type your message here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Message expansion
            const messageCards = document.querySelectorAll('.message-card');
            messageCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('.action-buttons') && !e.target.closest('.reply-btn')) {
                        this.classList.toggle('expanded');
                        
                        // Mark as read if unread
                        if (this.classList.contains('unread') && this.closest('#inbox')) {
                            const messageId = this.getAttribute('data-message-id');
                            markAsRead(messageId);
                        }
                    }
                });
            });
            
            // Reply button functionality
            const replyButtons = document.querySelectorAll('.reply-btn');
            replyButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const senderId = this.getAttribute('data-sender-id');
                    const senderName = this.getAttribute('data-sender-name');
                    const subject = this.getAttribute('data-subject');
                    
                    // Set values in compose modal
                    document.getElementById('receiver_id').value = senderId;
                    if (subject && subject !== 'No Subject') {
                        document.getElementById('subject').value = 'Re: ' + subject;
                    }
                    document.getElementById('message').focus();
                    
                    // Show modal
                    const composeModal = new bootstrap.Modal(document.getElementById('composeModal'));
                    composeModal.show();
                });
            });
            
            // Mark message as read
            function markAsRead(messageId) {
                const formData = new FormData();
                formData.append('message_id', messageId);
                formData.append('action', 'mark_read');
                
                fetch('messages.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    const messageCard = document.querySelector(`.message-card[data-message-id="${messageId}"]`);
                    if (messageCard) {
                        messageCard.classList.remove('unread');
                        
                        // Update unread count
                        const unreadBadge = document.querySelector('#inbox-tab .badge');
                        if (unreadBadge) {
                            let count = parseInt(unreadBadge.textContent);
                            if (count > 1) {
                                unreadBadge.textContent = count - 1;
                            } else {
                                unreadBadge.remove();
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
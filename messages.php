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

// Get users for dropdown (teachers can message students, students can message teachers, admin can message anyone)
if ($user['role'] == 'teacher') {
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role = 'student' AND status = 'approved'");
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($user['role'] == 'student') {
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE role = 'teacher' AND status = 'approved'");
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $users_stmt = $pdo->prepare("SELECT id, full_name, role FROM users WHERE status = 'approved' AND id != ?");
    $users_stmt->execute([$user['id']]);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border: #e9ecef;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .messages-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .messages-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .messages-nav {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .message-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            border-left: 4px solid var(--border);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .message-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .message-card.unread {
            border-left-color: var(--primary);
            background-color: #f0f7ff;
        }
        
        .message-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .message-info {
            flex: 1;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }
        
        .message-subject {
            font-weight: 500;
            margin-bottom: 0.2rem;
        }
        
        .message-preview {
            color: var(--gray);
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .message-details {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-message {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-message:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background-color: #e11570;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .badge-role {
            background-color: #e9ecef;
            color: var(--dark);
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-unread {
            background-color: var(--primary);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .compose-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .message-tabs .nav-link {
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .message-tabs .nav-link.active {
            background-color: var(--primary);
            color: white;
            border-radius: 8px;
        }
        
        .message-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: #f9f9f9;
            border-radius: 0 0 8px 8px;
            margin-top: -0.5rem;
        }
        
        .message-card.expanded .message-content {
            max-height: 500px;
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .message-time {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .message-card {
                flex-direction: column;
            }
            
            .action-buttons {
                margin-top: 1rem;
                justify-content: flex-end;
            }
            
            .message-details {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Messages Header -->
    <div class="messages-header">
        <div class="messages-container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0"><?= ucfirst($user['role']) ?> Dashboard</h1>
                    <p class="mb-0 opacity-75">Message Center</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 position-relative">
                        <i class="fas fa-bell me-2"></i>
                        <span class="badge bg-danger">3</span>
                    </div>
                    <div class="me-3 position-relative">
                        <i class="fas fa-envelope me-2"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="message-avatar me-2"><?= strtoupper(substr(htmlspecialchars($user['full_name']), 0, 2)) ?></div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($user['full_name']) ?></div>
                                <small><?= ucfirst($user['role']) ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="messages-container">
        <!-- Navigation -->
        <div class="messages-nav d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="messages.php"><i class="fas fa-envelope me-2"></i> Messages</a>
                    </li>
                    <?php if ($user['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php"><i class="fas fa-users me-2"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_approve.php"><i class="fas fa-user-check me-2"></i> Approvals</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="d-flex">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                    <i class="fas fa-plus me-2"></i> New Message
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= count($received_messages) ?></div>
                            <div class="text-muted">Total Messages</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $unread_count ?></div>
                            <div class="text-muted">Unread Messages</div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-envelope-open fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= count($sent_messages) ?></div>
                            <div class="text-muted">Sent Messages</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-paper-plane fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= count($users) ?></div>
                            <div class="text-muted">Available Contacts</div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-address-book fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Tabs -->
        <div class="card">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs message-tabs card-header-tabs" id="messagesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab">
                            <i class="fas fa-inbox me-2"></i> Inbox
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-primary ms-1"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                            <i class="fas fa-paper-plane me-2"></i> Sent
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="messagesTabContent">
                    <!-- Inbox Tab -->
                    <div class="tab-pane fade show active" id="inbox" role="tabpanel">
                        <?php if (empty($received_messages)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>No Messages</h4>
                                <p>Your inbox is empty. Send a message to get started!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($received_messages as $message): ?>
                            <div class="message-card <?= !$message['is_read'] ? 'unread' : '' ?>" data-message-id="<?= $message['id'] ?>">
                                <div class="d-flex flex-column flex-md-row align-items-md-center">
                                    <div class="message-avatar me-3 mb-2 mb-md-0">
                                        <?= strtoupper(substr(htmlspecialchars($message['sender_name']), 0, 2)) ?>
                                    </div>
                                    <div class="message-info">
                                        <div class="message-sender">
                                            <?= htmlspecialchars($message['sender_name']) ?>
                                            <span class="badge-role ms-2"><?= ucfirst($message['sender_role']) ?></span>
                                            <?php if (!$message['is_read']): ?>
                                                <span class="badge-unread ms-2">Unread</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-subject"><?= htmlspecialchars($message['subject'] ?: 'No Subject') ?></div>
                                        <div class="message-preview"><?= htmlspecialchars(substr($message['message'], 0, 100)) ?>...</div>
                                        <div class="message-details">
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span class="message-time"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="action-buttons ms-md-auto">
                                        <?php if (!$message['is_read']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                <button type="submit" name="action" value="mark_read" class="btn-message btn-sm">
                                                    <i class="fas fa-envelope-open me-1"></i> Mark Read
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                                <button type="submit" name="action" value="mark_unread" class="btn-message btn-sm">
                                                    <i class="fas fa-envelope me-1"></i> Mark Unread
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <button type="submit" name="action" value="delete" class="btn-delete btn-sm">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="message-content">
                                    <div class="mt-2">
                                        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                        <div class="d-flex justify-content-end mt-3">
                                            <button class="btn btn-primary btn-sm reply-btn" data-sender-id="<?= $message['sender_id'] ?>" data-sender-name="<?= htmlspecialchars($message['sender_name']) ?>">
                                                <i class="fas fa-reply me-1"></i> Reply
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sent Tab -->
                    <div class="tab-pane fade" id="sent" role="tabpanel">
                        <?php if (empty($sent_messages)): ?>
                            <div class="empty-state">
                                <i class="fas fa-paper-plane"></i>
                                <h4>No Sent Messages</h4>
                                <p>You haven't sent any messages yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sent_messages as $message): ?>
                            <div class="message-card" data-message-id="<?= $message['id'] ?>">
                                <div class="d-flex flex-column flex-md-row align-items-md-center">
                                    <div class="message-avatar me-3 mb-2 mb-md-0">
                                        <?= strtoupper(substr(htmlspecialchars($message['receiver_name']), 0, 2)) ?>
                                    </div>
                                    <div class="message-info">
                                        <div class="message-sender">
                                            To: <?= htmlspecialchars($message['receiver_name']) ?>
                                            <span class="badge-role ms-2"><?= ucfirst($message['receiver_role']) ?></span>
                                        </div>
                                        <div class="message-subject"><?= htmlspecialchars($message['subject'] ?: 'No Subject') ?></div>
                                        <div class="message-preview"><?= htmlspecialchars(substr($message['message'], 0, 100)) ?>...</div>
                                        <div class="message-details">
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span class="message-time"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="action-buttons ms-md-auto">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            <button type="submit" name="action" value="delete" class="btn-delete btn-sm">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="message-content">
                                    <div class="mt-2">
                                        <p><?= nl2br(htmlspecialchars($message['message'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="composeModalLabel">Compose New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <textarea class="form-control" id="message" name="message" rows="6" placeholder="Type your message here..." required></textarea>
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
        // Message expansion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const messageCards = document.querySelectorAll('.message-card');
            
            messageCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't toggle if clicking action buttons
                    if (e.target.closest('.action-buttons') || e.target.closest('.reply-btn')) {
                        return;
                    }
                    
                    // Toggle expanded class
                    this.classList.toggle('expanded');
                    
                    // Mark as read if it's unread and in inbox
                    if (this.classList.contains('unread') && this.closest('#inbox')) {
                        const messageId = this.getAttribute('data-message-id');
                        markAsRead(messageId);
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
                    
                    // Set the recipient in the compose modal
                    document.getElementById('receiver_id').value = senderId;
                    
                    // Set a reply subject if there's already a subject
                    const subject = this.closest('.message-card').querySelector('.message-subject').textContent;
                    if (subject && subject !== 'No Subject') {
                        document.getElementById('subject').value = 'Re: ' + subject;
                    }
                    
                    // Focus on message textarea
                    document.getElementById('message').focus();
                    
                    // Open the compose modal
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
                }).then(response => {
                    // Remove unread styling
                    const messageCard = document.querySelector(`.message-card[data-message-id="${messageId}"]`);
                    if (messageCard) {
                        messageCard.classList.remove('unread');
                        
                        // Update unread badge count
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
            
            // Search functionality
            const searchInput = document.querySelector('.search-box input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const messageCards = document.querySelectorAll('.message-card');
                    
                    messageCards.forEach(card => {
                        const senderName = card.querySelector('.message-sender').textContent.toLowerCase();
                        const subject = card.querySelector('.message-subject').textContent.toLowerCase();
                        const preview = card.querySelector('.message-preview').textContent.toLowerCase();
                        
                        if (senderName.includes(searchTerm) || subject.includes(searchTerm) || preview.includes(searchTerm)) {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
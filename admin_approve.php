<?php
include_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'] ?? '';
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id'], $user_id]);
        $_SESSION['message'] = "User approved successfully!";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected', approved_at = NOW(), approved_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id'], $user_id]);
        $_SESSION['message'] = "User rejected successfully!";
    }
    
    header("Location: admin_approve.php");
    exit;
}

// Get pending users
$stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
$stmt->execute();
$pending_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Users - Admin</title>
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
            --gray: #6c757d;
            --border: #e9ecef;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .admin-nav {
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
        
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-top: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .user-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.2rem;
        }
        
        .user-email {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .user-details {
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
        
        .btn-approve {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-approve:hover {
            background-color: #3aa8d0;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
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
        
        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .search-box {
            position: relative;
            max-width: 300px;
        }
        
        .search-box input {
            padding-left: 2.5rem;
            border-radius: 20px;
            border: 1px solid var(--border);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .filter-dropdown {
            margin-left: 1rem;
        }
        
        @media (max-width: 768px) {
            .user-card {
                flex-direction: column;
            }
            
            .action-buttons {
                margin-top: 1rem;
                justify-content: flex-end;
            }
            
            .user-details {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Admin Dashboard</h1>
                    <p class="mb-0 opacity-75">User Approval Management</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-bell me-2"></i>
                        <span class="badge bg-danger">3</span>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">AJ</div>
                            <div>
                                <div class="fw-bold">Admin User</div>
                                <small>Administrator</small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <!-- Navigation -->
        <div class="admin-nav d-flex justify-content-between align-items-center">
            <div>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_approve.php"><i class="fas fa-user-check me-2"></i> Approvals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_user.php"><i class="fas fa-users me-2"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-chart-bar me-2"></i> Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cog me-2"></i> Settings</a>
                    </li>
                </ul>
            </div>
            <div class="d-flex">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search users...">
                </div>
                <div class="filter-dropdown">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">All Users</a></li>
                            <li><a class="dropdown-item" href="#">Pending</a></li>
                            <li><a class="dropdown-item" href="#">Approved</a></li>
                            <li><a class="dropdown-item" href="#">Rejected</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= count($pending_users) ?></div>
                            <div class="text-muted">Pending Approvals</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-user-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">48</div>
                            <div class="text-muted">Total Users</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">36</div>
                            <div class="text-muted">Approved Users</div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number">5</div>
                            <div class="text-muted">Rejected Users</div>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-user-times fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-clock me-2 text-primary"></i>Pending User Approvals</h5>
                <div class="text-muted">
                    <span class="badge bg-primary"><?= count($pending_users) ?> users waiting</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pending_users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-check"></i>
                        <h4>No Pending Approvals</h4>
                        <p>All user requests have been processed.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_users as $user): ?>
                    <div class="user-card d-flex flex-column flex-md-row align-items-md-center">
                        <div class="user-avatar me-3 mb-2 mb-md-0">
                            <?= strtoupper(substr(htmlspecialchars($user['full_name']), 0, 2)) ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="user-details">
                                <div class="detail-item">
                                    <i class="fas fa-user-tag"></i>
                                    <span class="badge-role"><?= ucfirst($user['role']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-building"></i>
                                    <span><?= htmlspecialchars($user['institution'] ?? 'N/A') ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="action-buttons ms-md-auto">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn-approve">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm('Are you sure you want to reject this user?')">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-box input');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const userCards = document.querySelectorAll('.user-card');
                
                userCards.forEach(card => {
                    const userName = card.querySelector('.user-name').textContent.toLowerCase();
                    const userEmail = card.querySelector('.user-email').textContent.toLowerCase();
                    
                    if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
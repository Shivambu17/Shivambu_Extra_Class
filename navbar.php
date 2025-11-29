<?php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
$user = $_SESSION['user'];

// Get unread message count
$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
$stmt->execute([$user['id']]);
$unread_data = $stmt->fetch(PDO::FETCH_ASSOC);
$unread_count = $unread_data['unread_count'] ?? 0;
?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>Shivambu's Classes
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                
                <?php if ($user['role'] == 'teacher'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chalkboard-teacher me-1"></i>Teaching
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="create_course.php"><i class="fas fa-plus me-2"></i>Create Course</a></li>
                            <li><a class="dropdown-item" href="create_assignment.php"><i class="fas fa-tasks me-2"></i>Create Assignment</a></li>
                            <li><a class="dropdown-item" href="manage_students.php"><i class="fas fa-users me-2"></i>Manage Students</a></li>
                            <li><a class="dropdown-item" href="course_manage.php"><i class="fas fa-cog me-2"></i>Course Management</a></li>
                        </ul>
                    </li>
                <?php elseif ($user['role'] == 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="my_courses.php">
                            <i class="fas fa-book me-1"></i>My Courses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="assignments.php">
                            <i class="fas fa-tasks me-1"></i>Assignments
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-crown me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin_users.php"><i class="fas fa-users me-2"></i>Manage Users</a></li>
                            <li><a class="dropdown-item" href="admin_approve.php"><i class="fas fa-user-check me-2"></i>Approve Users</a></li>
                            <li><a class="dropdown-item" href="admin_courses.php"><i class="fas fa-book me-2"></i>Manage Courses</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link position-relative" href="messages.php">
                        <i class="fas fa-envelope me-1"></i>Messages
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unread_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['full_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
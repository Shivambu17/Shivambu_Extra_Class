<?php
include_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Initialize all variables with default values
$course_count = $assignment_count = $student_count = $user_count = $teacher_count = $pending_approvals = $message_count = $submission_count = 0;
$recent_courses = [];
$pending_users = [];

// Generate or get QR code for students
if ($user['role'] == 'student') {
    $qr_code = getOrCreateQRCode($pdo, $user['id']);
}

// Fetch user-specific data based on role with error handling
try {
    if ($user['role'] == 'student') {
        // Check if course_enrollments table exists
        $table_exists = $pdo->query("SHOW TABLES LIKE 'course_enrollments'")->rowCount() > 0;
        if ($table_exists) {
            // Get student's enrolled courses count
            $stmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM course_enrollments WHERE student_id = ?");
            $stmt->execute([$user['id']]);
            $course_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $course_count = $course_data['course_count'] ?? 0;

            // Get pending assignments count
            $assignments_table_exists = $pdo->query("SHOW TABLES LIKE 'assignments'")->rowCount() > 0;
            $submissions_table_exists = $pdo->query("SHOW TABLES LIKE 'assignment_submissions'")->rowCount() > 0;
            
            if ($assignments_table_exists && $submissions_table_exists) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as assignment_count FROM assignments a 
                                    WHERE a.course_id IN (SELECT course_id FROM course_enrollments WHERE student_id = ?) 
                                    AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)
                                    AND (a.due_date IS NULL OR a.due_date >= CURDATE())");
                $stmt->execute([$user['id'], $user['id']]);
                $assignment_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $assignment_count = $assignment_data['assignment_count'] ?? 0;
            }

            // Get recent courses for student
            $courses_table_exists = $pdo->query("SHOW TABLES LIKE 'courses'")->rowCount() > 0;
            if ($courses_table_exists) {
                $stmt = $pdo->prepare("SELECT c.*, u.full_name as teacher_name FROM courses c 
                                    JOIN course_enrollments ce ON c.id = ce.course_id 
                                    JOIN users u ON c.teacher_id = u.id
                                    WHERE ce.student_id = ? 
                                    ORDER BY ce.enrolled_at DESC 
                                    LIMIT 3");
                $stmt->execute([$user['id']]);
                $recent_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

    } elseif ($user['role'] == 'teacher') {
        // Check if courses table exists
        $courses_table_exists = $pdo->query("SHOW TABLES LIKE 'courses'")->rowCount() > 0;
        if ($courses_table_exists) {
            // Get teacher's course count
            $stmt = $pdo->prepare("SELECT COUNT(*) as course_count FROM courses WHERE teacher_id = ?");
            $stmt->execute([$user['id']]);
            $course_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $course_count = $course_data['course_count'] ?? 0;

            // Get total students
            $enrollments_table_exists = $pdo->query("SHOW TABLES LIKE 'course_enrollments'")->rowCount() > 0;
            if ($enrollments_table_exists) {
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT ce.student_id) as student_count 
                                    FROM course_enrollments ce 
                                    JOIN courses c ON ce.course_id = c.id 
                                    WHERE c.teacher_id = ?");
                $stmt->execute([$user['id']]);
                $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $student_count = $student_data['student_count'] ?? 0;
            }

            // Get pending submissions count
            $submissions_table_exists = $pdo->query("SHOW TABLES LIKE 'assignment_submissions'")->rowCount() > 0;
            $assignments_table_exists = $pdo->query("SHOW TABLES LIKE 'assignments'")->rowCount() > 0;
            
            if ($submissions_table_exists && $assignments_table_exists) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as submission_count FROM assignment_submissions asub
                                    JOIN assignments a ON asub.assignment_id = a.id
                                    WHERE a.teacher_id = ? AND asub.points_earned IS NULL");
                $stmt->execute([$user['id']]);
                $submission_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $submission_count = $submission_data['submission_count'] ?? 0;
            }

            // Get teacher's recent courses
            $stmt = $pdo->prepare("SELECT c.*, 
                                  (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as student_count
                                  FROM courses c WHERE c.teacher_id = ? ORDER BY c.created_at DESC LIMIT 3");
            $stmt->execute([$user['id']]);
            $recent_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } else {
        // Admin statistics
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $student_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'approved'")->fetchColumn();
        $teacher_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'approved'")->fetchColumn();
        
        $courses_table_exists = $pdo->query("SHOW TABLES LIKE 'courses'")->rowCount() > 0;
        if ($courses_table_exists) {
            $course_count = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        }
        
        $pending_approvals = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
        
        // Get recent pending users for admin
        $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get unread messages count (check if messages table exists)
    $messages_table_exists = $pdo->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0;
    if ($messages_table_exists) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as message_count FROM messages WHERE receiver_id = ? AND is_read = FALSE");
        $stmt->execute([$user['id']]);
        $message_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $message_count = $message_data['message_count'] ?? 0;
    }

} catch (PDOException $e) {
    // If tables don't exist yet, values remain at default 0
    error_log("Dashboard statistics error: " . $e->getMessage());
}

// Function to generate or get QR code
function getOrCreateQRCode($pdo, $user_id) {
    // Check if qr_codes table exists
    try {
        $table_exists = $pdo->query("SHOW TABLES LIKE 'qr_codes'")->rowCount() > 0;
        if (!$table_exists) {
            // Create qr_codes table
            $pdo->exec("CREATE TABLE IF NOT EXISTS qr_codes (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                qr_data TEXT NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }

        // Check if active QR code exists
        $stmt = $pdo->prepare("SELECT * FROM qr_codes WHERE user_id = ? AND is_active = TRUE AND expires_at > NOW()");
        $stmt->execute([$user_id]);
        $existing_qr = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_qr) {
            return $existing_qr;
        }
        
        // Deactivate old QR codes
        $stmt = $pdo->prepare("UPDATE qr_codes SET is_active = FALSE WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Generate new QR code
        $qr_data = json_encode([
            'student_id' => $user_id,
            'full_name' => $_SESSION['user']['full_name'],
            'email' => $_SESSION['user']['email'],
            'role' => $_SESSION['user']['role'],
            'generated_at' => time(),
            'expires_at' => time() + (30 * 24 * 60 * 60) // 30 days
        ]);
        
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("INSERT INTO qr_codes (user_id, qr_data, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $qr_data, $expires_at]);
        
        return [
            'qr_data' => $qr_data,
            'expires_at' => $expires_at,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
    } catch (PDOException $e) {
        error_log("QR code generation error: " . $e->getMessage());
        return null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Shivambu's Extra Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --gradient-primary: linear-gradient(135deg, #4361ee, #3a0ca3);
            --gradient-secondary: linear-gradient(135deg, #7209b7, #3a0ca3);
            --gradient-success: linear-gradient(135deg, #4ade80, #16a34a);
            --gradient-warning: linear-gradient(135deg, #f59e0b, #d97706);
            --gradient-info: linear-gradient(135deg, #0ea5e9, #0369a1);
            --gradient-danger: linear-gradient(135deg, #ef4444, #dc2626);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            cursor: default;
        }

        .navbar {
            background: var(--gradient-primary) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            background: var(--gradient-primary) !important;
            padding: 0.5rem 0;
        }

        .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 2px;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .dashboard-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
            border-left: 4px solid var(--primary-color);
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            clip-path: circle(20% at 90% 20%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .btn-dashboard {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .btn-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            background: var(--gradient-secondary);
            color: white;
        }

        .welcome-card {
            background: var(--gradient-primary);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .activity-item {
            padding: 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .activity-item:hover {
            transform: translateX(8px);
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
        }

        .floating-icon {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .qr-code-container {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        #qrcode {
            margin: 0 auto;
            padding: 10px;
            background: white;
            border-radius: 10px;
            display: inline-block;
        }

        .expiry-countdown {
            font-size: 0.9rem;
            color: #666;
            margin-top: 10px;
        }

        .expiry-warning {
            color: var(--danger-color);
            font-weight: bold;
        }

        footer {
            background: var(--gradient-primary) !important;
        }

        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .quick-action {
            text-align: center;
            padding: 25px 15px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .course-progress {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .course-progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .course-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .student-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold animate__animated animate__fadeInLeft" href="dashboard.php">
                <i class="fas fa-graduation-cap floating-icon"></i> Shivambu's Classes
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse animate__animated animate__fadeInRight" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    
                    <?php if ($user['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-crown me-1"></i>Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="admin_users.php"><i class="fas fa-users me-2"></i>Manage Users</a></li>
                                <li><a class="dropdown-item" href="admin_approve.php"><i class="fas fa-user-check me-2"></i>Approve Users</a></li>
                                <li><a class="dropdown-item" href="admin_courses.php"><i class="fas fa-book me-2"></i>Manage Courses</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin_reports.php"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</a></li>
                            </ul>
                        </li>
                    <?php elseif ($user['role'] == 'teacher'): ?>
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
                    <?php else: ?>
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
                        <li class="nav-item">
                            <a class="nav-link" href="schedule.php">
                                <i class="fas fa-calendar me-1"></i>My Schedule
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="messages.php">
                            <i class="fas fa-envelope me-1"></i>Messages
                            <?php if ($message_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $message_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="messages.php">
                                <i class="fas fa-envelope me-2"></i>Messages
                                <?php if ($message_count > 0): ?>
                                    <span class="badge bg-danger float-end"><?= $message_count ?></span>
                                <?php endif; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div style="height: 80px;"></div> <!-- Spacer for fixed navbar -->

    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="welcome-card animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-2">Welcome back, <?= htmlspecialchars($user['full_name']) ?>! 👋</h1>
                    <p class="mb-0" style="opacity: 0.9;">Here's what's happening in your <?= ucfirst($user['role']) ?> dashboard today.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white text-primary rounded-pill px-4 py-2 d-inline-block">
                        <i class="fas fa-user-tie me-2"></i>
                        <strong><?= ucfirst($user['role']) ?> Account</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Section for Students -->
        <?php if ($user['role'] == 'student' && isset($qr_code)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header bg-transparent border-bottom-0">
                        <h4 class="text-gradient mb-0"><i class="fas fa-qrcode me-2"></i>Your Student ID QR Code</h4>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6 text-center">
                                <div class="qr-code-container p-4">
                                    <div id="qrcode" class="mb-3"></div>
                                    <div class="student-info bg-light p-3 rounded">
                                        <h5 class="text-primary"><?= htmlspecialchars($user['full_name']) ?></h5>
                                        <p class="mb-1"><strong>Student ID:</strong> STU-<?= str_pad($user['id'], 6, '0', STR_PAD_LEFT) ?></p>
                                        <p class="mb-1"><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
                                        <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                    <div class="expiry-countdown mt-3">
                                        <i class="fas fa-clock me-1"></i>
                                        <span id="expiry-text">
                                            Expires in: <strong><?= ceil((strtotime($qr_code['expires_at']) - time()) / (60 * 60 * 24)) ?> days</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary">How to Use Your QR Code</h5>
                                <div class="list-group">
                                    <div class="list-group-item border-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Class Attendance:</strong> Show this code when entering class
                                    </div>
                                    <div class="list-group-item border-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Assignment Submission:</strong> Use for assignment verification
                                    </div>
                                    <div class="list-group-item border-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Library Access:</strong> Scan for resource borrowing
                                    </div>
                                    <div class="list-group-item border-0">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Exam Hall:</strong> Required for examination entry
                                    </div>
                                </div>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> This QR code refreshes automatically every 30 days for security. Keep it confidential.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="row mb-5">
            <?php if ($user['role'] == 'student'): ?>
                <!-- Student Statistics -->
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-success);">
                        <i class="fas fa-book stat-icon"></i>
                        <div class="stat-number"><?= $course_count ?></div>
                        <div class="stat-label">Enrolled Courses</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-warning);">
                        <i class="fas fa-tasks stat-icon"></i>
                        <div class="stat-number"><?= $assignment_count ?></div>
                        <div class="stat-label">Pending Assignments</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-info);">
                        <i class="fas fa-envelope stat-icon"></i>
                        <div class="stat-number"><?= $message_count ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-primary);">
                        <i class="fas fa-star stat-icon"></i>
                        <div class="stat-number">85%</div>
                        <div class="stat-label">Average Grade</div>
                    </div>
                </div>

            <?php elseif ($user['role'] == 'teacher'): ?>
                <!-- Teacher Statistics -->
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-success);">
                        <i class="fas fa-chalkboard-teacher stat-icon"></i>
                        <div class="stat-number"><?= $course_count ?></div>
                        <div class="stat-label">Active Courses</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-info);">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-number"><?= $student_count ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-warning);">
                        <i class="fas fa-file-alt stat-icon"></i>
                        <div class="stat-number"><?= $submission_count ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-primary);">
                        <i class="fas fa-envelope stat-icon"></i>
                        <div class="stat-number"><?= $message_count ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Admin Statistics -->
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-success);">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-number"><?= $user_count ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-info);">
                        <i class="fas fa-user-graduate stat-icon"></i>
                        <div class="stat-number"><?= $student_count ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-warning);">
                        <i class="fas fa-chalkboard-teacher stat-icon"></i>
                        <div class="stat-number"><?= $teacher_count ?></div>
                        <div class="stat-label">Teachers</div>
                    </div>
                </div>
                <div class="col-md-3 mb-4 fade-in-up">
                    <div class="stat-card" style="background: var(--gradient-danger);">
                        <i class="fas fa-clock stat-icon"></i>
                        <div class="stat-number"><?= $pending_approvals ?></div>
                        <div class="stat-label">Pending Approvals</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions & Main Content -->
        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="dashboard-card">
                    <div class="card-header bg-transparent border-bottom-0">
                        <h4 class="text-gradient mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($user['role'] == 'student'): ?>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-search quick-action-icon text-primary"></i>
                                        <h6>Find Courses</h6>
                                        <a href="courses.php" class="btn btn-primary btn-sm mt-2">Explore</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-calendar quick-action-icon text-success"></i>
                                        <h6>My Schedule</h6>
                                        <a href="schedule.php" class="btn btn-success btn-sm mt-2">View</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-file-upload quick-action-icon text-warning"></i>
                                        <h6>Submit Work</h6>
                                        <a href="assignments.php" class="btn btn-warning btn-sm mt-2">Submit</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-envelope quick-action-icon text-info"></i>
                                        <h6>Messages</h6>
                                        <a href="messages.php" class="btn btn-info btn-sm mt-2">
                                            View
                                            <?php if ($message_count > 0): ?>
                                                <span class="badge bg-danger ms-1"><?= $message_count ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>

                            <?php elseif ($user['role'] == 'teacher'): ?>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-plus quick-action-icon text-primary"></i>
                                        <h6>Create Course</h6>
                                        <a href="create_course.php" class="btn btn-primary btn-sm mt-2">Create</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-tasks quick-action-icon text-success"></i>
                                        <h6>Add Assignment</h6>
                                        <a href="create_assignment.php" class="btn btn-success btn-sm mt-2">Add</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-users quick-action-icon text-warning"></i>
                                        <h6>Manage Students</h6>
                                        <a href="manage_students.php" class="btn btn-warning btn-sm mt-2">Manage</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-envelope quick-action-icon text-info"></i>
                                        <h6>Messages</h6>
                                        <a href="messages.php" class="btn btn-info btn-sm mt-2">
                                            View
                                            <?php if ($message_count > 0): ?>
                                                <span class="badge bg-danger ms-1"><?= $message_count ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-users quick-action-icon text-primary"></i>
                                        <h6>Manage Users</h6>
                                        <a href="admin_users.php" class="btn btn-primary btn-sm mt-2">Manage</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-user-check quick-action-icon text-success"></i>
                                        <h6>Approve Users</h6>
                                        <a href="admin_approve.php" class="btn btn-success btn-sm mt-2">
                                            Approve
                                            <?php if ($pending_approvals > 0): ?>
                                                <span class="badge bg-danger ms-1"><?= $pending_approvals ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-book quick-action-icon text-warning"></i>
                                        <h6>Manage Courses</h6>
                                        <a href="admin_courses.php" class="btn btn-warning btn-sm mt-2">Manage</a>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="quick-action">
                                        <i class="fas fa-chart-bar quick-action-icon text-info"></i>
                                        <h6>View Reports</h6>
                                        <a href="admin_reports.php" class="btn btn-info btn-sm mt-2">View</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Recent Activity -->
                <div class="dashboard-card mb-4">
                    <div class="card-header bg-transparent border-bottom-0">
                        <h4 class="text-gradient mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h4>
                    </div>
                    <div class="card-body">
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 me-3">
                                    <i class="fas fa-sign-in-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Welcome to your dashboard</h6>
                                    <p class="mb-0 text-muted">You have successfully logged in to Shivambu's Extra Classes system.</p>
                                </div>
                                <small class="text-muted">Just now</small>
                            </div>
                        </div>

                        <?php if ($user['role'] == 'student'): ?>
                            <?php if ($course_count > 0): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success text-white rounded-circle p-3 me-3">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Course Enrollment</h6>
                                            <p class="mb-0 text-muted">You are enrolled in <?= $course_count ?> course(s).</p>
                                        </div>
                                        <small class="text-muted">Today</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info text-white rounded-circle p-3 me-3">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Available Courses</h6>
                                            <p class="mb-0 text-muted">Browse and enroll in available courses to get started.</p>
                                        </div>
                                        <small class="text-muted">Today</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($assignment_count > 0): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Pending Assignments</h6>
                                        <p class="mb-0 text-muted">You have <?= $assignment_count ?> assignment(s) waiting for submission.</p>
                                    </div>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($qr_code)): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">QR Code Generated</h6>
                                        <p class="mb-0 text-muted">Your access QR code has been generated and is valid for 30 days.</p>
                                    </div>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php elseif ($user['role'] == 'teacher'): ?>
                            <?php if ($course_count > 0): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success text-white rounded-circle p-3 me-3">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Active Courses</h6>
                                            <p class="mb-0 text-muted">You are teaching <?= $course_count ?> course(s) with <?= $student_count ?> total students.</p>
                                        </div>
                                        <small class="text-muted">Today</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info text-white rounded-circle p-3 me-3">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Create Your First Course</h6>
                                            <p class="mb-0 text-muted">Start by creating a course to teach students.</p>
                                        </div>
                                        <small class="text-muted">Today</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($submission_count > 0): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Pending Submissions</h6>
                                        <p class="mb-0 text-muted">You have <?= $submission_count ?> assignment submission(s) waiting for review.</p>
                                    </div>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">System Overview</h6>
                                        <p class="mb-0 text-muted">Managing <?= $user_count ?> users and <?= $course_count ?> courses.</p>
                                    </div>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <?php if ($pending_approvals > 0): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Pending Approvals</h6>
                                        <p class="mb-0 text-muted">You have <?= $pending_approvals ?> user(s) waiting for approval.</p>
                                    </div>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Courses/Users Section -->
                <div class="dashboard-card">
                    <div class="card-header bg-transparent border-bottom-0">
                        <h4 class="text-gradient mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= $user['role'] == 'student' ? 'My Courses' : ($user['role'] == 'teacher' ? 'My Courses' : 'Pending Approvals') ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($user['role'] == 'student'): ?>
                            <?php if (!empty($recent_courses)): ?>
                                <div class="row">
                                    <?php foreach ($recent_courses as $course): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="course-card">
                                                <h6 class="text-primary"><?= htmlspecialchars($course['title'] ?? 'Untitled Course') ?></h6>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($course['category'] ?? 'General') ?></p>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-chalkboard-teacher me-1"></i>
                                                    <?= htmlspecialchars($course['teacher_name'] ?? 'Unknown Teacher') ?>
                                                </p>
                                                <div class="course-progress mb-2">
                                                    <div class="course-progress-bar bg-success" style="width: <?= rand(30, 90) ?>%"></div>
                                                </div>
                                                <small class="text-muted">Progress: <?= rand(30, 90) ?>%</small>
                                                <div class="mt-2">
                                                    <a href="course_content.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary">View Course</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($course_count > 3): ?>
                                    <div class="text-center mt-3">
                                        <a href="my_courses.php" class="btn btn-primary btn-sm">View All Courses</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Courses Enrolled</h5>
                                    <p class="text-muted">You haven't enrolled in any courses yet.</p>
                                    <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($user['role'] == 'teacher'): ?>
                            <?php if (!empty($recent_courses)): ?>
                                <div class="list-group">
                                    <?php foreach ($recent_courses as $course): ?>
                                        <div class="list-group-item border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($course['title'] ?? 'Untitled Course') ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($course['category'] ?? 'General') ?> • 
                                                        <?= $course['student_count'] ?? 0 ?> students
                                                    </small>
                                                </div>
                                                <div>
                                                    <a href="course_manage.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($course_count > 3): ?>
                                    <div class="text-center mt-3">
                                        <a href="course_manage.php" class="btn btn-primary btn-sm">View All Courses</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Courses Created</h5>
                                    <p class="text-muted">You haven't created any courses yet.</p>
                                    <a href="create_course.php" class="btn btn-primary">Create Your First Course</a>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <?php if (!empty($pending_users)): ?>
                                <div class="list-group">
                                    <?php foreach ($pending_users as $pending_user): ?>
                                        <div class="list-group-item border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($pending_user['full_name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($pending_user['email']) ?> • <?= ucfirst($pending_user['role']) ?></small>
                                                    <br>
                                                    <small class="text-muted">
                                                        Registered: <?= date('M j, Y', strtotime($pending_user['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <a href="admin_approve.php" class="btn btn-sm btn-success">Review</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($pending_approvals > 3): ?>
                                    <div class="text-center mt-3">
                                        <a href="admin_approve.php" class="btn btn-primary btn-sm">View All Pending Approvals</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Pending Approvals</h5>
                                    <p class="text-muted">All user registrations have been processed.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-2">&copy; 2025 Shivambu's Extra Class. All rights reserved.</p>
            <p class="mb-0">+27 69 270 2761 | support@shivambu.com</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in-up');
            
            const fadeInOnScroll = function() {
                fadeElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('visible');
                    }
                });
            };

            // Initial check
            fadeInOnScroll();
            
            // Check on scroll
            window.addEventListener('scroll', fadeInOnScroll);

            // Add loading animation to buttons
            const buttons = document.querySelectorAll('.btn-dashboard, .quick-action .btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }, 2000);
                    }
                });
            });

            // Generate QR Code for students
            <?php if ($user['role'] == 'student' && isset($qr_code)): ?>
            const qrData = '<?= addslashes($qr_code['qr_data']) ?>';
            const qrElement = document.getElementById('qrcode');
            
            if (qrElement && qrData) {
                new QRCode(qrElement, {
                    text: qrData,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }

            // Countdown timer for QR code expiry
            const expiryDate = new Date('<?= $qr_code['expires_at'] ?>').getTime();
            function updateCountdown() {
                const now = new Date().getTime();
                const distance = expiryDate - now;
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                
                const expiryText = document.getElementById('expiry-text');
                if (expiryText) {
                    if (distance < 0) {
                        expiryText.innerHTML = 'QR Code has expired!';
                        expiryText.className = 'expiry-warning';
                    } else if (days < 7) {
                        expiryText.innerHTML = `Expires in: <strong class="expiry-warning">${days}d ${hours}h</strong>`;
                    } else {
                        expiryText.innerHTML = `Expires in: <strong>${days} days</strong>`;
                    }
                }
            }
            
            updateCountdown();
            setInterval(updateCountdown, 3600000); // Update every hour
            <?php endif; ?>
        });
    </script>
</body>
</html>
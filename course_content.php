<?php
include_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$course_id = $_GET['id'] ?? '';

if (empty($course_id)) {
    header("Location: dashboard.php");
    exit;
}

// Get course details
$stmt = $pdo->prepare("SELECT c.*, u.full_name as teacher_name FROM courses c 
                      JOIN users u ON c.teacher_id = u.id 
                      WHERE c.id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: dashboard.php");
    exit;
}

// For students, check if enrolled
if ($user['role'] == 'student') {
    $stmt = $pdo->prepare("SELECT * FROM course_enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->execute([$course_id, $user['id']]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        $_SESSION['error'] = "You are not enrolled in this course";
        header("Location: my_courses.php");
        exit;
    }
}

// For teachers, check if they own the course
if ($user['role'] == 'teacher' && $course['teacher_id'] != $user['id']) {
    $_SESSION['error'] = "Access denied";
    header("Location: dashboard.php");
    exit;
}

// Get course assignments
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
$stmt->execute([$course_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course announcements
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE course_id = ? ORDER BY created_at DESC");
$stmt->execute([$course_id]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['course_name']) ?> - Course Content</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
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
        
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <!-- Course Header -->
        <div class="dashboard-card p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="text-primary"><?= htmlspecialchars($course['course_name']) ?></h2>
                    <p class="text-muted mb-2"><?= htmlspecialchars($course['category']) ?></p>
                    <p class="mb-0">Taught by: <strong><?= htmlspecialchars($course['teacher_name']) ?></strong></p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($user['role'] == 'teacher'): ?>
                        <a href="course_manage.php?id=<?= $course_id ?>" class="btn btn-primary">
                            <i class="fas fa-cog me-2"></i>Manage Course
                        </a>
                    <?php endif; ?>
                    <a href="<?= $user['role'] == 'student' ? 'my_courses.php' : 'dashboard.php' ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Course Description -->
            <div class="col-lg-8">
                <div class="dashboard-card p-4 mb-4">
                    <h4 class="mb-4">Course Description</h4>
                    <p><?= nl2br(htmlspecialchars($course['course_description'])) ?></p>
                </div>

                <!-- Announcements -->
                <div class="dashboard-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Announcements</h4>
                        <?php if ($user['role'] == 'teacher'): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                <i class="fas fa-plus me-2"></i>New Announcement
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($announcements)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No announcements yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="content-card">
                                <h6><?= htmlspecialchars($announcement['title']) ?></h6>
                                <p class="mb-2"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                                <small class="text-muted">
                                    Posted: <?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Assignments -->
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Assignments</h4>
                    
                    <?php if (empty($assignments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No assignments yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><?= htmlspecialchars($assignment['title']) ?></h6>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($assignment['assignment_type']) ?> • <?= $assignment['max_points'] ?> points</p>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars(substr($assignment['description'], 0, 200))) ?>...</p>
                                        <small class="text-muted">
                                            Due: <?= date('M j, Y g:i A', strtotime($assignment['due_date'])) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if ($user['role'] == 'student'): ?>
                                            <a href="view_assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php else: ?>
                                            <a href="assignment_submissions.php?assignment_id=<?= $assignment['id'] ?>" class="btn btn-sm btn-primary">Submissions</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="dashboard-card p-4">
                    <h5 class="mb-4">Course Information</h5>
                    
                    <div class="mb-3">
                        <strong>Course Category:</strong>
                        <p class="text-muted"><?= htmlspecialchars($course['category']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Instructor:</strong>
                        <p class="text-muted"><?= htmlspecialchars($course['teacher_name']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Maximum Students:</strong>
                        <p class="text-muted"><?= $course['max_students'] ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Course Status:</strong>
                        <p class="text-muted">
                            <span class="badge <?= $course['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $course['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Created:</strong>
                        <p class="text-muted"><?= date('M j, Y', strtotime($course['created_at'])) ?></p>
                    </div>
                    
                    <?php if ($user['role'] == 'teacher'): ?>
                        <div class="mt-4">
                            <a href="create_assignment.php?course_id=<?= $course_id ?>" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-plus me-2"></i>Add Assignment
                            </a>
                            <a href="manage_students.php?course_id=<?= $course_id ?>" class="btn btn-warning w-100">
                                <i class="fas fa-users me-2"></i>Manage Students
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcement Modal for Teachers -->
    <?php if ($user['role'] == 'teacher'): ?>
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="create_announcement.php">
                    <div class="modal-body">
                        <input type="hidden" name="course_id" value="<?= $course_id ?>">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Get all courses with teacher information
$stmt = $pdo->prepare("SELECT c.*, u.full_name as teacher_name, 
                      (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as enrolled_students
                      FROM courses c 
                      JOIN users u ON c.teacher_id = u.id 
                      ORDER BY c.created_at DESC");
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle course actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    
    if ($action === 'toggle_active' && !empty($course_id)) {
        $stmt = $pdo->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$course_id]);
        $_SESSION['message'] = "Course status updated successfully!";
        header("Location: admin_courses.php");
        exit;
    } elseif ($action === 'delete' && !empty($course_id)) {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $_SESSION['message'] = "Course deleted successfully!";
        header("Location: admin_courses.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-book me-2"></i>Manage Courses</h2>
                <p class="text-muted">Manage all courses in the system</p>
            </div>
        </div>

        <!-- Courses Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Courses (<?= count($courses) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($courses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No courses found in the system.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Teacher</th>
                                    <th>Category</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($course['title']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($course['description'] ?? '', 0, 50)) ?>...</small>
                                        </td>
                                        <td><?= htmlspecialchars($course['teacher_name']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($course['category'] ?? 'General') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $course['enrolled_students'] ?> enrolled</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $course['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $course['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($course['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                    <button type="submit" name="action" value="toggle_active" class="btn btn-<?= $course['is_active'] ? 'warning' : 'success' ?>">
                                                        <i class="fas fa-<?= $course['is_active'] ? 'pause' : 'play' ?>"></i>
                                                    </button>
                                                </form>
                                                <a href="course_manage.php?id=<?= $course['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
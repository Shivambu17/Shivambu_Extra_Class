<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Get student's enrolled courses
$stmt = $pdo->prepare("SELECT c.*, ce.enrolled_at FROM courses c 
                      JOIN course_enrollments ce ON c.id = ce.course_id 
                      WHERE ce.student_id = ? 
                      ORDER BY ce.enrolled_at DESC");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available courses for enrollment
$stmt = $pdo->prepare("SELECT * FROM courses 
                      WHERE id NOT IN (SELECT course_id FROM course_enrollments WHERE student_id = ?)
                      AND is_active = TRUE
                      ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Dashboard</title>
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
        
        .course-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-book me-2"></i>My Courses</h2>
                    <a href="courses.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Browse Courses
                    </a>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="dashboard-card p-4 mb-4">
            <h4 class="mb-4">My Enrolled Courses (<?= count($courses) ?>)</h4>
            
            <?php if (empty($courses)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Courses Enrolled</h4>
                    <p class="text-muted">You haven't enrolled in any courses yet.</p>
                    <a href="courses.php" class="btn btn-primary">Browse Available Courses</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-md-6 mb-4">
                            <div class="course-card">
                                <h5 class="text-primary"><?= htmlspecialchars($course['title'] ?? 'Untitled Course') ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($course['category'] ?? 'General') ?></p>
                                <p class="mb-3"><?= nl2br(htmlspecialchars(substr($course['description'] ?? 'No description available', 0, 150))) ?>...</p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Enrolled: <?= date('M j, Y', strtotime($course['enrolled_at'])) ?>
                                    </small>
                                    <div>
                                        <a href="course_content.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i>View Course
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Courses -->
        <div class="dashboard-card p-4">
            <h4 class="mb-4">Available Courses (<?= count($available_courses) ?>)</h4>
            
            <?php if (empty($available_courses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No additional courses available for enrollment.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($available_courses as $course): ?>
                        <div class="col-md-6 mb-3">
                            <div class="course-card">
                                <h5 class="text-primary"><?= htmlspecialchars($course['title'] ?? 'Untitled Course') ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($course['category'] ?? 'General') ?></p>
                                <p class="mb-3"><?= nl2br(htmlspecialchars(substr($course['description'] ?? 'No description available', 0, 150))) ?>...</p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Max Students: <?= htmlspecialchars($course['max_students'] ?? '30') ?>
                                    </small>
                                    <form method="POST" action="enroll_course.php">
                                        <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-user-plus me-1"></i>Enroll
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
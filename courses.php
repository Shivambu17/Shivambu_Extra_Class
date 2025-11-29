<?php
include_once 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Get all available courses
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT c.*, u.full_name as teacher_name, 
          (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as enrolled_students
          FROM courses c 
          JOIN users u ON c.teacher_id = u.id 
          WHERE c.is_active = TRUE";

$params = [];

if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ? OR u.full_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category) && $category !== 'all') {
    $query .= " AND c.category = ?";
    $params[] = $category;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique categories for filter
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM courses WHERE category IS NOT NULL AND category != ''");
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Check if student is already enrolled in courses
$enrolled_courses = [];
if ($user['role'] == 'student') {
    $stmt = $pdo->prepare("SELECT course_id FROM course_enrollments WHERE student_id = ?");
    $stmt->execute([$user['id']]);
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - <?= ucfirst($user['role']) ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .course-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .category-badge {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-search me-2"></i>Browse Courses</h2>
                <p class="text-muted">Discover and enroll in available courses</p>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search courses..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-4">
                <select class="form-select" onchange="window.location.href = 'courses.php?category=' + this.value">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="row">
            <?php if (empty($courses)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Courses Found</h4>
                    <p class="text-muted"><?= !empty($search) ? 'Try adjusting your search criteria' : 'No courses are available at the moment' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 course-card">
                            <div class="card-body">
                                <h5 class="card-title text-primary">
                                    <?= htmlspecialchars($course['title'] ?? $course['course_name'] ?? 'Untitled Course') ?>
                                </h5>
                                
                                <div class="mb-2">
                                    <span class="badge category-badge">
                                        <?= htmlspecialchars($course['category'] ?? 'General') ?>
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted">
                                    <?= htmlspecialchars(substr($course['description'] ?? $course['course_description'] ?? 'No description available', 0, 100)) ?>
                                    <?= strlen($course['description'] ?? $course['course_description'] ?? '') > 100 ? '...' : '' ?>
                                </p>
                                
                                <div class="course-meta mb-3">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?= htmlspecialchars($course['teacher_name'] ?? 'Unknown Teacher') ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-users me-1"></i>
                                        <?= $course['enrolled_students'] ?? 0 ?> students enrolled
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user-graduate me-1"></i>
                                        Level: <?= htmlspecialchars($course['course_level'] ?? 'Beginner') ?>
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($course['max_students'] ?? 30) ?> max students
                                    </span>
                                    
                                    <?php if ($user['role'] == 'student'): ?>
                                        <?php if (in_array($course['id'], $enrolled_courses)): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Enrolled
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" action="enroll_course.php" class="d-inline">
                                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-user-plus me-1"></i>Enroll
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Created: <?= date('M j, Y', strtotime($course['created_at'] ?? 'now')) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
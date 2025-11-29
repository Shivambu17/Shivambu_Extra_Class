<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$course_id = $_GET['id'] ?? '';

if (empty($course_id)) {
    header("Location: create_course.php");
    exit;
}

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $user['id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    $_SESSION['error'] = "Course not found or access denied";
    header("Location: create_course.php");
    exit;
}

// Handle course updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_course') {
        $course_name = $_POST['course_name'] ?? '';
        $course_description = $_POST['course_description'] ?? '';
        $course_category = $_POST['course_category'] ?? '';
        $max_students = $_POST['max_students'] ?? 30;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ?, category = ?, max_students = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$course_name, $course_description, $course_category, $max_students, $is_active, $course_id]);
        $_SESSION['message'] = "Course updated successfully!";
        header("Location: course_manage.php?id=" . $course_id);
        exit;
    }
    
    // Handle student enrollment
    if (isset($_POST['action']) && $_POST['action'] == 'enroll_student') {
        $student_id = $_POST['student_id'] ?? '';
        if (!empty($student_id)) {
            // Check if already enrolled
            $check_stmt = $pdo->prepare("SELECT * FROM course_enrollments WHERE course_id = ? AND student_id = ?");
            $check_stmt->execute([$course_id, $student_id]);
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO course_enrollments (course_id, student_id, enrolled_at) VALUES (?, ?, NOW())");
                $stmt->execute([$course_id, $student_id]);
                $_SESSION['message'] = "Student enrolled successfully!";
            } else {
                $_SESSION['error'] = "Student is already enrolled in this course";
            }
        }
        header("Location: course_manage.php?id=" . $course_id);
        exit;
    }
    
    // Handle remove student
    if (isset($_POST['action']) && $_POST['action'] == 'remove_student') {
        $enrollment_id = $_POST['enrollment_id'] ?? '';
        if (!empty($enrollment_id)) {
            $stmt = $pdo->prepare("DELETE FROM course_enrollments WHERE id = ? AND course_id = ?");
            $stmt->execute([$enrollment_id, $course_id]);
            $_SESSION['message'] = "Student removed from course!";
        }
        header("Location: course_manage.php?id=" . $course_id);
        exit;
    }
}

// Get enrolled students
$stmt = $pdo->prepare("SELECT ce.*, u.full_name, u.email FROM course_enrollments ce 
                      JOIN users u ON ce.student_id = u.id 
                      WHERE ce.course_id = ? ORDER BY ce.enrolled_at DESC");
$stmt->execute([$course_id]);
$enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available students for enrollment
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND status = 'approved' 
                      AND id NOT IN (SELECT student_id FROM course_enrollments WHERE course_id = ?)");
$stmt->execute([$course_id]);
$available_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get course assignments count
$stmt = $pdo->prepare("SELECT COUNT(*) as assignment_count FROM assignments WHERE course_id = ?");
$stmt->execute([$course_id]);
$assignment_count = $stmt->fetch(PDO::FETCH_ASSOC)['assignment_count'];

// Get course announcements count
$stmt = $pdo->prepare("SELECT COUNT(*) as announcement_count FROM announcements WHERE course_id = ?");
$stmt->execute([$course_id]);
$announcement_count = $stmt->fetch(PDO::FETCH_ASSOC)['announcement_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?= htmlspecialchars($course['course_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
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
        
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            color: white;
            margin-bottom: 1rem;
        }
        
        .student-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
        }
        
        .nav-tabs .nav-link.active {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
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
                        <h2><i class="fas fa-cog me-2"></i>Manage Course</h2>
                        <h4 class="text-primary"><?= htmlspecialchars($course['course_name']) ?></h4>
                    </div>
                    <div>
                        <a href="create_course.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Courses
                        </a>
                        <a href="course_content.php?id=<?= $course_id ?>" class="btn btn-primary">
                            <i class="fas fa-file-alt me-2"></i>Course Content
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: var(--primary);">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h4><?= count($enrolled_students) ?></h4>
                    <p class="mb-0">Enrolled Students</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: var(--success);">
                    <i class="fas fa-tasks fa-2x mb-2"></i>
                    <h4><?= $assignment_count ?></h4>
                    <p class="mb-0">Assignments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: var(--secondary);">
                    <i class="fas fa-bullhorn fa-2x mb-2"></i>
                    <h4><?= $announcement_count ?></h4>
                    <p class="mb-0">Announcements</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: #6c757d;">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h4><?= $course['max_students'] ?></h4>
                    <p class="mb-0">Max Capacity</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="courseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button">
                    <i class="fas fa-edit me-2"></i>Course Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button">
                    <i class="fas fa-users me-2"></i>Students (<?= count($enrolled_students) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="enroll-tab" data-bs-toggle="tab" data-bs-target="#enroll" type="button">
                    <i class="fas fa-user-plus me-2"></i>Enroll Students
                </button>
            </li>
        </ul>

        <div class="tab-content" id="courseTabsContent">
            <!-- Edit Course Tab -->
            <div class="tab-pane fade show active" id="edit" role="tabpanel">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Course Information</h4>
                    <form method="POST" action="course_manage.php?id=<?= $course_id ?>">
                        <input type="hidden" name="action" value="update_course">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="course_name" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" 
                                       value="<?= htmlspecialchars($course['course_name']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="course_category" class="form-label">Category</label>
                                <select class="form-select" id="course_category" name="course_category">
                                    <option value="Engineering" <?= $course['category'] == 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                    <option value="IT" <?= $course['category'] == 'IT' ? 'selected' : '' ?>>Information Technology</option>
                                    <option value="Business" <?= $course['category'] == 'Business' ? 'selected' : '' ?>>Business</option>
                                    <option value="Science" <?= $course['category'] == 'Science' ? 'selected' : '' ?>>Science</option>
                                    <option value="Mathematics" <?= $course['category'] == 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                                    <option value="Other" <?= $course['category'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_students" class="form-label">Maximum Students</label>
                                <input type="number" class="form-control" id="max_students" name="max_students" 
                                       value="<?= $course['max_students'] ?>" min="1" max="100">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= $course['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active Course
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_description" class="form-label">Course Description</label>
                            <textarea class="form-control" id="course_description" name="course_description" 
                                      rows="6" required><?= htmlspecialchars($course['course_description']) ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="create_assignment.php?course_id=<?= $course_id ?>" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Add Assignment
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Tab -->
            <div class="tab-pane fade" id="students" role="tabpanel">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Enrolled Students</h4>
                    
                    <?php if (empty($enrolled_students)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No students enrolled in this course yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($enrolled_students as $student): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="student-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($student['full_name']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                                <br>
                                                <small class="text-muted">
                                                    Enrolled: <?= date('M j, Y', strtotime($student['enrolled_at'])) ?>
                                                </small>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="manage_students.php?course_id=<?= $course_id ?>&student_id=<?= $student['student_id'] ?>">
                                                            <i class="fas fa-chart-line me-2"></i>View Progress
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="remove_student">
                                                            <input type="hidden" name="enrollment_id" value="<?= $student['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger" 
                                                                    onclick="return confirm('Are you sure you want to remove this student?')">
                                                                <i class="fas fa-user-times me-2"></i>Remove
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enroll Students Tab -->
            <div class="tab-pane fade" id="enroll" role="tabpanel">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Enroll New Students</h4>
                    
                    <?php if (empty($available_students)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                            <p class="text-muted">All available students are already enrolled in this course.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="course_manage.php?id=<?= $course_id ?>" class="mb-4">
                            <input type="hidden" name="action" value="enroll_student">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="student_id" class="form-label">Select Student</label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">Choose a student...</option>
                                        <?php foreach ($available_students as $student): ?>
                                            <option value="<?= $student['id'] ?>">
                                                <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-user-plus me-2"></i>Enroll Student
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <h5>Available Students</h5>
                        <div class="row">
                            <?php foreach ($available_students as $student): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="border rounded p-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($student['full_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
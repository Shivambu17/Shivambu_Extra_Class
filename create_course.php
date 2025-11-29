<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Initialize courses array
$courses = [];

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_course') {
    $course_name = $_POST['course_name'] ?? '';
    $course_description = $_POST['course_description'] ?? '';
    $course_category = $_POST['course_category'] ?? '';
    $max_students = $_POST['max_students'] ?? 30;
    
    if (!empty($course_name) && !empty($course_description)) {
        try {
            // Check if courses table exists, if not create it
            $table_exists = $pdo->query("SHOW TABLES LIKE 'courses'")->rowCount() > 0;
            
            if (!$table_exists) {
                // Create courses table
                $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    teacher_id INT NOT NULL,
                    course_name VARCHAR(255) NOT NULL,
                    course_description TEXT,
                    category VARCHAR(100),
                    max_students INT DEFAULT 30,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
                )");
            }
            
            $stmt = $pdo->prepare("INSERT INTO courses (teacher_id, course_name, course_description, category, max_students, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user['id'], $course_name, $course_description, $course_category, $max_students]);
            $course_id = $pdo->lastInsertId();
            
            $_SESSION['message'] = "Course created successfully!";
            header("Location: course_manage.php?id=" . $course_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating course: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields";
    }
}

// Get teacher's existing courses (with error handling)
try {
    $table_exists = $pdo->query("SHOW TABLES LIKE 'courses'")->rowCount() > 0;
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Table doesn't exist or other error - courses array remains empty
    $courses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - Teacher Dashboard</title>
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
        
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-plus-circle me-2"></i>Create New Course</h2>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Course Creation Form -->
            <div class="col-lg-6">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Course Information</h4>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="create_course.php">
                        <input type="hidden" name="action" value="create_course">
                        
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name *</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required 
                                   placeholder="e.g., Advanced Programming">
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_category" class="form-label">Category</label>
                            <select class="form-select" id="course_category" name="course_category">
                                <option value="Engineering">Engineering</option>
                                <option value="IT">Information Technology</option>
                                <option value="Business">Business</option>
                                <option value="Science">Science</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_students" class="form-label">Maximum Students</label>
                            <input type="number" class="form-control" id="max_students" name="max_students" 
                                   value="30" min="1" max="100">
                        </div>
                        
                        <div class="mb-3">
                            <label for="course_description" class="form-label">Course Description *</label>
                            <textarea class="form-control" id="course_description" name="course_description" 
                                      rows="5" required placeholder="Describe the course content, objectives, and requirements..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Create Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Courses -->
            <div class="col-lg-6">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Your Existing Courses</h4>
                    
                    <?php if (empty($courses)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't created any courses yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-2"><?= htmlspecialchars($course['course_name']) ?></h5>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($course['category']) ?></p>
                                        <small class="text-muted">
                                            Created: <?= date('M j, Y', strtotime($course['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="course_manage.php?id=<?= $course['id'] ?>">
                                                    <i class="fas fa-edit me-2"></i>Manage
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="course_content.php?id=<?= $course['id'] ?>">
                                                    <i class="fas fa-file-alt me-2"></i>Content
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
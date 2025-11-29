<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$course_id = $_GET['course_id'] ?? '';

// Get teacher's courses for dropdown
$stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ? ORDER BY course_name");
$stmt->execute([$user['id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_assignment') {
    $course_id = $_POST['course_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $max_points = $_POST['max_points'] ?? 100;
    $assignment_type = $_POST['assignment_type'] ?? 'homework';
    
    if (!empty($course_id) && !empty($title) && !empty($due_date)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO assignments (course_id, teacher_id, title, description, due_date, max_points, assignment_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$course_id, $user['id'], $title, $description, $due_date, $max_points, $assignment_type]);
            
            $_SESSION['message'] = "Assignment created successfully!";
            header("Location: create_assignment.php?course_id=" . $course_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating assignment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill in all required fields";
    }
}

// Get existing assignments for the selected course
if (!empty($course_id)) {
    $stmt = $pdo->prepare("SELECT a.*, c.course_name FROM assignments a 
                          JOIN courses c ON a.course_id = c.id 
                          WHERE a.course_id = ? 
                          ORDER BY a.due_date DESC");
    $stmt->execute([$course_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - Teacher Dashboard</title>
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
        
        .assignment-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .assignment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
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
        
        .due-date-warning {
            border-left: 4px solid var(--danger);
        }
        
        .due-date-normal {
            border-left: 4px solid var(--success);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tasks me-2"></i>Create Assignment</h2>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
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

        <div class="row">
            <!-- Assignment Creation Form -->
            <div class="col-lg-6">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Assignment Details</h4>

                    <form method="POST" action="create_assignment.php">
                        <input type="hidden" name="action" value="create_assignment">
                        
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Select Course *</label>
                            <select class="form-select" id="course_id" name="course_id" required 
                                    onchange="window.location.href = 'create_assignment.php?course_id=' + this.value">
                                <option value="">Choose a course...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>" 
                                        <?= ($course_id == $course['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Assignment Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   placeholder="e.g., Programming Assignment 1">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignment_type" class="form-label">Assignment Type</label>
                                <select class="form-select" id="assignment_type" name="assignment_type">
                                    <option value="homework">Homework</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="test">Test</option>
                                    <option value="project">Project</option>
                                    <option value="lab">Lab Work</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="max_points" class="form-label">Maximum Points</label>
                                <input type="number" class="form-control" id="max_points" name="max_points" 
                                       value="100" min="1" max="1000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date *</label>
                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" required 
                                   min="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Assignment Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="6" required 
                                      placeholder="Provide detailed instructions for the assignment..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" 
                                    <?= empty($course_id) ? 'disabled' : '' ?>>
                                <i class="fas fa-save me-2"></i>Create Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Assignments -->
            <div class="col-lg-6">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">
                        <?php if (!empty($course_id)): ?>
                            Assignments for <?= htmlspecialchars($courses[array_search($course_id, array_column($courses, 'id'))]['course_name'] ?? 'Selected Course') ?>
                        <?php else: ?>
                            Your Assignments
                        <?php endif; ?>
                    </h4>
                    
                    <?php if (empty($course_id)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Select a course to view assignments.</p>
                        </div>
                    <?php elseif (empty($assignments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No assignments created for this course yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php
                            $due_date = new DateTime($assignment['due_date']);
                            $now = new DateTime();
                            $is_overdue = $due_date < $now;
                            ?>
                            
                            <div class="assignment-card <?= $is_overdue ? 'due-date-warning' : 'due-date-normal' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-2"><?= htmlspecialchars($assignment['title']) ?></h5>
                                        <p class="text-muted mb-2"><?= htmlspecialchars($assignment['assignment_type']) ?> • <?= $assignment['max_points'] ?> points</p>
                                        <p class="mb-2"><?= nl2br(htmlspecialchars(substr($assignment['description'], 0, 150))) ?>...</p>
                                        <div class="d-flex gap-3 text-sm">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Due: <?= date('M j, Y g:i A', strtotime($assignment['due_date'])) ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-book me-1"></i>
                                                <?= htmlspecialchars($assignment['course_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="dropdown ms-2">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="assignment_submissions.php?assignment_id=<?= $assignment['id'] ?>">
                                                    <i class="fas fa-eye me-2"></i>View Submissions
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="edit_assignment.php?id=<?= $assignment['id'] ?>">
                                                    <i class="fas fa-edit me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="delete_assignment.php?id=<?= $assignment['id'] ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this assignment?')">
                                                    <i class="fas fa-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <?php if ($is_overdue): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum datetime for due date to current time
        document.addEventListener('DOMContentLoaded', function() {
            const dueDateInput = document.getElementById('due_date');
            if (dueDateInput) {
                const now = new Date();
                now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                dueDateInput.min = now.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>
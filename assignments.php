<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Get student's assignments
try {
    $stmt = $pdo->prepare("SELECT a.*, c.title as course_title, 
                          asub.id as submission_id, asub.submitted_at, asub.grade,
                          asub.status as submission_status
                          FROM assignments a
                          JOIN courses c ON a.course_id = c.id
                          JOIN course_enrollments ce ON c.id = ce.course_id
                          LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                          WHERE ce.student_id = ?
                          ORDER BY a.due_date ASC");
    $stmt->execute([$user['id'], $user['id']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching assignments: " . $e->getMessage());
    $assignments = [];
}

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_assignment') {
    $assignment_id = $_POST['assignment_id'] ?? '';
    $submission_text = $_POST['submission_text'] ?? '';
    
    if (!empty($assignment_id) && !empty($submission_text)) {
        try {
            // Check if already submitted
            $check_stmt = $pdo->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
            $check_stmt->execute([$assignment_id, $user['id']]);
            
            if ($check_stmt->fetch()) {
                // Update existing submission
                $stmt = $pdo->prepare("UPDATE assignment_submissions SET submission_text = ?, submitted_at = NOW(), status = 'submitted' WHERE assignment_id = ? AND student_id = ?");
                $stmt->execute([$submission_text, $assignment_id, $user['id']]);
            } else {
                // Create new submission
                $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, status) VALUES (?, ?, ?, 'submitted')");
                $stmt->execute([$assignment_id, $user['id'], $submission_text]);
            }
            
            $_SESSION['message'] = "Assignment submitted successfully!";
            header("Location: assignments.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error submitting assignment: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please provide your submission content";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .assignment-card {
            border-left: 4px solid #4361ee;
            transition: all 0.3s ease;
        }
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .submitted-badge {
            background: linear-gradient(135deg, #4ade80, #16a34a);
        }
        .pending-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .graded-badge {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-tasks me-2"></i>My Assignments</h2>
                <p class="text-muted">View and submit your course assignments</p>
            </div>
        </div>

        <!-- Assignment List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($assignments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tasks fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Assignments</h4>
                        <p class="text-muted">You don't have any assignments yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="card mb-3 assignment-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="card-title text-primary"><?= htmlspecialchars($assignment['title'] ?? 'Untitled Assignment') ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($assignment['description'] ?? 'No description available') ?></p>
                                        <div class="assignment-meta">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-book me-1"></i>
                                                <?= htmlspecialchars($assignment['course_title'] ?? 'Unknown Course') ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-clock me-1"></i>
                                                Due: <?= date('M j, Y g:i A', strtotime($assignment['due_date'] ?? 'now')) ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-star me-1"></i>
                                                Max Points: <?= $assignment['max_points'] ?? 100 ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <?php if ($assignment['submission_id']): ?>
                                            <?php if ($assignment['grade'] !== null): ?>
                                                <span class="badge graded-badge">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Graded: <?= $assignment['grade'] ?>/<?= $assignment['max_points'] ?? 100 ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge submitted-badge">
                                                    <i class="fas fa-check me-1"></i>
                                                    Submitted
                                                </span>
                                            <?php endif; ?>
                                            <small class="d-block text-muted mt-1">
                                                On: <?= date('M j, Y', strtotime($assignment['submitted_at'] ?? 'now')) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge pending-badge">
                                                <i class="fas fa-clock me-1"></i>
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#submitModal<?= $assignment['id'] ?>">
                                            <i class="fas fa-file-upload me-1"></i>
                                            <?= $assignment['submission_id'] ? 'Resubmit' : 'Submit' ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submission Modal -->
                        <div class="modal fade" id="submitModal<?= $assignment['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Submit Assignment: <?= htmlspecialchars($assignment['title'] ?? 'Untitled Assignment') ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="submit_assignment">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Assignment Description</label>
                                                <p class="form-control-plaintext"><?= nl2br(htmlspecialchars($assignment['description'] ?? 'No description available')) ?></p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="submission_text<?= $assignment['id'] ?>" class="form-label">Your Submission</label>
                                                <textarea class="form-control" id="submission_text<?= $assignment['id'] ?>" name="submission_text" rows="8" placeholder="Type your assignment submission here..." required><?= $assignment['submission_text'] ?? '' ?></textarea>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Make sure to submit before: <strong><?= date('M j, Y g:i A', strtotime($assignment['due_date'] ?? 'now')) ?></strong>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Submit Assignment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
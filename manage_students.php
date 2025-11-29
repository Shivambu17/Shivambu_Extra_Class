<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'teacher' && $_SESSION['user']['role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$course_id = $_GET['course_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';

// For teachers, verify they own the course
if ($user['role'] == 'teacher' && !empty($course_id)) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $user['id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        $_SESSION['error'] = "Course not found or access denied";
        header("Location: create_course.php");
        exit;
    }
}

// Handle attendance recording
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'record_attendance') {
    $student_id = $_POST['student_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'present';
    $notes = $_POST['notes'] ?? '';
    
    if (!empty($student_id) && !empty($course_id)) {
        // Check if attendance already recorded for this date
        $check_stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
        $check_stmt->execute([$student_id, $course_id, $attendance_date]);
        
        if ($check_stmt->fetch()) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, notes = ?, updated_at = NOW() WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
            $stmt->execute([$status, $notes, $student_id, $course_id, $attendance_date]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, course_id, attendance_date, status, notes, recorded_by, recorded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$student_id, $course_id, $attendance_date, $status, $notes, $user['id']]);
        }
        
        $_SESSION['message'] = "Attendance recorded successfully!";
        header("Location: manage_students.php?course_id=" . $course_id . "&student_id=" . $student_id);
        exit;
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_grade') {
    $submission_id = $_POST['submission_id'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    if (!empty($submission_id) && !empty($grade)) {
        $stmt = $pdo->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ?, graded_at = NOW(), graded_by = ? WHERE id = ?");
        $stmt->execute([$grade, $feedback, $user['id'], $submission_id]);
        $_SESSION['message'] = "Grade submitted successfully!";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

// Get student details if specific student selected
if (!empty($student_id)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get student's attendance for this course
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND course_id = ? ORDER BY attendance_date DESC");
    $stmt->execute([$student_id, $course_id]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student's assignments for this course
    $stmt = $pdo->prepare("SELECT a.*, asub.grade, asub.feedback, asub.submitted_at, asub.graded_at 
                          FROM assignments a 
                          LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                          WHERE a.course_id = ? 
                          ORDER BY a.due_date DESC");
    $stmt->execute([$student_id, $course_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate attendance statistics
    $total_classes = count($attendance_records);
    $present_count = 0;
    foreach ($attendance_records as $record) {
        if ($record['status'] == 'present') $present_count++;
    }
    $attendance_rate = $total_classes > 0 ? round(($present_count / $total_classes) * 100, 2) : 0;
}

// Get all students for the course
$stmt = $pdo->prepare("SELECT u.*, ce.enrolled_at 
                      FROM users u 
                      JOIN course_enrollments ce ON u.id = ce.student_id 
                      WHERE ce.course_id = ? 
                      ORDER BY u.full_name");
$stmt->execute([$course_id]);
$course_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Teacher Dashboard</title>
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
        
        .student-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }
        
        .attendance-present {
            border-left: 4px solid #28a745;
        }
        
        .attendance-absent {
            border-left: 4px solid #dc3545;
        }
        
        .attendance-late {
            border-left: 4px solid #ffc107;
        }
        
        .progress {
            height: 10px;
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
                        <h2><i class="fas fa-users-cog me-2"></i>Manage Students</h2>
                        <?php if (!empty($course_id)): ?>
                            <h5 class="text-primary">
                                Course: <?= htmlspecialchars($course['course_name'] ?? 'Unknown Course') ?>
                            </h5>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="course_manage.php?id=<?= $course_id ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Course
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Students List -->
            <div class="col-lg-4">
                <div class="dashboard-card p-4">
                    <h4 class="mb-4">Course Students (<?= count($course_students) ?>)</h4>
                    
                    <?php if (empty($course_students)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No students enrolled in this course.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($course_students as $stud): ?>
                            <div class="student-card <?= ($student_id == $stud['id']) ? 'bg-light' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($stud['full_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($stud['email']) ?></small>
                                        <br>
                                        <small class="text-muted">
                                            Enrolled: <?= date('M j, Y', strtotime($stud['enrolled_at'])) ?>
                                        </small>
                                    </div>
                                    <a href="manage_students.php?course_id=<?= $course_id ?>&student_id=<?= $stud['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Details -->
            <div class="col-lg-8">
                <?php if (!empty($student)): ?>
                    <!-- Student Header -->
                    <div class="dashboard-card p-4 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4><?= htmlspecialchars($student['full_name']) ?></h4>
                                <p class="text-muted mb-2"><?= htmlspecialchars($student['email']) ?></p>
                                <div class="d-flex gap-3">
                                    <div>
                                        <small class="text-muted">Attendance Rate</small>
                                        <h5 class="mb-0 <?= $attendance_rate >= 80 ? 'text-success' : ($attendance_rate >= 60 ? 'text-warning' : 'text-danger') ?>">
                                            <?= $attendance_rate ?>%
                                        </h5>
                                    </div>
                                    <div>
                                        <small class="text-muted">Classes Attended</small>
                                        <h5 class="mb-0"><?= $present_count ?>/<?= $total_classes ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-primary text-white rounded-pill px-3 py-1 d-inline-block">
                                    <i class="fas fa-user-graduate me-1"></i>
                                    Student
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="studentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button">
                                <i class="fas fa-tasks me-2"></i>Assignments
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="record-tab" data-bs-toggle="tab" data-bs-target="#record" type="button">
                                <i class="fas fa-edit me-2"></i>Record Attendance
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="studentTabsContent">
                        <!-- Attendance Tab -->
                        <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                            <div class="dashboard-card p-4">
                                <h5 class="mb-4">Attendance History</h5>
                                
                                <?php if (empty($attendance_records)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No attendance records found.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                    <th>Recorded By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($attendance_records as $record): ?>
                                                    <tr class="align-middle">
                                                        <td><?= date('M j, Y', strtotime($record['attendance_date'])) ?></td>
                                                        <td>
                                                            <span class="badge 
                                                                <?= $record['status'] == 'present' ? 'bg-success' : '' ?>
                                                                <?= $record['status'] == 'absent' ? 'bg-danger' : '' ?>
                                                                <?= $record['status'] == 'late' ? 'bg-warning' : '' ?>">
                                                                <?= ucfirst($record['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($record['notes'] ?: '-') ?></td>
                                                        <td>
                                                            <?php 
                                                                $teacher_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                                                                $teacher_stmt->execute([$record['recorded_by']]);
                                                                $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
                                                                echo htmlspecialchars($teacher['full_name'] ?? 'Unknown');
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Assignments Tab -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <div class="dashboard-card p-4">
                                <h5 class="mb-4">Assignment Submissions</h5>
                                
                                <?php if (empty($assignments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No assignments found for this course.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <div class="border rounded p-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-1"><?= htmlspecialchars($assignment['title']) ?></h6>
                                                <?php if ($assignment['grade'] !== null): ?>
                                                    <span class="badge bg-success">Graded: <?= $assignment['grade'] ?>%</span>
                                                <?php elseif ($assignment['submitted_at'] !== null): ?>
                                                    <span class="badge bg-warning">Submitted - Awaiting Grade</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Submitted</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="text-muted mb-2">Due: <?= date('M j, Y', strtotime($assignment['due_date'])) ?></p>
                                            
                                            <?php if ($assignment['submitted_at']): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">Submitted: <?= date('M j, Y g:i A', strtotime($assignment['submitted_at'])) ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($assignment['feedback']): ?>
                                                <div class="alert alert-info p-2">
                                                    <small><strong>Feedback:</strong> <?= htmlspecialchars($assignment['feedback']) ?></small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($assignment['submitted_at'] && $assignment['grade'] === null): ?>
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#gradeModal<?= $assignment['id'] ?>">
                                                    <i class="fas fa-edit me-1"></i>Grade Assignment
                                                </button>
                                                
                                                <!-- Grade Modal -->
                                                <div class="modal fade" id="gradeModal<?= $assignment['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Grade Assignment</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="submit_grade">
                                                                    <input type="hidden" name="submission_id" value="<?= $assignment['id'] ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="grade<?= $assignment['id'] ?>" class="form-label">Grade (%)</label>
                                                                        <input type="number" class="form-control" id="grade<?= $assignment['id'] ?>" 
                                                                               name="grade" min="0" max="100" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="feedback<?= $assignment['id'] ?>" class="form-label">Feedback</label>
                                                                        <textarea class="form-control" id="feedback<?= $assignment['id'] ?>" 
                                                                                  name="feedback" rows="3" placeholder="Provide feedback for the student..."></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">Submit Grade</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Record Attendance Tab -->
                        <div class="tab-pane fade" id="record" role="tabpanel">
                            <div class="dashboard-card p-4">
                                <h5 class="mb-4">Record Attendance</h5>
                                
                                <form method="POST" action="manage_students.php">
                                    <input type="hidden" name="action" value="record_attendance">
                                    <input type="hidden" name="student_id" value="<?= $student_id ?>">
                                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="attendance_date" class="form-label">Date</label>
                                            <input type="date" class="form-control" id="attendance_date" name="attendance_date" 
                                                   value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="present">Present</option>
                                                <option value="absent">Absent</option>
                                                <option value="late">Late</option>
                                                <option value="excused">Excused</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Add any additional notes..."></textarea>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Record Attendance
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card p-5 text-center">
                        <i class="fas fa-user-graduate fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Select a Student</h4>
                        <p class="text-muted">Choose a student from the list to view their details and manage their progress.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
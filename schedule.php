<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

// Get student's enrolled courses with schedule info
$stmt = $pdo->prepare("SELECT c.*, ce.enrolled_at, cs.schedule_day, cs.schedule_time 
                      FROM courses c 
                      JOIN course_enrollments ce ON c.id = ce.course_id 
                      LEFT JOIN course_schedules cs ON c.id = cs.course_id
                      WHERE ce.student_id = ? 
                      ORDER BY cs.schedule_day, cs.schedule_time");
$stmt->execute([$user['id']]);
$scheduled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group courses by day
$schedule_by_day = [];
foreach ($scheduled_courses as $course) {
    $day = $course['schedule_day'] ?? 'Unscheduled';
    $schedule_by_day[$day][] = $course;
}

// Days of the week for ordering
$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Unscheduled'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .schedule-day {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .schedule-item {
            border-left: 4px solid #4361ee;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .time-badge {
            background: #4361ee;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-calendar me-2"></i>My Class Schedule</h2>
                <p class="text-muted">View your upcoming classes and course schedule</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?php if (empty($scheduled_courses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Scheduled Classes</h4>
                        <p class="text-muted">You don't have any scheduled classes yet.</p>
                        <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($days_order as $day): ?>
                        <?php if (isset($schedule_by_day[$day])): ?>
                            <div class="schedule-day">
                                <h4 class="text-primary mb-3">
                                    <i class="fas fa-calendar-day me-2"></i><?= $day ?>
                                </h4>
                                <?php foreach ($schedule_by_day[$day] as $course): ?>
                                    <div class="schedule-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h5 class="mb-1"><?= htmlspecialchars($course['title']) ?></h5>
                                                <p class="text-muted mb-1"><?= htmlspecialchars($course['category'] ?? 'General') ?></p>
                                                <small class="text-muted">
                                                    Teacher: <?= htmlspecialchars($course['teacher_name'] ?? 'Not assigned') ?>
                                                </small>
                                            </div>
                                            <div class="col-md-3">
                                                <?php if ($course['schedule_time']): ?>
                                                    <span class="time-badge">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('g:i A', strtotime($course['schedule_time'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Time not set</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <a href="course_content.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-play me-1"></i>Join Class
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
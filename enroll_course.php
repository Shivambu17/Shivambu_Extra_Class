<?php
include_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'] ?? '';
    
    if (!empty($course_id)) {
        try {
            // Check if already enrolled
            $check_stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id = ? AND student_id = ?");
            $check_stmt->execute([$course_id, $user['id']]);
            
            if (!$check_stmt->fetch()) {
                // Check if course exists and has space
                $course_stmt = $pdo->prepare("SELECT max_students, (SELECT COUNT(*) FROM course_enrollments WHERE course_id = ?) as current_enrollments FROM courses WHERE id = ?");
                $course_stmt->execute([$course_id, $course_id]);
                $course_data = $course_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($course_data) {
                    $max_students = $course_data['max_students'] ?? 30;
                    $current_enrollments = $course_data['current_enrollments'] ?? 0;
                    
                    if ($current_enrollments < $max_students) {
                        // Enroll student
                        $stmt = $pdo->prepare("INSERT INTO course_enrollments (course_id, student_id) VALUES (?, ?)");
                        $stmt->execute([$course_id, $user['id']]);
                        $_SESSION['message'] = "Successfully enrolled in the course!";
                    } else {
                        $_SESSION['error'] = "This course is already full. Maximum $max_students students allowed.";
                    }
                } else {
                    $_SESSION['error'] = "Course not found.";
                }
            } else {
                $_SESSION['message'] = "You are already enrolled in this course.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error enrolling in course: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid course selection";
    }
    
    header("Location: courses.php");
    exit;
} else {
    header("Location: courses.php");
    exit;
}
?>
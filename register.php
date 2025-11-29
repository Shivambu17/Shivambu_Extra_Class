<?php
include_once 'config.php';

// Initialize error variable
$error = '';
$success = '';

// Check if form was submitted and action is set
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'register') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $phone = $_POST['phone'] ?? '';
    $institution = $_POST['institution'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($confirm_password)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with pending status
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, phone, institution, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                
                if ($stmt->execute([$username, $email, $hashed_password, $full_name, $role, $phone, $institution])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // Notify admin (in a real system, you'd send an email)
                    $admin_stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
                    $admin_stmt->execute();
                    $admin = $admin_stmt->fetch();
                    
                    if ($admin) {
                        // Log the approval request
                        error_log("New registration requires approval: " . $email . " - Role: " . $role);
                    }
                    
                    $_SESSION['message'] = "Registration submitted successfully! Your account is pending admin approval. You'll receive an email once approved.";
                    $_SESSION['message_type'] = 'warning';
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Shivambu's Extra Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --warning-color: #f59e0b;
            --gradient-primary: linear-gradient(135deg, #4361ee, #3a0ca3);
            --gradient-secondary: linear-gradient(135deg, #7209b7, #3a0ca3);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px 0;
        }

        .register-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .register-form {
            padding: 40px;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .floating-icon {
            animation: float 3s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .step.completed {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .form-section {
            display: none;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.5s ease;
        }

        .form-section.active {
            display: block;
            opacity: 1;
            transform: translateX(0);
        }

        .info-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .requirement-list {
            list-style: none;
            padding: 0;
        }

        .requirement-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
        }

        .requirement-list li:last-child {
            border-bottom: none;
        }

        .requirement-list li i {
            margin-right: 12px;
            color: var(--success-color);
            font-size: 1.1em;
        }

        .input-group-text {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 2px solid #e2e8f0;
            border-right: none;
        }

        .password-strength {
            margin-top: 10px;
        }

        .progress {
            height: 6px;
            border-radius: 3px;
        }

        .form-text {
            font-size: 0.85rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .register-form {
                padding: 25px;
            }
            
            .register-header {
                padding: 30px 20px;
            }
            
            .step-indicator {
                max-width: 250px;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .loading {
            position: relative;
            color: transparent !important;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="register-container animate__animated animate__fadeInUp">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x floating-icon mb-3"></i>
                        <h2>Join Our Learning Community</h2>
                        <p class="mb-0">Create your account to access quality education</p>
                    </div>
                    
                    <div class="register-form">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step completed">1</div>
                            <div class="step active">2</div>
                            <div class="step">3</div>
                        </div>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'success' ?>">
                                <?= htmlspecialchars($_SESSION['message']) ?>
                            </div>
                            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                        <?php endif; ?>

                        <!-- Approval Process Info -->
                        <div class="info-card mb-4">
                            <h5><i class="fas fa-info-circle text-primary me-2"></i>Account Approval Process</h5>
                            <p class="mb-3">All new accounts require admin approval before you can login. This helps us maintain a secure learning environment.</p>
                            <ul class="requirement-list">
                                <li><i class="fas fa-check-circle"></i>Submit your registration details</li>
                                <li><i class="fas fa-check-circle"></i>Admin reviews your application</li>
                                <li><i class="fas fa-check-circle"></i>Receive approval email notification</li>
                                <li><i class="fas fa-check-circle"></i>Login and start learning</li>
                            </ul>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>Approval usually takes 1-2 business days.</small>
                        </div>

                        <form method="POST" id="registrationForm">
                            <input type="hidden" name="action" value="register">
                            
                            <!-- Personal Information -->
                            <div class="form-section active" id="section1">
                                <h4 class="text-primary mb-4"><i class="fas fa-user me-2"></i>Personal Information</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-primary"></i>
                                            </span>
                                            <input type="text" name="full_name" class="form-control border-start-0" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-at text-primary"></i>
                                            </span>
                                            <input type="text" name="username" class="form-control border-start-0" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                                        </div>
                                        <small class="form-text text-muted">This will be your unique identifier</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-envelope text-primary"></i>
                                            </span>
                                            <input type="email" name="email" class="form-control border-start-0" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-phone text-primary"></i>
                                            </span>
                                            <input type="tel" name="phone" class="form-control border-start-0" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="Optional">
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-primary next-section" data-next="2">
                                        Next <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Account Details -->
                            <div class="form-section" id="section2">
                                <h4 class="text-primary mb-4"><i class="fas fa-lock me-2"></i>Account Details</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-key text-primary"></i>
                                            </span>
                                            <input type="password" name="password" id="password" class="form-control border-start-0" required>
                                            <button type="button" class="btn btn-outline-secondary toggle-password border-start-0">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2">
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small class="form-text text-muted">Password strength: <span id="strength-text">Weak</span></small>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-key text-primary"></i>
                                            </span>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0" required>
                                        </div>
                                        <small class="form-text text-muted" id="password-match">Enter the same password again</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                                        <select name="role" class="form-select" required>
                                            <option value="student" <?= ($_POST['role'] ?? '') == 'student' ? 'selected' : '' ?>>Student</option>
                                            <option value="teacher" <?= ($_POST['role'] ?? '') == 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Institution/Organization</label>
                                        <input type="text" name="institution" class="form-control" value="<?= htmlspecialchars($_POST['institution'] ?? '') ?>" placeholder="Optional">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary prev-section" data-prev="1">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </button>
                                    <button type="button" class="btn btn-primary next-section" data-next="3">
                                        Next <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Review & Submit -->
                            <div class="form-section" id="section3">
                                <h4 class="text-primary mb-4"><i class="fas fa-clipboard-check me-2"></i>Review & Submit</h4>
                                
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary mb-3"><i class="fas fa-list-check me-2"></i>Please review your information:</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Full Name:</strong><br>
                                                <span id="review-fullname" class="fw-semibold"><?= htmlspecialchars($_POST['full_name'] ?? '') ?></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Username:</strong><br>
                                                <span id="review-username" class="fw-semibold"><?= htmlspecialchars($_POST['username'] ?? '') ?></span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Email:</strong><br>
                                                <span id="review-email" class="fw-semibold"><?= htmlspecialchars($_POST['email'] ?? '') ?></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Role:</strong><br>
                                                <span id="review-role" class="fw-semibold"><?= htmlspecialchars($_POST['role'] ?? 'student') ?></span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Phone:</strong><br>
                                                <span id="review-phone" class="fw-semibold"><?= htmlspecialchars($_POST['phone'] ?? 'Not provided') ?></span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <strong class="text-muted">Institution:</strong><br>
                                                <span id="review-institution" class="fw-semibold"><?= htmlspecialchars($_POST['institution'] ?? 'Not provided') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms.php" class="text-primary text-decoration-none">Terms of Service</a> and <a href="privacy.php" class="text-primary text-decoration-none">Privacy Policy</a>
                                    </label>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-secondary prev-section" data-prev="2">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </button>
                                    <button type="submit" class="btn btn-success pulse">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Registration
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-primary text-decoration-none fw-semibold">Sign in here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Multi-step form navigation
            const nextButtons = document.querySelectorAll('.next-section');
            const prevButtons = document.querySelectorAll('.prev-section');
            const sections = document.querySelectorAll('.form-section');
            const steps = document.querySelectorAll('.step');

            function showSection(sectionNumber) {
                sections.forEach(section => {
                    section.classList.remove('active');
                });
                
                setTimeout(() => {
                    document.getElementById(`section${sectionNumber}`).classList.add('active');
                }, 50);
                
                // Update step indicators
                steps.forEach((step, index) => {
                    step.classList.remove('active', 'completed');
                    if (index + 1 < sectionNumber) {
                        step.classList.add('completed');
                    } else if (index + 1 === sectionNumber) {
                        step.classList.add('active');
                    }
                });
            }

            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const nextSection = this.getAttribute('data-next');
                    if (validateSection(parseInt(nextSection) - 1)) {
                        showSection(nextSection);
                        updateReviewSection();
                    }
                });
            });

            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const prevSection = this.getAttribute('data-prev');
                    showSection(prevSection);
                });
            });

            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const strengthBar = document.querySelector('.progress-bar');
            const strengthText = document.getElementById('strength-text');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('password-match');

            function checkPasswordStrength(password) {
                let strength = 0;
                
                if (password.length >= 8) strength += 25;
                else if (password.length >= 6) strength += 15;
                
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
                if (password.match(/\d/)) strength += 25;
                if (password.match(/[^a-zA-Z\d]/)) strength += 25;

                return strength;
            }

            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);

                strengthBar.style.width = strength + '%';
                
                if (strength < 50) {
                    strengthBar.className = 'progress-bar bg-danger';
                    strengthText.textContent = 'Weak';
                    strengthText.className = 'text-danger';
                } else if (strength < 75) {
                    strengthBar.className = 'progress-bar bg-warning';
                    strengthText.textContent = 'Medium';
                    strengthText.className = 'text-warning';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    strengthText.textContent = 'Strong';
                    strengthText.className = 'text-success';
                }

                // Also check password match
                checkPasswordMatch();
            });

            // Password confirmation
            function checkPasswordMatch() {
                if (confirmPassword.value && passwordInput.value !== confirmPassword.value) {
                    passwordMatch.textContent = 'Passwords do not match';
                    passwordMatch.className = 'form-text text-danger';
                    confirmPassword.classList.add('is-invalid');
                } else if (confirmPassword.value) {
                    passwordMatch.textContent = 'Passwords match';
                    passwordMatch.className = 'form-text text-success';
                    confirmPassword.classList.remove('is-invalid');
                } else {
                    passwordMatch.textContent = 'Enter the same password again';
                    passwordMatch.className = 'form-text text-muted';
                    confirmPassword.classList.remove('is-invalid');
                }
            }

            confirmPassword.addEventListener('input', checkPasswordMatch);

            // Toggle password visibility
            document.querySelector('.toggle-password').addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                this.classList.toggle('btn-primary');
                this.classList.toggle('btn-outline-secondary');
            });

            // Update review section
            function updateReviewSection() {
                document.getElementById('review-fullname').textContent = document.querySelector('input[name="full_name"]').value || 'Not provided';
                document.getElementById('review-username').textContent = document.querySelector('input[name="username"]').value || 'Not provided';
                document.getElementById('review-email').textContent = document.querySelector('input[name="email"]').value || 'Not provided';
                document.getElementById('review-role').textContent = document.querySelector('select[name="role"]').value || 'student';
                document.getElementById('review-phone').textContent = document.querySelector('input[name="phone"]').value || 'Not provided';
                document.getElementById('review-institution').textContent = document.querySelector('input[name="institution"]').value || 'Not provided';
            }

            // Form validation for each section
            function validateSection(sectionNumber) {
                const currentSection = document.getElementById(`section${sectionNumber}`);
                const inputs = currentSection.querySelectorAll('input[required], select[required]');
                
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        
                        // Add shake animation to invalid fields
                        input.classList.add('animate__animated', 'animate__shakeX');
                        setTimeout(() => {
                            input.classList.remove('animate__animated', 'animate__shakeX');
                        }, 1000);
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // Special validation for section 2 (passwords)
                if (sectionNumber === 2) {
                    const password = passwordInput.value;
                    const confirm = confirmPassword.value;
                    
                    if (password !== confirm) {
                        isValid = false;
                        confirmPassword.classList.add('is-invalid');
                        passwordMatch.textContent = 'Passwords do not match';
                        passwordMatch.className = 'form-text text-danger';
                    }
                    
                    if (password.length < 6) {
                        isValid = false;
                        passwordInput.classList.add('is-invalid');
                    }
                }

                return isValid;
            }

            // Form submission
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                if (!document.getElementById('terms').checked) {
                    e.preventDefault();
                    const termsCheckbox = document.getElementById('terms');
                    termsCheckbox.classList.add('is-invalid');
                    
                    // Shake animation for terms checkbox
                    termsCheckbox.classList.add('animate__animated', 'animate__shakeX');
                    setTimeout(() => {
                        termsCheckbox.classList.remove('animate__animated', 'animate__shakeX');
                    }, 1000);
                    
                    alert('Please agree to the Terms of Service and Privacy Policy');
                    return;
                }
                
                // Final validation
                if (!validateSection(1) || !validateSection(2)) {
                    e.preventDefault();
                    alert('Please fix the errors in the form before submitting.');
                    return;
                }
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                // Allow form to submit normally
            });

            // Real-time validation for required fields
            document.querySelectorAll('input[required], select[required]').forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Initialize form with any existing POST data
            updateReviewSection();
        });
    </script>
</body>
</html>
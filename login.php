<?php
include_once 'config.php';

// Initialize error variable
$error = '';

// Check if form was submitted and action is set
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Check if user is approved
                    if ($user['status'] !== 'approved') {
                        $error = "Your account is pending admin approval. You'll receive an email once approved.";
                    } else {
                        // Login successful - set session
                        $_SESSION['user'] = $user;
                        $_SESSION['message'] = "Welcome back, " . ($user['full_name'] ?? $user['username']) . "!";
                        
                        header("Location: dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Invalid username or password";
                }
            } else {
                $error = "No user found with that username/email";
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shivambu's Extra Classes</title>
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

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px 0;
        }

        .login-header {
            background: var(--gradient-primary);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
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

        .login-form {
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

        .input-group-text {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 2px solid #e2e8f0;
            border-right: none;
        }

        .demo-card {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--primary-color);
        }

        .role-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 8px;
        }

        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .login-form {
                padding: 25px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="login-container animate__animated animate__fadeInUp">
                    <div class="login-header">
                        <i class="fas fa-graduation-cap fa-3x floating-icon mb-3"></i>
                        <h2>Welcome Back</h2>
                        <p class="mb-0">Sign in to your account</p>
                    </div>
                    
                    <div class="login-form">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success animate__animated animate__fadeIn">
                                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['message']) ?>
                            </div>
                            <?php unset($_SESSION['message']); ?>
                        <?php endif; ?>

                        <form method="POST" id="loginForm">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" name="username" class="form-control border-start-0" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required placeholder="Enter your username or email">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-primary"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control border-start-0" required placeholder="Enter your password">
                                    <button type="button" class="btn btn-outline-secondary toggle-password border-start-0">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                                <a href="forgot-password.php" class="text-primary text-decoration-none">
                                    <i class="fas fa-key me-1"></i>Forgot password?
                                </a>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 mb-4 pulse">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>

                            <!-- Demo Accounts -->
                            <div class="demo-card">
                                <h6 class="text-center mb-3 text-primary">
                                    <i class="fas fa-users me-2"></i>Demo Accounts
                                </h6>
                                <div class="row text-center">
                                    <div class="col-md-4 mb-2">
                                        <div class="p-3 rounded bg-white">
                                            <small class="d-block fw-bold text-primary">Admin</small>
                                            <small class="text-muted d-block">admin / password</small>
                                            <span class="role-badge bg-danger text-white mt-1">Admin</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="p-3 rounded bg-white">
                                            <small class="d-block fw-bold text-success">Teacher</small>
                                            <small class="text-muted d-block">teacher / password</small>
                                            <span class="role-badge bg-success text-white mt-1">Teacher</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="p-3 rounded bg-white">
                                            <small class="d-block fw-bold text-warning">Student</small>
                                            <small class="text-muted d-block">student / password</small>
                                            <span class="role-badge bg-warning text-dark mt-1">Student</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Press 1, 2, or 3 to quickly fill demo accounts
                                    </small>
                                </div>
                            </div>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-primary text-decoration-none fw-semibold">
                                    <i class="fas fa-user-plus me-1"></i>Create one here
                                </a>
                            </p>
                        </div>

                        <!-- Approval Notice -->
                        <div class="alert alert-info mt-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock fa-lg me-3"></i>
                                <div>
                                    <h6 class="mb-1">Account Approval Required</h6>
                                    <p class="mb-0 small">New accounts require admin approval. You'll receive an email notification once approved.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('input[name="password"]');

            // Form submission handler
            loginForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Basic validation
                const username = document.querySelector('input[name="username"]').value.trim();
                const password = passwordInput.value.trim();
                
                if (!username || !password) {
                    e.preventDefault();
                    return;
                }
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
                submitBtn.disabled = true;
                submitBtn.classList.add('loading');
                
                // Allow form to submit normally
            });

            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                this.classList.toggle('btn-primary');
                this.classList.toggle('btn-outline-secondary');
            });

            // Quick fill for demo accounts
            document.addEventListener('keypress', function(e) {
                const usernameField = document.querySelector('input[name="username"]');
                const passwordField = document.querySelector('input[name="password"]');
                
                if (e.key === '1') {
                    usernameField.value = 'admin';
                    passwordField.value = 'password';
                    showQuickFillNotification('Admin credentials filled!');
                } else if (e.key === '2') {
                    usernameField.value = 'teacher';
                    passwordField.value = 'password';
                    showQuickFillNotification('Teacher credentials filled!');
                } else if (e.key === '3') {
                    usernameField.value = 'student';
                    passwordField.value = 'password';
                    showQuickFillNotification('Student credentials filled!');
                }
            });

            function showQuickFillNotification(message) {
                // Remove existing notification if any
                const existingNotification = document.querySelector('.quick-fill-notification');
                if (existingNotification) {
                    existingNotification.remove();
                }

                // Create notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success quick-fill-notification animate__animated animate__fadeInUp';
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '250px';
                notification.innerHTML = `
                    <i class="fas fa-bolt me-2"></i>${message}
                `;
                
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('animate__fadeInUp');
                    notification.classList.add('animate__fadeOutRight');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 500);
                }, 3000);
            }

            // Add input validation styling
            const inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });

                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Auto-focus username field
            document.querySelector('input[name="username"]').focus();
        });
    </script>
</body>
</html>
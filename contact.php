<?php
include_once 'config.php';

// Initialize variables
$error = '';
$success = '';

// Check if form was submitted and action is set
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // In a real system, you would send an email here
        $_SESSION['message'] = "Thank you for your message! We'll get back to you soon.";
        $_SESSION['message_type'] = "success";
        header("Location: contact.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Shivambu's Extra Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --warning-color: #f59e0b;
            --gradient-primary: linear-gradient(135deg, #4361ee, #3a0ca3);
            --gradient-accent: linear-gradient(135deg, #4cc9f0, #4361ee);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            cursor: default;
        }

        .navbar {
            background: var(--gradient-primary) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 2px;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .contact-item {
            padding: 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .contact-item:hover {
            transform: translateX(8px);
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
        }

        .contact-item i {
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            background: var(--gradient-accent);
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.1);
            transform: translateY(-2px);
        }

        #map {
            height: 300px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .floating-icon {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        footer {
            background: var(--gradient-primary) !important;
            margin-top: 50px;
        }

        .alert {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h1, h2, h3, h4, h5 {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .lead {
            color: #64748b;
            font-weight: 500;
        }

        /* Custom cursor for interactive elements */
        .btn, .nav-link, .form-control, .contact-item {
            cursor: pointer;
        }

        /* Loading animation for form submission */
        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
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
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold animate__animated animate__fadeInLeft" href="index.php">
                <i class="fas fa-graduation-cap floating-icon"></i> Shivambu's Classes
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse animate__animated animate__fadeInRight" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
                    
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div style="height: 80px;"></div> <!-- Spacer for fixed navbar -->

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?? 'success' ?> alert-dismissible fade show m-3 animate__animated animate__fadeInDown" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="text-center mb-5 animate__animated animate__fadeInUp">
                    <h1 class="display-4 fw-bold">Contact Us</h1>
                    <p class="lead">Have questions? We'd love to hear from you. Send us a message!</p>
                    <div class="d-flex justify-content-center">
                        <div style="width: 80px; height: 4px; background: var(--gradient-primary); border-radius: 2px;"></div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4 animate__animated animate__fadeInLeft">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <h4 class="mb-4"><i class="fas fa-paper-plane me-2"></i>Send us a Message</h4>
                                <form method="POST" id="contactForm">
                                    <input type="hidden" name="action" value="send_message">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                        <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                        <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-3 pulse">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4 animate__animated animate__fadeInRight">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Contact Information</h4>
                                
                                <div class="contact-item">
                                    <h5><i class="fas fa-envelope me-2"></i>Email</h5>
                                    <p class="mb-0 fw-semibold">support@shivambu.com</p>
                                </div>

                                <div class="contact-item">
                                    <h5><i class="fas fa-phone me-2"></i>Phone</h5>
                                    <p class="mb-0 fw-semibold">+27 69 270 2761</p>
                                </div>

                                <div class="contact-item">
                                    <h5><i class="fas fa-map-marker-alt me-2"></i>Address</h5>
                                    <p class="mb-0 fw-semibold">
                                        602 Motsoaledi<br>
                                        Diepkloof, Soweto<br>
                                        Gauteng, South Africa
                                    </p>
                                </div>

                                <div class="contact-item">
                                    <h5><i class="fas fa-clock me-2"></i>Office Hours</h5>
                                    <p class="mb-0 fw-semibold">
                                        Monday - Friday: 9:00 AM - 6:00 PM<br>
                                        Saturday: 10:00 AM - 2:00 PM<br>
                                        Sunday: Closed
                                    </p>
                                </div>

                                <!-- Map Container -->
                                <div id="map" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-white py-4">
        <div class="container text-center">
            <p class="mb-2">&copy; 2025 Shivambu's Extra Class. All rights reserved.</p>
            <p class="mb-0">+27 69 270 2761 | support@shivambu.com</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        function initMap() {
            // Coordinates for Diepkloof, Soweto (approximate)
            const map = L.map('map').setView([-26.2366, 27.8736], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Custom icon
            const customIcon = L.divIcon({
                html: '<i class="fas fa-map-marker-alt fa-2x" style="color: #4361ee;"></i>',
                iconSize: [30, 30],
                className: 'floating-icon'
            });

            // Add marker
            L.marker([-26.2366, 27.8736], { icon: customIcon })
                .addTo(map)
                .bindPopup('<b>Shivambu\'s Extra Classes</b><br>602 Motsoaledi, Diepkloof')
                .openPopup();
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();

            // Form submission animation
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.classList.add('btn-loading');
                    setTimeout(() => {
                        submitBtn.classList.remove('btn-loading');
                    }, 2000);
                });
            }

            // Add animation on scroll
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.animate__animated');
                
                elements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.style.opacity = '1';
                    }
                });
            };

            // Set initial opacity
            document.querySelectorAll('.animate__animated').forEach(el => {
                el.style.opacity = '0';
            });

            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll(); // Initial check
        });
    </script>
</body>
</html>
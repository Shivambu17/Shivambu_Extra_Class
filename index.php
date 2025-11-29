<?php include_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shivambu's Extra Classes - Quality Education</title>
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
            --gradient-hero: linear-gradient(135deg, #4361ee 0%, #3a0ca3 50%, #7209b7 100%);
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
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            background: var(--gradient-primary) !important;
            padding: 0.5rem 0;
        }

        .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 2px;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .hero-section {
            background: var(--gradient-hero);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .floating-icon {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3rem;
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

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        .course-header {
            background: var(--gradient-primary) !important;
            border: none;
            padding: 20px;
        }

        .course-card .card-header {
            border-radius: 15px 15px 0 0 !important;
        }

        .stats-section {
            background: var(--gradient-secondary);
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .testimonial-card {
            border-left: 4px solid var(--primary-color);
        }

        footer {
            background: var(--gradient-primary) !important;
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

        .text-gradient {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Custom cursor for interactive elements */
        .btn, .nav-link, .card {
            cursor: pointer;
        }

        /* Loading animation */
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

        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">
                            <i class="fas fa-user me-1"></i>Logout (<?= htmlspecialchars($_SESSION['user']['full_name']) ?>)
                        </a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div style="height: 80px;"></div> <!-- Spacer for fixed navbar -->

    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3 animate__animated animate__fadeInDown" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section text-white">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-6 animate__animated animate__fadeInLeft">
                    <h1 class="display-4 fw-bold mb-4">Welcome to Shivambu's Extra Classes</h1>
                    <p class="lead mb-4" style="opacity: 0.9;">Quality education in Engineering, IT, and Management. Learn from expert teachers and enhance your knowledge with personalized attention.</p>
                    <div class="mt-4">
                        <a href="register.php" class="btn btn-light btn-lg me-3 pulse">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="about.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate__animated animate__fadeInRight">
                    <div class="floating-icon">
                        <i class="fas fa-graduation-cap display-1" style="opacity: 0.8;"></i>
                    </div>
                    <div class="mt-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-white">
                                    <h3 class="mb-0">50+</h3>
                                    <small>Students</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-white">
                                    <h3 class="mb-0">10+</h3>
                                    <small>Courses</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-white">
                                    <h3 class="mb-0">5+</h3>
                                    <small>Teachers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Hours Taught</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">Why Choose Us?</h2>
            <div class="row text-center">
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-book feature-icon mb-3"></i>
                            <h4 class="text-gradient">Quality Courses</h4>
                            <p class="text-muted">Engineering, IT, and Management courses designed by industry experts with latest curriculum.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-chalkboard-teacher feature-icon mb-3"></i>
                            <h4 class="text-gradient">Expert Teachers</h4>
                            <p class="text-muted">Learn from qualified and passionate instructors with years of teaching experience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <i class="fas fa-laptop-code feature-icon mb-3"></i>
                            <h4 class="text-gradient">Interactive Learning</h4>
                            <p class="text-muted">Engage with interactive materials, assignments, and real-world projects online.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">Our Courses</h2>
            <div class="row">
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100 course-card">
                        <div class="card-header course-header text-white">
                            <h4><i class="fas fa-cogs me-2"></i>Engineering</h4>
                        </div>
                        <div class="card-body">
                            <p class="fw-semibold text-primary">Featured Modules:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Computing 2</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Programming</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mathematics</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Physics</li>
                            </ul>
                            <div class="mt-3">
                                <span class="badge bg-primary">Popular</span>
                                <span class="badge bg-success">New</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100 course-card">
                        <div class="card-header text-white" style="background: var(--gradient-secondary);">
                            <h4><i class="fas fa-chart-line me-2"></i>Operational Management</h4>
                        </div>
                        <div class="card-body">
                            <p class="fw-semibold text-primary">Featured Modules:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Programming</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Business Management</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Operations</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Project Management</li>
                            </ul>
                            <div class="mt-3">
                                <span class="badge bg-warning text-dark">Trending</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="card h-100 course-card">
                        <div class="card-header text-white" style="background: var(--gradient-accent);">
                            <h4><i class="fas fa-laptop me-2"></i>IT Programs</h4>
                        </div>
                        <div class="card-body">
                            <p class="fw-semibold text-primary">All Year Levels:</p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>First Year Modules</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Second Year Modules</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Third Year Modules</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced Topics</li>
                            </ul>
                            <div class="mt-3">
                                <span class="badge bg-info">Comprehensive</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">What Our Students Say</h2>
            <div class="row">
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="card testimonial-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">John Smith</h5>
                                    <small class="text-muted">Engineering Student</small>
                                </div>
                            </div>
                            <p class="mb-0">"The programming classes helped me understand complex concepts easily. The teachers are amazing!"</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="card testimonial-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-0">Sarah Johnson</h5>
                                    <small class="text-muted">IT Student</small>
                                </div>
                            </div>
                            <p class="mb-0">"Best decision I made! The extra classes boosted my grades and confidence in IT subjects."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Start Your Learning Journey?</h2>
            <p class="lead mb-4">Join hundreds of successful students who have transformed their academic performance.</p>
            <a href="register.php" class="btn btn-light btn-lg pulse">
                <i class="fas fa-user-plus me-2"></i>Enroll Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white py-4">
        <div class="container text-center">
            <p class="mb-2">&copy; 2025 Shivambu's Extra Class. All rights reserved.</p>
            <p class="mb-0">+27 69 270 2761 | support@shivambu.com</p>
            <div class="mt-3">
                <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in-up');
            
            const fadeInOnScroll = function() {
                fadeElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('visible');
                    }
                });
            };

            // Initial check
            fadeInOnScroll();
            
            // Check on scroll
            window.addEventListener('scroll', fadeInOnScroll);

            // Add loading animation to buttons
            const buttons = document.querySelectorAll('a[href*="register"], a[href*="login"]');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!this.getAttribute('href').startsWith('#')) {
                        this.classList.add('btn-loading');
                        setTimeout(() => {
                            this.classList.remove('btn-loading');
                        }, 2000);
                    }
                });
            });
        });
    </script>
</body>
</html>
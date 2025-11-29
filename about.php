<?php include_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Shivambu's Extra Classes | Quality Education</title>
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
            border-left: 4px solid var(--primary-color);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
        }

        .mission-vision-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
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

        .list-group-item {
            border: none;
            padding: 15px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            transform: translateX(10px);
        }

        .team-member {
            padding: 30px 20px;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            transition: all 0.3s ease;
            height: 100%;
        }

        .team-member:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
        .btn, .nav-link, .card, .list-group-item {
            cursor: pointer;
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

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--gradient-primary);
            transform: translateX(-50%);
        }

        .timeline-item {
            margin-bottom: 40px;
            position: relative;
        }

        .timeline-content {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            width: 45%;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-right: auto;
        }

        .timeline-content::after {
            content: '';
            position: absolute;
            top: 20px;
            width: 20px;
            height: 20px;
            background: var(--gradient-primary);
            border-radius: 50%;
        }

        .timeline-item:nth-child(odd) .timeline-content::after {
            left: -10px;
        }

        .timeline-item:nth-child(even) .timeline-content::after {
            right: -10px;
        }

        .value-card {
            text-align: center;
            padding: 30px 20px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
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
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
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

    <!-- Hero Section -->
    <section class="hero-section text-white">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInDown">About Shivambu's Extra Classes</h1>
                    <p class="lead mb-4 animate__animated animate__fadeInUp" style="opacity: 0.9;">
                        Empowering students through quality education, innovative teaching methods, and personalized learning experiences.
                    </p>
                    <div class="animate__animated animate__fadeInUp">
                        <a href="#mission" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-bullseye me-2"></i>Our Mission
                        </a>
                        <a href="#courses" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-book me-2"></i>Our Courses
                        </a>
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
                        <div class="stat-number">3+</div>
                        <div class="stat-label">Years Experience</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Students Taught</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4 fade-in-up">
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-5" id="mission">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="card h-100 text-center">
                        <div class="card-body p-5">
                            <i class="fas fa-bullseye mission-vision-icon feature-icon"></i>
                            <h3 class="text-gradient mb-4">Our Mission</h3>
                            <p class="lead text-muted">
                                To provide affordable, high-quality education through flexible learning solutions that 
                                empower students to achieve academic excellence and personal growth.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="card h-100 text-center">
                        <div class="card-body p-5">
                            <i class="fas fa-eye mission-vision-icon feature-icon"></i>
                            <h3 class="text-gradient mb-4">Our Vision</h3>
                            <p class="lead text-muted">
                                To become the leading provider of extra-class education in South Africa, recognized for 
                                innovation, excellence, and transformative impact on students' academic journeys.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What We Offer -->
    <section class="py-5 bg-light" id="courses">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">What We Offer</h2>
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="card">
                        <div class="card-body p-5">
                            <h3 class="text-center mb-4"><i class="fas fa-gift feature-icon me-2"></i>Comprehensive Learning Solutions</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Engineering Courses</strong> - Computing 2, Programming
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Operational Management</strong> - Programming, Business Analysis
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>IT Programs</strong> - All years including Web Development
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Expert Tutors</strong> - Qualified and experienced educators
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Practical Lessons</strong> - Hands-on learning approach
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Study Materials</strong> - Comprehensive resources
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>Secure Platform</strong> - Protected learning environment
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-check-circle text-success me-3"></i>
                                            <strong>QR-code Access</strong> - Modern class attendance system
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">Our Core Values</h2>
            <div class="row">
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="value-card">
                        <i class="fas fa-graduation-cap value-icon feature-icon"></i>
                        <h4 class="text-gradient">Excellence</h4>
                        <p class="text-muted">We strive for the highest standards in teaching and student support.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="value-card">
                        <i class="fas fa-heart value-icon feature-icon"></i>
                        <h4 class="text-gradient">Passion</h4>
                        <p class="text-muted">We teach with enthusiasm and genuine care for student success.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4 fade-in-up">
                    <div class="value-card">
                        <i class="fas fa-users value-icon feature-icon"></i>
                        <h4 class="text-gradient">Community</h4>
                        <p class="text-muted">We build supportive learning communities where students thrive.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 animate__animated animate__fadeInUp">Our Team</h2>
            <p class="text-center lead mb-5">Meet the dedicated professionals behind Shivambu's Extra Classes</p>
            
            <div class="row">
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="team-member text-center">
                        <i class="fas fa-user-tie fa-3x feature-icon mb-3"></i>
                        <h4 class="text-gradient">Shivambu</h4>
                        <p class="text-primary fw-semibold">Founder & Lead Educator</p>
                        <p class="text-muted">
                            With years of experience in education and technology, Shivambu leads our team with 
                            passion and dedication to student success.
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-primary me-2">Engineering</span>
                            <span class="badge bg-success me-2">Programming</span>
                            <span class="badge bg-info">Leadership</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4 fade-in-up">
                    <div class="team-member text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x feature-icon mb-3"></i>
                        <h4 class="text-gradient">Expert Educators</h4>
                        <p class="text-primary fw-semibold">Subject Matter Specialists</p>
                        <p class="text-muted">
                            Our team of qualified teachers brings diverse expertise and innovative teaching 
                            methods to create engaging learning experiences.
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-warning me-2 text-dark">IT</span>
                            <span class="badge bg-secondary me-2">Management</span>
                            <span class="badge bg-success">Mathematics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Join Our Learning Community?</h2>
            <p class="lead mb-4">Start your journey to academic excellence with Shivambu's Extra Classes today.</p>
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

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
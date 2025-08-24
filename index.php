<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Hearts Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h1 class="hero-title">Welcome to the Happy Hearts Counseling Portal</h1>
                        <p class="hero-subtitle">Book appointments with counselors, manage sessions, and get the support you need.</p>
                        <div class="hero-buttons mt-4">
                            <a href="auth/login.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="section-title">Why Choose Our Platform?</h2>
                    <p class="text-muted">Connecting students with professional counselors through a secure, easy-to-use platform</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-calendar-check feature-icon"></i>
                            <h5 class="card-title mt-3">Easy Booking</h5>
                            <p class="card-text text-muted">Schedule appointments with your preferred counselor in just a few clicks</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-tie feature-icon"></i>
                            <h5 class="card-title mt-3">Qualified Counselors</h5>
                            <p class="card-text text-muted">Connect with licensed professionals specialized in various areas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h5 class="card-title mt-3">Confidential Sessions</h5>
                            <p class="card-text text-muted">Your privacy is protected with secure, confidential counseling sessions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-comments feature-icon"></i>
                            <h5 class="card-title mt-3">Feedback & Notes</h5>
                            <p class="card-text text-muted">Access session notes and provide feedback for continuous improvement</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title">About Our Counseling System</h2>
                    <p class="lead">We're dedicated to providing accessible mental health support for university students through our comprehensive counseling platform.</p>
                    <p>Our system connects students with qualified counselors, making it easier than ever to access professional support when you need it most.</p>
                    <a href="aboutus.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right me-2"></i>Learn More About Us
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-heart about-icon"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center">
                    <i class="fas fa-envelope contact-icon"></i>
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title">Get In Touch</h2>
                    <p class="lead">Have questions or need support? We're here to help you every step of the way.</p>
                    <p>Reach out to our support team for technical assistance or general inquiries about our counseling services.</p>
                    <a href="contact.php" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
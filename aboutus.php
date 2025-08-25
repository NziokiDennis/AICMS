<?php
// aboutus.php (UPDATED)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - University Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="page-hero bg-primary text-white py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold">About Our Mission</h1>
                    <p class="lead">Dedicated to providing accessible and professional counseling services for university students</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="content-section mb-5">
                        <h2 class="section-title text-center mb-4">Who We Are</h2>
                        <p class="lead text-center text-muted mb-4">We are a dedicated team committed to student mental health and wellbeing.</p>

                        <div class="row g-4 mt-4">
                            <div class="col-md-6">
                                <div class="about-card">
                                    <i class="fas fa-graduation-cap text-primary mb-3" aria-hidden="true"></i>
                                    <h4>Student-Focused</h4>
                                    <p>Our platform is designed specifically for university students, understanding their unique challenges and needs.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="about-card">
                                    <i class="fas fa-users text-primary mb-3" aria-hidden="true"></i>
                                    <h4>Expert Team</h4>
                                    <p>We work with licensed counselors and mental health professionals who specialize in student counseling.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-section mb-5">
                        <h2 class="section-title">Our Purpose</h2>
                        <p>The University Counseling System was created to bridge the gap between students seeking mental health support and qualified counselors. We understand that university life can be challenging, and students often face unique stressors including:</p>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Academic pressure and stress</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Social adjustment difficulties</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Financial concerns</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Career uncertainty</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Relationship challenges</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mental health concerns</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="content-section mb-5">
                        <h2 class="section-title">How We Help</h2>
                        <p>Our comprehensive platform provides:</p>

                        <div class="row g-4 mt-3">
                            <div class="col-md-4">
                                <div class="help-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-search text-primary mb-3" aria-hidden="true"></i>
                                        <h5>Easy Discovery</h5>
                                        <p class="text-muted">Find counselors based on specialty, availability, and preferred meeting mode.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="help-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-alt text-primary mb-3" aria-hidden="true"></i>
                                        <h5>Simple Booking</h5>
                                        <p class="text-muted">Schedule appointments at times that work for your busy student schedule.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="help-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock text-primary mb-3" aria-hidden="true"></i>
                                        <h5>Privacy First</h5>
                                        <p class="text-muted">All sessions and communications are kept strictly confidential and secure.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="contact.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope me-2"></i>Get In Touch
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

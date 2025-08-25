<?php
// contact.php (UPDATED)
$message = '';
if ($_POST) {
    // Simple form validation
    $name  = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $msg   = htmlspecialchars(trim($_POST['message'] ?? ''));

    if ($name && $email && $msg && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // In real implementation: persist or email.
        $message = '<div class="alert alert-success">Thank you! Your message has been sent successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Please fill all fields with valid information.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - University Counseling Portal</title>
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
                    <h1 class="display-4 fw-bold">Contact Us</h1>
                    <p class="lead">We're here to help and answer any questions you might have</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8 mb-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-4">
                            <h3 class="mb-0">
                                <i class="fas fa-paper-plane text-primary me-2"></i>Send Us a Message
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            <?= $message ?>

                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message *</label>
                                        <textarea class="form-control" id="message" name="message" rows="6"
                                                  placeholder="Tell us how we can help you..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-send me-2"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">
                                <i class="fas fa-info-circle text-primary me-2"></i>Contact Information
                            </h4>

                            <div class="contact-info-item mb-3">
                                <i class="fas fa-envelope text-primary me-3"></i>
                                <div>
                                    <strong>Email</strong><br>
                                    <a href="mailto:support@counseling.edu" class="text-decoration-none">support@counseling.edu</a>
                                </div>
                            </div>

                            <div class="contact-info-item mb-3">
                                <i class="fas fa-phone text-primary me-3"></i>
                                <div>
                                    <strong>Phone</strong><br>
                                    <a href="tel:+15551234567" class="text-decoration-none">+1 (555) 123-4567</a>
                                </div>
                            </div>

                            <div class="contact-info-item mb-3">
                                <i class="fas fa-clock text-primary me-3"></i>
                                <div>
                                    <strong>Office Hours</strong><br>
                                    Monday - Friday: 8:00 AM - 6:00 PM<br>
                                    Saturday: 9:00 AM - 2:00 PM
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title">
                                <i class="fas fa-question-circle text-primary me-2"></i>Need Immediate Help?
                            </h5>
                            <p class="text-muted mb-3">If you're experiencing a mental health crisis, please contact:</p>
                            <div class="d-grid">
                                <a href="tel:988" class="btn btn-outline-danger">
                                    <i class="fas fa-phone me-2"></i>Crisis Line: 988
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../config/db.php';
require_once './includes/auth_check.php';

requireAdminAuth();

// Get role from URL parameter, default to STUDENT
$role = $_GET['role'] ?? 'STUDENT';
if (!in_array($role, ['STUDENT', 'COUNSELOR', 'ADMIN'])) {
    $role = 'STUDENT';
}

// Handle form submission
$message = '';
$message_type = 'info';

if ($_POST) {
    try {
        $role = $_POST['role'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        
        // Validate inputs
        if (!$name || !$email || !$password || !$role) {
            throw new Exception('All required fields must be filled');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $pdo->beginTransaction();
        
        // Create user
        $stmt = $pdo->prepare("
            INSERT INTO users (role, name, email, password_hash, phone) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$role, $name, $email, $password_hash, $phone]);
        $user_id = $pdo->lastInsertId();
        
        // Create counselor profile if role is COUNSELOR
        if ($role === 'COUNSELOR') {
            $specialty = $_POST['specialty'] ?? '';
            $meeting_mode = $_POST['meeting_mode'] ?? 'IN_PERSON';
            $bio = $_POST['bio'] ?? '';
            $location = $_POST['location'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO counselor_profiles (user_id, specialty, meeting_mode, bio, location)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $specialty, $meeting_mode, $bio, $location]);
        }
        
        $pdo->commit();
        $message = ucfirst(strtolower($role)) . " created successfully";
        $message_type = 'success';
        
        // Redirect back to manage users page after successful creation
        header("Location: manage_users.php?success=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add <?= ucfirst(strtolower($role)) ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include './includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 fw-bold text-danger">
                            <i class="fas fa-<?= $role === 'ADMIN' ? 'user-shield' : ($role === 'COUNSELOR' ? 'user-md' : 'graduation-cap') ?> me-2"></i>
                            Add <?= ucfirst(strtolower($role)) ?>
                        </h1>
                        <p class="text-muted mb-0">Create a new <?= strtolower($role) ?> account</p>
                    </div>
                    <a href="manage_users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
                    </a>
                </div>

                <!-- Role Selection Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <a href="?role=STUDENT" class="text-decoration-none">
                            <div class="card border-0 shadow-sm <?= $role === 'STUDENT' ? 'border-primary bg-primary text-white' : '' ?>">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-graduation-cap fs-4 mb-2"></i>
                                    <h6 class="mb-0">Student</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?role=COUNSELOR" class="text-decoration-none">
                            <div class="card border-0 shadow-sm <?= $role === 'COUNSELOR' ? 'border-success bg-success text-white' : '' ?>">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-user-md fs-4 mb-2"></i>
                                    <h6 class="mb-0">Counselor</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="?role=ADMIN" class="text-decoration-none">
                            <div class="card border-0 shadow-sm <?= $role === 'ADMIN' ? 'border-danger bg-danger text-white' : '' ?>">
                                <div class="card-body text-center py-3">
                                    <i class="fas fa-user-shield fs-4 mb-2"></i>
                                    <h6 class="mb-0">Admin</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-user-plus me-2"></i><?= ucfirst(strtolower($role)) ?> Information
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <input type="hidden" name="role" value="<?= $role ?>">
                            
                            <!-- Basic Information -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="name" required 
                                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" id="email" required
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" id="phone"
                                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" name="password" id="password" required minlength="6">
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            
                            <!-- Counselor-specific fields -->
                            <?php if ($role === 'COUNSELOR'): ?>
                                <hr class="my-4">
                                <h6 class="fw-bold text-success mb-3">
                                    <i class="fas fa-user-md me-2"></i>Counselor Profile Information
                                </h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label for="specialty" class="form-label">Specialty</label>
                                        <input type="text" class="form-control" name="specialty" id="specialty" 
                                               placeholder="e.g., Anxiety & Depression"
                                               value="<?= htmlspecialchars($_POST['specialty'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meeting_mode" class="form-label">Meeting Mode</label>
                                        <select class="form-select" name="meeting_mode" id="meeting_mode">
                                            <option value="IN_PERSON" <?= ($_POST['meeting_mode'] ?? 'IN_PERSON') === 'IN_PERSON' ? 'selected' : '' ?>>In Person</option>
                                            <option value="VIDEO" <?= ($_POST['meeting_mode'] ?? '') === 'VIDEO' ? 'selected' : '' ?>>Video Call</option>
                                            <option value="PHONE" <?= ($_POST['meeting_mode'] ?? '') === 'PHONE' ? 'selected' : '' ?>>Phone Call</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" name="location" id="location" 
                                               placeholder="Office location"
                                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" name="bio" id="bio" rows="4" 
                                                  placeholder="Professional background and approach..."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end gap-2">
                                <a href="manage_users.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Create <?= ucfirst(strtolower($role)) ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
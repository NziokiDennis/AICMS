<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAdminAuth();

// Handle user actions
$message = '';
$message_type = 'info';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_user':
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
                break;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'];
                
                if (!$name || !$email || !$user_id) {
                    throw new Exception('Required fields missing');
                }
                
                // Check email uniqueness (excluding current user)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    throw new Exception('Email already in use by another user');
                }
                
                $pdo->beginTransaction();
                
                // Update user
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $role, $user_id]);
                
                // Handle counselor profile
                if ($role === 'COUNSELOR') {
                    $specialty = $_POST['specialty'] ?? '';
                    $meeting_mode = $_POST['meeting_mode'] ?? 'IN_PERSON';
                    $bio = $_POST['bio'] ?? '';
                    $location = $_POST['location'] ?? '';
                    
                    // Check if profile exists
                    $stmt = $pdo->prepare("SELECT user_id FROM counselor_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->fetch()) {
                        // Update existing profile
                        $stmt = $pdo->prepare("
                            UPDATE counselor_profiles 
                            SET specialty = ?, meeting_mode = ?, bio = ?, location = ?
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$specialty, $meeting_mode, $bio, $location, $user_id]);
                    } else {
                        // Create new profile
                        $stmt = $pdo->prepare("
                            INSERT INTO counselor_profiles (user_id, specialty, meeting_mode, bio, location)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$user_id, $specialty, $meeting_mode, $bio, $location]);
                    }
                } else {
                    // Delete counselor profile if role changed from counselor
                    $stmt = $pdo->prepare("DELETE FROM counselor_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                $pdo->commit();
                $message = "User updated successfully";
                $message_type = 'success';
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                
                // Check if user has appointments
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE student_id = ? OR counselor_id = ?
                ");
                $stmt->execute([$user_id, $user_id]);
                $appointment_count = $stmt->fetchColumn();
                
                if ($appointment_count > 0) {
                    throw new Exception("Cannot delete user with existing appointments. Archive instead or contact system administrator.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $message = "User deleted successfully";
                $message_type = 'success';
                break;
                
            case 'reset_password':
                $user_id = $_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                if (strlen($new_password) < 6) {
                    throw new Exception('Password must be at least 6 characters long');
                }
                
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);
                
                $message = "Password reset successfully";
                $message_type = 'success';
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all users with their stats
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "
    SELECT u.*, cp.specialty, cp.meeting_mode, cp.bio, cp.location,
           (SELECT COUNT(*) FROM appointments WHERE student_id = u.id OR counselor_id = u.id) as appointment_count,
           (SELECT AVG(rating) FROM feedback f 
            JOIN sessions s ON f.session_id = s.id 
            JOIN appointments a ON s.appointment_id = a.id 
            WHERE a.counselor_id = u.id) as avg_rating,
           (SELECT COUNT(*) FROM feedback f 
            JOIN sessions s ON f.session_id = s.id 
            JOIN appointments a ON s.appointment_id = a.id 
            WHERE a.counselor_id = u.id) as feedback_count
    FROM users u
    LEFT JOIN counselor_profiles cp ON u.id = cp.user_id
    WHERE 1=1
";

$params = [];
if ($search) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get counts by role
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$role_counts = [];
while ($row = $stmt->fetch()) {
    $role_counts[$row['role']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 fw-bold text-danger">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </h1>
                        <p class="text-muted mb-0">Create, edit, and manage system users</p>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-plus me-2"></i>Add User
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#createUserModal" data-role="STUDENT">
                                <i class="fas fa-graduation-cap me-2"></i>Add Student</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#createUserModal" data-role="COUNSELOR">
                                <i class="fas fa-user-md me-2"></i>Add Counselor</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#createUserModal" data-role="ADMIN">
                                <i class="fas fa-user-shield me-2"></i>Add Admin</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-graduation-cap text-primary fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $role_counts['STUDENT'] ?? 0 ?></h4>
                        <p class="text-muted mb-0">Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-user-md text-success fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $role_counts['COUNSELOR'] ?? 0 ?></h4>
                        <p class="text-muted mb-0">Counselors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-user-shield text-danger fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= $role_counts['ADMIN'] ?? 0 ?></h4>
                        <p class="text-muted mb-0">Admins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-users text-info fs-2 mb-2"></i>
                        <h4 class="fw-bold"><?= array_sum($role_counts) ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search Users</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="role" class="form-label">Filter by Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="STUDENT" <?= $role_filter === 'STUDENT' ? 'selected' : '' ?>>Students</option>
                            <option value="COUNSELOR" <?= $role_filter === 'COUNSELOR' ? 'selected' : '' ?>>Counselors</option>
                            <option value="ADMIN" <?= $role_filter === 'ADMIN' ? 'selected' : '' ?>>Admins</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2"></i>Users (<?= count($users) ?> found)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User Details</th>
                                <th>Role & Specialty</th>
                                <th>Statistics</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-<?= $user['role'] === 'ADMIN' ? 'danger' : ($user['role'] === 'COUNSELOR' ? 'success' : 'primary') ?> 
                                                          text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-<?= $user['role'] === 'ADMIN' ? 'user-shield' : ($user['role'] === 'COUNSELOR' ? 'user-md' : 'graduation-cap') ?>"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($user['name']) ?></h6>
                                                <p class="mb-0 text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                                                <?php if ($user['phone']): ?>
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($user['phone']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['role'] === 'ADMIN' ? 'danger' : ($user['role'] === 'COUNSELOR' ? 'success' : 'primary') ?> mb-1">
                                            <?= $user['role'] ?>
                                        </span>
                                        <?php if ($user['specialty']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($user['specialty']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($user['meeting_mode']): ?>
                                            <br><span class="badge bg-light text-dark"><?= str_replace('_', ' ', $user['meeting_mode']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= $user['appointment_count'] ?> appointments
                                            <?php if ($user['role'] === 'COUNSELOR' && $user['avg_rating']): ?>
                                                <br><i class="fas fa-star text-warning me-1"></i><?= number_format($user['avg_rating'], 1) ?> 
                                                (<?= $user['feedback_count'] ?> reviews)
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                        <br><small class="text-muted">Joined <?= date('M Y', strtotime($user['created_at'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                    onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                    data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                                                    data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                    data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users text-muted fs-1"></i>
                        <h5 class="text-muted mt-3">No users found</h5>
                        <p class="text-muted">Try adjusting your search criteria or add a new user</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Add New <span id="roleLabel">User</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        <input type="hidden" name="role" id="userRole">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                        </div>
                        
                        <!-- Counselor-specific fields -->
                        <div id="counselorFields" style="display: none;">
                            <hr class="my-4">
                            <h6 class="fw-bold text-success">Counselor Profile Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="specialty" class="form-label">Specialty</label>
                                    <input type="text" class="form-control" name="specialty" 
                                           placeholder="e.g., Anxiety & Depression">
                                </div>
                                <div class="col-md-6">
                                    <label for="meeting_mode" class="form-label">Meeting Mode</label>
                                    <select class="form-select" name="meeting_mode">
                                        <option value="IN_PERSON">In Person</option>
                                        <option value="VIDEO">Video Call</option>
                                        <option value="PHONE">Phone Call</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" 
                                           placeholder="Office location">
                                </div>
                                <div class="col-12">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" rows="3" 
                                              placeholder="Professional background and approach..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="editUserId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editName" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="editName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editPhone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" id="editPhone">
                            </div>
                            <div class="col-md-6">
                                <label for="editRole" class="form-label">Role</label>
                                <select class="form-select" name="role" id="editRole">
                                    <option value="STUDENT">Student</option>
                                    <option value="COUNSELOR">Counselor</option>
                                    <option value="ADMIN">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Counselor-specific fields for editing -->
                        <div id="editCounselorFields">
                            <hr class="my-4">
                            <h6 class="fw-bold text-success">Counselor Profile Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="editSpecialty" class="form-label">Specialty</label>
                                    <input type="text" class="form-control" name="specialty" id="editSpecialty">
                                </div>
                                <div class="col-md-6">
                                    <label for="editMeetingMode" class="form-label">Meeting Mode</label>
                                    <select class="form-select" name="meeting_mode" id="editMeetingMode">
                                        <option value="IN_PERSON">In Person</option>
                                        <option value="VIDEO">Video Call</option>
                                        <option value="PHONE">Phone Call</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editLocation" class="form-label">Location</label>
                                    <input type="text" class="form-control" name="location" id="editLocation">
                                </div>
                                <div class="col-12">
                                    <label for="editBio" class="form-label">Bio</label>
                                    <textarea class="form-control" name="bio" id="editBio" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="resetUserId">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You are about to reset the password for <strong id="resetUserName"></strong>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" 
                                   id="newPassword" required minlength="6">
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Delete User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>
                        
                        <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                        <p class="text-muted">This will permanently remove the user and all associated data.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Handle create user modal
        document.querySelectorAll('[data-bs-target="#createUserModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const role = this.dataset.role;
                document.getElementById('userRole').value = role;
                document.getElementById('roleLabel').textContent = role.charAt(0) + role.slice(1).toLowerCase();
                
                // Show/hide counselor fields
                const counselorFields = document.getElementById('counselorFields');
                counselorFields.style.display = role === 'COUNSELOR' ? 'block' : 'none';
            });
        });

        // Handle edit user
        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editName').value = user.name;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editRole').value = user.role;
            
            // Counselor profile fields
            document.getElementById('editSpecialty').value = user.specialty || '';
            document.getElementById('editMeetingMode').value = user.meeting_mode || 'IN_PERSON';
            document.getElementById('editLocation').value = user.location || '';
            document.getElementById('editBio').value = user.bio || '';
            
            // Show/hide counselor fields
            toggleCounselorFields();
        }

        // Handle role change in edit modal
        document.getElementById('editRole').addEventListener('change', toggleCounselorFields);

        function toggleCounselorFields() {
            const role = document.getElementById('editRole').value;
            const counselorFields = document.getElementById('editCounselorFields');
            counselorFields.style.display = role === 'COUNSELOR' ? 'block' : 'none';
        }

        // Handle reset password modal
        document.querySelectorAll('[data-bs-target="#resetPasswordModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('resetUserId').value = this.dataset.userId;
                document.getElementById('resetUserName').textContent = this.dataset.userName;
            });
        });

        // Handle delete user modal
        document.querySelectorAll('[data-bs-target="#deleteUserModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteUserId').value = this.dataset.userId;
                document.getElementById('deleteUserName').textContent = this.dataset.userName;
            });
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
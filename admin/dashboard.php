<?php
session_start();
require_once '../config/db.php';
require_once './includes/auth_check.php';

requireAdminAuth();

// Get system statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $stmt->fetch()) {
    $stats['users'][$row['role']] = $row['count'];
}
$stats['users']['total'] = array_sum($stats['users'] ?? []);

// Pending appointments
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'PENDING'");
$stats['pending_appointments'] = $stmt->fetchColumn();

// Completed today
$stmt = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'COMPLETED' AND DATE(created_at) = CURDATE()");
$stats['completed_today'] = $stmt->fetchColumn();

// Average rating
$stmt = $pdo->query("SELECT AVG(rating) FROM feedback WHERE rating IS NOT NULL");
$stats['avg_rating'] = $stmt->fetchColumn() ?? 0;

// Stuck sessions (running > 2 hours)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM sessions 
    WHERE status = 'IN_PROGRESS' 
    AND started_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
");
$stats['stuck_sessions'] = $stmt->fetchColumn();

// Old pending appointments (> 7 days)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM appointments 
    WHERE status = 'PENDING' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stats['old_pending'] = $stmt->fetchColumn();

// Monthly appointment trends (last 6 months)
$monthly_data = [];
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed
    FROM appointments 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
while ($row = $stmt->fetch()) {
    $monthly_data[] = [
        'month' => date('M Y', strtotime($row['month'] . '-01')),
        'total' => $row['count'],
        'completed' => $row['completed']
    ];
}

// Recent activities (last 24 hours)
$recent_activities = [];
$stmt = $pdo->query("
    SELECT 'appointment' as type, 
           a.id, a.status, a.created_at,
           s.name as student_name, 
           c.name as counselor_name
    FROM appointments a
    JOIN users s ON a.student_id = s.id
    JOIN users c ON a.counselor_id = c.id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    
    UNION ALL
    
    SELECT 'feedback' as type,
           f.id, 'SUBMITTED' as status, f.created_at,
           s.name as student_name,
           c.name as counselor_name
    FROM feedback f
    JOIN sessions sess ON f.session_id = sess.id
    JOIN appointments a ON sess.appointment_id = a.id
    JOIN users s ON a.student_id = s.id
    JOIN users c ON a.counselor_id = c.id
    WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    
    ORDER BY created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Counseling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 fw-bold text-danger">
                            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                        </h1>
                        <p class="text-muted mb-0">System overview and management</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Last updated: <?= date('M j, Y g:i A') ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        <?php if ($stats['stuck_sessions'] > 0 || $stats['old_pending'] > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-1">System Attention Required</h5>
                                <div class="row">
                                    <?php if ($stats['stuck_sessions'] > 0): ?>
                                        <div class="col-md-6">
                                            <span class="badge bg-warning text-dark me-2"><?= $stats['stuck_sessions'] ?></span>
                                            Stuck session(s) running over 2 hours
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($stats['old_pending'] > 0): ?>
                                        <div class="col-md-6">
                                            <span class="badge bg-warning text-dark me-2"><?= $stats['old_pending'] ?></span>
                                            Old pending appointment(s) over 7 days
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="manage_appointments.php" class="btn btn-warning">
                                <i class="fas fa-tools me-2"></i>Fix Issues
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="text-primary fs-1 mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h2 class="fw-bold"><?= $stats['users']['total'] ?></h2>
                        <p class="text-muted mb-0">Total Users</p>
                        <small class="text-muted">
                            <?= $stats['users']['STUDENT'] ?? 0 ?> Students, 
                            <?= $stats['users']['COUNSELOR'] ?? 0 ?> Counselors
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="text-warning fs-1 mb-3">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="fw-bold"><?= $stats['pending_appointments'] ?></h2>
                        <p class="text-muted mb-0">Pending Appointments</p>
                        <small class="text-muted">Awaiting counselor approval</small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="text-success fs-1 mb-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="fw-bold"><?= $stats['completed_today'] ?></h2>
                        <p class="text-muted mb-0">Completed Today</p>
                        <small class="text-muted"><?= date('M j, Y') ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <div class="text-info fs-1 mb-3">
                            <i class="fas fa-star"></i>
                        </div>
                        <h2 class="fw-bold"><?= number_format($stats['avg_rating'], 1) ?></h2>
                        <p class="text-muted mb-0">Average Rating</p>
                        <small class="text-muted">System-wide satisfaction</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Monthly Trends Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-chart-line me-2"></i>Monthly Appointment Trends
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="manage_users.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Add New User
                            </a>
                            <a href="manage_appointments.php" class="btn btn-outline-warning">
                                <i class="fas fa-calendar-check me-2"></i>Review Appointments
                            </a>
                            <a href="reports/system_health.php" class="btn btn-outline-success">
                                <i class="fas fa-heartbeat me-2"></i>System Health
                            </a>
                            <a href="reports/usage_analytics.php" class="btn btn-outline-info">
                                <i class="fas fa-download me-2"></i>Export Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-activity me-2"></i>Recent Activity (Last 24 Hours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activities)): ?>
                            <div class="timeline">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="me-3">
                                            <div class="bg-<?= $activity['type'] === 'appointment' ? 'primary' : 'success' ?> 
                                                      text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px;">
                                                <i class="fas fa-<?= $activity['type'] === 'appointment' ? 'calendar' : 'star' ?> fa-sm"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium">
                                                <?= $activity['type'] === 'appointment' ? 'Appointment ' . $activity['status'] : 'Feedback Submitted' ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($activity['student_name']) ?> with 
                                                <?= htmlspecialchars($activity['counselor_name']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <?= date('M j, g:i A', strtotime($activity['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox text-muted fs-1"></i>
                                <h6 class="text-muted mt-3">No recent activity</h6>
                                <p class="text-muted">System activity will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white border-top py-3 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">© 2025 Happy Hearts Counseling System</small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Admin Panel v1.0</small>
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Monthly trends chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?= json_encode($monthly_data) ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Total Appointments',
                    data: monthlyData.map(d => d.total),
                    borderColor: '#0d6efd',
                    backgroundColor: '#0d6efd20',
                    tension: 0.4
                }, {
                    label: 'Completed',
                    data: monthlyData.map(d => d.completed),
                    borderColor: '#198754',
                    backgroundColor: '#19875420',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
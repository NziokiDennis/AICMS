<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['COUNSELOR']);

if (!$_GET) {
    header('Location: dashboard.php');
    exit;
}

$counselor_id = $_SESSION['user_id'];
$message = '';
$redirect_url = 'dashboard.php';

if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $appointment_id = (int)$_GET['approve'];
    
    try {
        $pdo->beginTransaction();
        
        // Verify the appointment belongs to this counselor and is pending
        $stmt = $pdo->prepare("
            SELECT * FROM appointments 
            WHERE id = ? AND counselor_id = ? AND status = 'PENDING'
        ");
        $stmt->execute([$appointment_id, $counselor_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            throw new Exception('Appointment not found or already processed');
        }
        
        // Update appointment status to APPROVED
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'APPROVED' 
            WHERE id = ?
        ");
        $stmt->execute([$appointment_id]);
        
        // Create session record
        $stmt = $pdo->prepare("
            INSERT INTO sessions (appointment_id, status) 
            VALUES (?, 'SCHEDULED')
        ");
        $stmt->execute([$appointment_id]);
        
        $pdo->commit();
        $message = 'success=Appointment approved and session scheduled successfully';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'error=' . urlencode('Failed to approve appointment: ' . $e->getMessage());
    }
    
} elseif (isset($_GET['decline']) && is_numeric($_GET['decline'])) {
    $appointment_id = (int)$_GET['decline'];
    
    try {
        // Verify the appointment belongs to this counselor and is pending
        $stmt = $pdo->prepare("
            SELECT * FROM appointments 
            WHERE id = ? AND counselor_id = ? AND status = 'PENDING'
        ");
        $stmt->execute([$appointment_id, $counselor_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            throw new Exception('Appointment not found or already processed');
        }
        
        // Update appointment status to DECLINED
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'DECLINED' 
            WHERE id = ?
        ");
        $stmt->execute([$appointment_id]);
        
        $message = 'info=Appointment declined successfully';
        
    } catch (Exception $e) {
        $message = 'error=' . urlencode('Failed to decline appointment: ' . $e->getMessage());
    }
}

// Redirect back to dashboard with message
header("Location: {$redirect_url}?" . $message);
exit;
?>
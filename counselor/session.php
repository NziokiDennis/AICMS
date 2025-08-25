<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();
date_default_timezone_set('Africa/Nairobi'); // Set to Kenyan time (EAT)
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

if (isset($_GET['start']) && is_numeric($_GET['start'])) {
    $appointment_id = (int)$_GET['start'];
    
    try {
        $pdo->beginTransaction();
        
        // Get appointment, session, and counselor profile details
        $stmt = $pdo->prepare("
            SELECT a.*, s.id as session_id, s.status as session_status, 
                   cp.meeting_mode, cp.location, u.name as student_name
            FROM appointments a
            LEFT JOIN sessions s ON s.appointment_id = a.id
            JOIN counselor_profiles cp ON a.counselor_id = cp.user_id
            JOIN users u ON a.student_id = u.id
            WHERE a.id = ? AND a.counselor_id = ? AND a.status = 'APPROVED'
        ");
        $stmt->execute([$appointment_id, $counselor_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            throw new Exception('Appointment not found or not approved');
        }
        
        if ($appointment['session_status'] && $appointment['session_status'] !== 'SCHEDULED') {
            throw new Exception('Session is already in progress or completed');
        }
        
        // Start the session
        $stmt = $pdo->prepare("
            UPDATE sessions 
            SET status = 'IN_PROGRESS', started_at = NOW()
            WHERE appointment_id = ?
        ");
        $stmt->execute([$appointment_id]);
        
        $pdo->commit();
        
        // Get meeting info for success message
        $meeting_info = '';
        switch ($appointment['meeting_mode']) {
            case 'IN_PERSON':
                $meeting_info = "Location: " . ($appointment['location'] ? $appointment['location'] : 'Room 10, Counseling Department, University Grounds');
                break;
            case 'VIDEO':
                $meeting_info = "Video call initiated. Share meeting link with student.";
                break;
            case 'PHONE':
                $meeting_info = "Phone session started. Contact student via registered phone number.";
                break;
        }
        
        $message = 'success=Session started successfully for ' . $appointment['student_name'] . '. ' . $meeting_info;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'error=' . urlencode('Failed to start session: ' . $e->getMessage());
    }
    
} elseif (isset($_GET['end']) && is_numeric($_GET['end'])) {
    $appointment_id = (int)$_GET['end'];
    
    try {
        $pdo->beginTransaction();
        
        // Get appointment and session details
        $stmt = $pdo->prepare("
            SELECT a.*, s.id as session_id, s.status as session_status, u.name as student_name
            FROM appointments a
            LEFT JOIN sessions s ON s.appointment_id = a.id
            JOIN users u ON a.student_id = u.id
            WHERE a.id = ? AND a.counselor_id = ? AND a.status = 'APPROVED'
        ");
        $stmt->execute([$appointment_id, $counselor_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            throw new Exception('Appointment not found or not approved');
        }
        
        if ($appointment['session_status'] !== 'IN_PROGRESS') {
            throw new Exception('Session is not currently in progress');
        }
        
        // End the session
        $stmt = $pdo->prepare("
            UPDATE sessions 
            SET status = 'COMPLETED', ended_at = NOW()
            WHERE appointment_id = ?
        ");
        $stmt->execute([$appointment_id]);
        
        // Update appointment status to COMPLETED
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'COMPLETED'
            WHERE id = ?
        ");
        $stmt->execute([$appointment_id]);
        
        $pdo->commit();
        $message = 'success=Session with ' . $appointment['student_name'] . ' completed successfully. You can now add notes.';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'error=' . urlencode('Failed to end session: ' . $e->getMessage());
    }
}

// Redirect back to dashboard with message
header("Location: {$redirect_url}?" . $message);
exit;
?>

<?php
// counselor/add_note.php
session_start();
require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireAuth(['COUNSELOR']);

$counselor_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $session_id = (int)$_POST['session_id'];
    $content = trim($_POST['content']);
    $visibility = $_POST['visibility'] ?? 'PRIVATE';
    
    if ($session_id && $content) {
        try {
            // Verify the session belongs to this counselor
            $stmt = $pdo->prepare("
                SELECT s.*, a.counselor_id 
                FROM sessions s
                JOIN appointments a ON s.appointment_id = a.id
                WHERE s.id = ? AND a.counselor_id = ?
            ");
            $stmt->execute([$session_id, $counselor_id]);
            $session = $stmt->fetch();
            
            if (!$session) {
                throw new Exception('Session not found or access denied');
            }
            
            // Insert the note
            $stmt = $pdo->prepare("
                INSERT INTO notes (session_id, counselor_id, content, visibility) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$session_id, $counselor_id, $content, $visibility]);
            
            $message = 'Note added successfully!';
            
        } catch (Exception $e) {
            $error = 'Failed to add note: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select a session and enter note content';
    }
}

// Handle publish/unpublish actions
if (isset($_GET['publish']) && is_numeric($_GET['publish'])) {
    $note_id = (int)$_GET['publish'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notes 
            SET visibility = 'PUBLISHED' 
            WHERE id = ? AND counselor_id = ?
        ");
        $stmt->execute([$note_id, $counselor_id]);
        
        $message = 'Note published successfully!';
        
    } catch (Exception $e) {
        $error = 'Failed to publish note';
    }
}

if (isset($_GET['unpublish']) && is_numeric($_GET['unpublish'])) {
    $note_id = (int)$_GET['unpublish'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notes 
            SET visibility = 'PRIVATE' 
            WHERE id = ? AND counselor_id = ?
        ");
        $stmt->execute([$note_id, $counselor_id]);
        
        $message = 'Note set to private successfully!';
        
    } catch (Exception $e) {
        $error = 'Failed to update note visibility';
    }
}

// Get sessions available for notes (in progress or completed)
$stmt = $pdo->prepare("
    SELECT s.*, a.student_id, a.start_time, a.end_time, u.name as student_name
    FROM sessions s
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON a.student_id = u.id
    WHERE a.counselor_id = ? AND s.status IN ('IN_PROGRESS', 'COMPLETED')
    ORDER BY a.start_time DESC
");
$stmt->execute([$counselor_id]);
$available_sessions = $stmt->fetchAll();

// Get existing notes
$stmt = $pdo->prepare("
    SELECT n.*, s.id as session_id, a.start_time, u.name as student_name
    FROM notes n
    JOIN sessions s ON n.session_id = s.id
    JOIN appointments a ON s.appointment_id = a.id
    JOIN users u ON a.student_id = u.id
    WHERE n.counselor_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$counselor_id]);
$existing_notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Notes - Counseling Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold">Session Notes</h2>
                        <p class="text-muted mb-0">Manage notes for your completed sessions</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add New Note -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus me-2"></i>Add New Note
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_sessions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle text-muted fs-1 mb-3"></i>
                                <h6 class="text-muted">No sessions available</h6>
                                <p class="text-muted small">Notes can be added after starting a session</p>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="session_id" class="form-label">Select Session</label>
                                    <select class="form-select" id="session_id" name="session_id" required>
                                        <option value="">Choose a session...</option>
                                        <?php foreach ($available_sessions as $session): ?>
                                            <option value="<?= $session['id'] ?>">
                                                <?= htmlspecialchars($session['student_name']) ?> - 
                                                <?= date('M j, Y g:i A', strtotime($session['start_time'])) ?>
                                                <?php if ($session['status'] === 'IN_PROGRESS'): ?>
                                                    <span class="text-warning">(In Progress)</span>
                                                <?php else: ?>
                                                    <span class="text-success">(Completed)</span>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label">Session Notes</label>
                                    <textarea class="form-control" id="content" name="content" rows="6" 
                                              placeholder="Enter your session notes, observations, recommendations..." required></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Be thorough but professional. These notes may be shared with the student.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Visibility</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visibility" 
                                               id="private" value="PRIVATE" checked>
                                        <label class="form-check-label" for="private">
                                            <i class="fas fa-lock me-1"></i>Private (Only you can see)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="visibility" 
                                               id="published" value="PUBLISHED">
                                        <label class="form-check-label" for="published">
                                            <i class="fas fa-eye me-1"></i>Published (Student can see)
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Save Note
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Existing Notes -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2 text-primary"></i>Your Notes (<?= count($existing_notes) ?>)
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($existing_notes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-sticky-note text-muted fs-1 mb-3"></i>
                                <h6 class="text-muted">No notes yet</h6>
                                <p class="text-muted small">Start adding notes for your completed sessions</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($existing_notes as $note): ?>
                                    <div class="list-group-item border-0 py-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?= htmlspecialchars($note['student_name']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Session: <?= date('M j, Y g:i A', strtotime($note['start_time'])) ?>
                                                </small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($note['visibility'] === 'PUBLISHED'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-eye me-1"></i>Published
                                                    </span>
                                                    <a href="?unpublish=<?= $note['id'] ?>" 
                                                       class="btn btn-sm btn-outline-warning"
                                                       title="Set to Private">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-lock me-1"></i>Private
                                                    </span>
                                                    <a href="?publish=<?= $note['id'] ?>" 
                                                       class="btn btn-sm btn-outline-success"
                                                       title="Publish to Student">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-light rounded p-3 mb-2">
                                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                        </div>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Added <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
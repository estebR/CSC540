<?php
require_once 'auth.php';
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'tutor') {
    header("Location: home.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$errors  = [];
$success = "";

// Handle accept / decline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']    ?? '';
    $booked_id = (int)($_POST['booked_id'] ?? 0);
    $decision  = $_POST['decision']  ?? '';

    if ($action === 'update_status' && $booked_id > 0) {
        if (!in_array($decision, ['accept', 'reject'], true)) {
            $errors[] = "Invalid decision.";
        } else {
            try {
                mysqli_begin_transaction($conn);

                // Make sure this booking belongs to this tutor
                $checkSql = "
                    SELECT status, session_start 
                    FROM Booked 
                    WHERE booked_id = ? 
                      AND user_id = ?
                    FOR UPDATE
                ";
                $checkStmt = mysqli_prepare($conn, $checkSql);
                mysqli_stmt_bind_param($checkStmt, "ii", $booked_id, $current_user_id);
                mysqli_stmt_execute($checkStmt);
                $checkRes  = mysqli_stmt_get_result($checkStmt);
                $bookRow   = mysqli_fetch_assoc($checkRes);
                mysqli_stmt_close($checkStmt);

                if (!$bookRow) {
                    $errors[] = "Appointment not found.";
                } else {
                    $startDT = new DateTime($bookRow['session_start']);
                    $nowDT   = new DateTime('now');

                    // Optional: avoid editing past appointments
                    if ($startDT <= $nowDT) {
                        $errors[] = "You cannot change a past appointment.";
                    }

                    if (empty($errors)) {
                        if ($decision === 'accept') {
                            // Accept -> status = 'scheduled'
                            $updSql = "
                                UPDATE Booked
                                SET status = 'scheduled'
                                WHERE booked_id = ?
                                  AND user_id = ?
                            ";
                            $updStmt = mysqli_prepare($conn, $updSql);
                            mysqli_stmt_bind_param($updStmt, "ii", $booked_id, $current_user_id);
                            mysqli_stmt_execute($updStmt);
                            mysqli_stmt_close($updStmt);

                            $success = "Appointment accepted.";

                        } else {
                            // Reject -> status = 'declined' and remove student link(s)
                            $delSql = "DELETE FROM AppointmentStudent WHERE booked_id = ?";
                            $delStmt = mysqli_prepare($conn, $delSql);
                            mysqli_stmt_bind_param($delStmt, "i", $booked_id);
                            mysqli_stmt_execute($delStmt);
                            mysqli_stmt_close($delStmt);

                            $updSql = "
                                UPDATE Booked
                                SET status = 'declined'
                                WHERE booked_id = ?
                                  AND user_id = ?
                            ";
                            $updStmt = mysqli_prepare($conn, $updSql);
                            mysqli_stmt_bind_param($updStmt, "ii", $booked_id, $current_user_id);
                            mysqli_stmt_execute($updStmt);
                            mysqli_stmt_close($updStmt);

                            $success = "Appointment declined.";
                        }
                    }
                }

                if (!empty($errors)) {
                    mysqli_rollback($conn);
                } else {
                    mysqli_commit($conn);
                }

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = "Unable to update appointment. Please try again.";
            }
        }
    }
}

// Fetch this tutor's upcoming appointments
$appointments = [];
$appSql = "
    SELECT 
        b.booked_id,
        b.session_start,
        b.session_end,
        b.status,
        b.max_students,
        s.subject_name,
        s.department,
        s.level,
        stu.user_id    AS student_id,
        stu.first_name AS student_first,
        stu.last_name  AS student_last
    FROM Booked b
    INNER JOIN Subject s ON b.subject_id = s.subject_id
    LEFT JOIN AppointmentStudent a ON a.booked_id = b.booked_id
    LEFT JOIN Users stu ON a.user_id = stu.user_id
    WHERE b.user_id = ?
      AND b.session_start >= NOW()
    ORDER BY b.session_start ASC
";
$appStmt = mysqli_prepare($conn, $appSql);
mysqli_stmt_bind_param($appStmt, "i", $current_user_id);
mysqli_stmt_execute($appStmt);
$appRes = mysqli_stmt_get_result($appStmt);
while ($row = mysqli_fetch_assoc($appRes)) {
    $appointments[] = $row;
}
mysqli_stmt_close($appStmt);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your Appointments | Tutoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container page-wrapper">
    <div class="card-glass p-4">
        <h1 class="h4 mb-2 section-title">Your Appointments</h1>
        <p class="small text-muted mb-3">
            Review your upcoming appointments and accept or decline new requests.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success && empty($errors)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($appointments)): ?>
            <p class="small text-muted mb-0">
                You have no upcoming appointments.
            </p>
        <?php else: ?>
            <div class="vstack gap-3">
                <?php foreach ($appointments as $a): ?>
                    <?php
                    $start = new DateTime($a['session_start']);
                    $end   = new DateTime($a['session_end']);

                    $status      = $a['status'];
                    $isPending   = ($status === 'pending');
                    $isScheduled = ($status === 'scheduled');
                    $isDeclined  = ($status === 'declined');
                    ?>
                    <div class="p-3 rounded-3 border border-secondary-subtle">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="fw-semibold">
                                    <?php
                                    echo htmlspecialchars(
                                        $a['department'] . ' ' . $a['level'] . ' – ' . $a['subject_name']
                                    );
                                    ?>
                                </div>
                                <div class="small text-muted">
                                    <?php echo $start->format('D, M j'); ?> •
                                    <?php echo $start->format('g:i A'); ?> – <?php echo $end->format('g:i A'); ?>
                                </div>
                                <div class="small text-muted mt-1">
                                    <?php if ($a['student_id']): ?>
                                        Student: <?php echo htmlspecialchars($a['student_first'] . ' ' . $a['student_last']); ?>
                                    <?php else: ?>
                                        Student: <span class="text-muted">Not assigned yet</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <?php if ($isPending): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($isScheduled): ?>
                                    <span class="badge bg-success">Scheduled</span>
                                <?php elseif ($isDeclined): ?>
                                    <span class="badge bg-danger">Declined</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="small text-muted">
                                Max students: <?php echo (int)$a['max_students']; ?>
                            </div>

                            <?php if ($isPending): ?>
                                <div class="d-flex gap-2">
                                    <form method="post" action="tutor_appointments.php" class="mb-0">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booked_id" value="<?php echo (int)$a['booked_id']; ?>">
                                        <input type="hidden" name="decision" value="accept">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            Accept
                                        </button>
                                    </form>

                                    <form method="post" action="tutor_appointments.php" class="mb-0">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booked_id" value="<?php echo (int)$a['booked_id']; ?>">
                                        <input type="hidden" name="decision" value="reject">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Decline
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="small text-muted">
                                    No further action needed.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
</body>
</html>

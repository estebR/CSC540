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

// 15-minute time slots generator (08:00–20:00)
function build_time_slots($start = '08:00', $end = '20:00', $intervalMinutes = 15): array
{
    $slots     = [];
    $startTime = DateTime::createFromFormat('H:i', $start);
    $endTime   = DateTime::createFromFormat('H:i', $end);

    for ($t = clone $startTime; $t <= $endTime; $t->modify("+{$intervalMinutes} minutes")) {
        $slots[] = [
            'value' => $t->format('H:i'),   // "08:00"
            'label' => $t->format('g:i A'), // "8:00 AM"
        ];
    }

    return $slots;
}

$time_slots     = build_time_slots();
$allowed_values = array_column($time_slots, 'value');

// Handle form submissions (availability ONLY)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1️⃣ Add availability
    if ($action === 'add_availability') {
        $day_of_week = isset($_POST['day_of_week']) ? (int)$_POST['day_of_week'] : -1;
        $start_time  = trim($_POST['start_time'] ?? '');
        $end_time    = trim($_POST['end_time'] ?? '');

        // Only Monday (1) to Friday (5)
        if ($day_of_week < 1 || $day_of_week > 5) {
            $errors[] = "You can only add availability from Monday to Friday.";
        }

        if ($start_time === '' || $end_time === '') {
            $errors[] = "Please provide both a start and end time.";
        } else {
            // Ensure times are from our 15-minute slot list
            if (!in_array($start_time, $allowed_values, true) ||
                !in_array($end_time, $allowed_values, true)) {
                $errors[] = "Invalid time selection.";
            }

            // Enforce 08:00–20:00 window and order
            if ($start_time >= $end_time) {
                $errors[] = "End time must be after start time.";
            }
            if ($start_time < '08:00' || $start_time > '20:00' ||
                $end_time   < '08:00' || $end_time   > '20:00') {
                $errors[] = "Availability must be between 8:00 AM and 8:00 PM.";
            }
        }

        if (empty($errors)) {
            $insSql = "
                INSERT INTO Availability (user_id, day_of_week, start_time, end_time)
                VALUES (?, ?, ?, ?)
            ";
            $insStmt = mysqli_prepare($conn, $insSql);
            mysqli_stmt_bind_param($insStmt, "iiss", $current_user_id, $day_of_week, $start_time, $end_time);
            mysqli_stmt_execute($insStmt);
            mysqli_stmt_close($insStmt);

            $success = "Availability added.";
        }
    }

    // 2️⃣ Delete availability row
    if ($action === 'delete_availability') {
        $availability_id = (int)($_POST['availability_id'] ?? 0);

        if ($availability_id > 0) {
            $delSql = "DELETE FROM Availability WHERE availability_id = ? AND user_id = ?";
            $delStmt = mysqli_prepare($conn, $delSql);
            mysqli_stmt_bind_param($delStmt, "ii", $availability_id, $current_user_id);
            mysqli_stmt_execute($delStmt);
            mysqli_stmt_close($delStmt);

            $success = "Availability slot removed.";
        }
    }
}

// Fetch tutor's subjects (read-only list)
$tutor_subjects = [];
$subSql = "
    SELECT s.subject_name, s.department, s.level
    FROM TutorQualification tq
    INNER JOIN Subject s ON tq.subject_id = s.subject_id
    WHERE tq.user_id = ?
    ORDER BY s.department, s.level, s.subject_name
";
$subStmt = mysqli_prepare($conn, $subSql);
mysqli_stmt_bind_param($subStmt, "i", $current_user_id);
mysqli_stmt_execute($subStmt);
$subResult = mysqli_stmt_get_result($subStmt);
while ($row = mysqli_fetch_assoc($subResult)) {
    $tutor_subjects[] = $row;
}
mysqli_stmt_close($subStmt);

// Fetch tutor availability
$availability = [];
$availSql = "
    SELECT availability_id, day_of_week, start_time, end_time
    FROM Availability
    WHERE user_id = ?
    ORDER BY day_of_week, start_time
";
$availStmt = mysqli_prepare($conn, $availSql);
mysqli_stmt_bind_param($availStmt, "i", $current_user_id);
mysqli_stmt_execute($availStmt);
$availResult = mysqli_stmt_get_result($availStmt);
while ($row = mysqli_fetch_assoc($availResult)) {
    $availability[] = $row;
}
mysqli_stmt_close($availStmt);

$day_names = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// For keeping form values on error
$posted_day   = $_POST['day_of_week'] ?? '';
$posted_start = $_POST['start_time'] ?? '';
$posted_end   = $_POST['end_time'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tutor Dashboard | Tutoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, #22c55e 0, #020617 45%, #020617 100%);
            color: #e5e7eb;
        }
        .page-wrapper {
            padding: 2rem 0 3rem;
        }
        .card-glass {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
        }
        .section-title {
            color: #f9fafb;
        }
        .form-control, .form-select {
            background-color: #020617;
            border-color: #374151;
            color: #e5e7eb;
        }
        .form-control:focus, .form-select:focus {
            background-color: #020617;
            border-color: #22c55e;
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.35);
            color: #f9fafb;
        }
        .table-dark td, .table-dark th {
            vertical-align: middle;
            color: #e5e7eb;
        }
        .text-muted {
            color: #cbd5f5 !important;
        }
        .badge-level {
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container page-wrapper">
    <div class="row g-4">
        <!-- Subjects card (read-only) -->
        <div class="col-lg-6">
            <div class="card-glass p-4 h-100">
                <h2 class="h4 mb-1 section-title">Subjects you can tutor</h2>
                <p class="small text-muted mb-3">
                    Subjects You Can Tutor.
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

                <?php if (empty($tutor_subjects)): ?>
                    <p class="text-muted mb-0">
                        You currently have no subjects assigned. (This shouldn’t normally happen – please contact an admin.)
                    </p>
                <?php else: ?>
                    <?php
                    $current_dept = null;
                    foreach ($tutor_subjects as $sub):
                        if ($current_dept !== $sub['department']):
                            if ($current_dept !== null) {
                                echo '<hr class="border-secondary">';
                            }
                            $current_dept = $sub['department'];
                            ?>
                            <h6 class="text-uppercase text-muted mt-2 mb-2">
                                <?php echo htmlspecialchars($current_dept); ?>
                            </h6>
                        <?php endif; ?>
                        <div class="d-flex align-items-center mb-1">
                            <span class="me-2">•</span>
                            <div>
                                <div><?php echo htmlspecialchars($sub['subject_name']); ?></div>
                                <div class="small text-muted">
                                    Level <?php echo (int)$sub['level']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Availability card -->
        <div class="col-lg-6">
            <div class="card-glass p-4 h-100">
                <h2 class="h4 mb-1 section-title">Your weekly availability</h2>
                <p class="small text-muted mb-3">
                    Add time slots when you are available to tutor (Monday–Friday, 8:00 AM–8:00 PM).
                </p>

                <form method="post" action="tutor_dashboard.php" class="row g-2 align-items-end mb-4">
                    <input type="hidden" name="action" value="add_availability">

                    <div class="col-md-4">
                        <label for="day_of_week" class="form-label">Day</label>
                        <select class="form-select" id="day_of_week" name="day_of_week" required>
                            <option value="" disabled <?php echo $posted_day === '' ? 'selected' : ''; ?>>Select day</option>
                            <option value="1" <?php echo $posted_day == '1' ? 'selected' : ''; ?>>Monday</option>
                            <option value="2" <?php echo $posted_day == '2' ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="3" <?php echo $posted_day == '3' ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="4" <?php echo $posted_day == '4' ? 'selected' : ''; ?>>Thursday</option>
                            <option value="5" <?php echo $posted_day == '5' ? 'selected' : ''; ?>>Friday</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Start</label>
                        <select
                            class="form-select"
                            id="start_time"
                            name="start_time"
                            required
                        >
                            <option value="" disabled <?php echo $posted_start === '' ? 'selected' : ''; ?>>Start</option>
                            <?php foreach ($time_slots as $slot): ?>
                                <option value="<?php echo htmlspecialchars($slot['value']); ?>"
                                    <?php echo ($posted_start === $slot['value']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($slot['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="end_time" class="form-label">End</label>
                        <select
                            class="form-select"
                            id="end_time"
                            name="end_time"
                            required
                        >
                            <option value="" disabled <?php echo $posted_end === '' ? 'selected' : ''; ?>>End</option>
                            <?php foreach ($time_slots as $slot): ?>
                                <option value="<?php echo htmlspecialchars($slot['value']); ?>"
                                    <?php echo ($posted_end === $slot['value']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($slot['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">
                            Add
                        </button>
                    </div>
                </form>

                <?php if (empty($availability)): ?>
                    <p class="text-muted small mb-0">
                        You have not added any availability yet.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availability as $slot): ?>
                                    <tr>
                                        <td><?php echo $day_names[(int)$slot['day_of_week']] ?? 'Unknown'; ?></td>
                                        <td><?php echo date('g:i A', strtotime($slot['start_time'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($slot['end_time'])); ?></td>
                                        <td class="text-end">
                                            <form method="post" action="tutor_dashboard.php" class="d-inline">
                                                <input type="hidden" name="action" value="delete_availability">
                                                <input type="hidden" name="availability_id" value="<?php echo $slot['availability_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
</body>
</html>

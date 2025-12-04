<?php
require_once 'auth.php';
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

// Only admins can view this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}

$errors  = [];
$success = "";

// --------------------------------------------------
// 1) Fetch all tutors (Users + Tutors)
// --------------------------------------------------
$tutors = [];
$tutorSql = "
    SELECT u.user_id, u.first_name, u.last_name
    FROM Users u
    INNER JOIN Tutors t ON u.user_id = t.user_id
    ORDER BY u.first_name, u.last_name
";
$tutorResult = mysqli_query($conn, $tutorSql);
while ($row = mysqli_fetch_assoc($tutorResult)) {
    $tutors[] = $row;
}
mysqli_free_result($tutorResult);

// selected tutor (from GET or POST)
$selected_tutor_id = 0;
if (isset($_GET['tutor_id'])) {
    $selected_tutor_id = (int)$_GET['tutor_id'];
} elseif (isset($_POST['selected_tutor_id'])) {
    $selected_tutor_id = (int)$_POST['selected_tutor_id'];
}

// --------------------------------------------------
// 2) Handle add/remove qualification
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_subject') {
        $selected_tutor_id = (int)($_POST['selected_tutor_id'] ?? 0);
        $subject_id        = (int)($_POST['subject_id'] ?? 0);

        if ($selected_tutor_id <= 0 || $subject_id <= 0) {
            $errors[] = "Please select a tutor and a subject.";
        } else {
            // Check if already exists
            $checkSql = "
                SELECT 1 FROM TutorQualification
                WHERE user_id = ? AND subject_id = ?
            ";
            $checkStmt = mysqli_prepare($conn, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "ii", $selected_tutor_id, $subject_id);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);

            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $errors[] = "This tutor is already qualified for that subject.";
            }
            mysqli_stmt_close($checkStmt);

            if (empty($errors)) {
                $insSql = "
                    INSERT INTO TutorQualification (user_id, subject_id)
                    VALUES (?, ?)
                ";
                $insStmt = mysqli_prepare($conn, $insSql);
                mysqli_stmt_bind_param($insStmt, "ii", $selected_tutor_id, $subject_id);
                mysqli_stmt_execute($insStmt);
                mysqli_stmt_close($insStmt);

                $success = "Subject added to tutor's qualifications.";
            }
        }
    }

    if ($action === 'remove_subject') {
        $selected_tutor_id = (int)($_POST['selected_tutor_id'] ?? 0);
        $subject_id        = (int)($_POST['subject_id'] ?? 0);

        if ($selected_tutor_id <= 0 || $subject_id <= 0) {
            $errors[] = "Invalid tutor or subject.";
        } else {
            $delSql = "
                DELETE FROM TutorQualification
                WHERE user_id = ? AND subject_id = ?
            ";
            $delStmt = mysqli_prepare($conn, $delSql);
            mysqli_stmt_bind_param($delStmt, "ii", $selected_tutor_id, $subject_id);
            mysqli_stmt_execute($delStmt);
            mysqli_stmt_close($delStmt);

            $success = "Subject removed from tutor's qualifications.";
        }
    }
}

// --------------------------------------------------
// 3) Fetch all subjects + current qualifications for selected tutor
// --------------------------------------------------
$subjects = [];
$subSql = "
    SELECT subject_id, subject_name, department, level
    FROM Subject
    ORDER BY department, level, subject_name
";
$subResult = mysqli_query($conn, $subSql);
while ($row = mysqli_fetch_assoc($subResult)) {
    $subjects[] = $row;
}
mysqli_free_result($subResult);

// Map subject_id => subject row
$subjectsById = [];
foreach ($subjects as $s) {
    $subjectsById[(int)$s['subject_id']] = $s;
}

// Current subjects for selected tutor
$current_subject_ids = [];
if ($selected_tutor_id > 0) {
    $qualSql = "
        SELECT subject_id
        FROM TutorQualification
        WHERE user_id = ?
    ";
    $qualStmt = mysqli_prepare($conn, $qualSql);
    mysqli_stmt_bind_param($qualStmt, "i", $selected_tutor_id);
    mysqli_stmt_execute($qualStmt);
    $qualRes = mysqli_stmt_get_result($qualStmt);
    while ($row = mysqli_fetch_assoc($qualRes)) {
        $current_subject_ids[] = (int)$row['subject_id'];
    }
    mysqli_stmt_close($qualStmt);
}

// Subjects that tutor does NOT yet have (for the add selector)
$available_subjects_for_add = array_filter(
    $subjects,
    function ($s) use ($current_subject_ids) {
        return !in_array((int)$s['subject_id'], $current_subject_ids, true);
    }
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard | Tutoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
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
            background: rgba(15, 23, 42, 0.96);
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
        }
        .section-title {
            color: #f9fafb;
        }
        .form-control,
        .form-select {
            background-color: #020617;
            border-color: #374151;
            color: #e5e7eb;
        }
        .form-control:focus,
        .form-select:focus {
            background-color: #020617;
            border-color: #22c55e;
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.35);
            color: #f9fafb;
        }
        .table-dark td,
        .table-dark th {
            vertical-align: middle;
            color: #e5e7eb;
        }
        .badge-course {
            background-color: rgba(148, 163, 184, 0.2);
            border: 1px solid rgba(148, 163, 184, 0.4);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container page-wrapper">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-glass p-4 h-100">
                <h1 class="h4 mb-2 section-title">Admin Dashboard</h1>
                <p class="small text-muted mb-3">
                    Manage tutor qualifications. Select a tutor, review their subjects, and add or remove qualifications.
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

                <!-- Select tutor -->
                <form method="get" action="admin_dashboard.php" class="mb-3">
                    <label for="tutor_id" class="form-label">Select tutor</label>
                    <div class="input-group">
                        <select
                            id="tutor_id"
                            name="tutor_id"
                            class="form-select"
                            onchange="this.form.submit()"
                        >
                            <option value="">Choose a tutor</option>
                            <?php foreach ($tutors as $t): ?>
                                <option
                                    value="<?php echo (int)$t['user_id']; ?>"
                                    <?php if ($selected_tutor_id === (int)$t['user_id']) echo 'selected'; ?>
                                >
                                    <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-light" type="submit">Load</button>
                    </div>
                </form>

                <?php if ($selected_tutor_id === 0): ?>
                    <p class="small text-muted mb-0">
                        Choose a tutor to view and edit their qualifications.
                    </p>
                <?php else: ?>
                    <!-- Current subjects -->
                    <h2 class="h6 mt-3 mb-2 section-title">Current qualifications</h2>

                    <?php if (empty($current_subject_ids)): ?>
                        <p class="small text-muted">
                            This tutor does not have any subjects assigned yet.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-dark table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th style="width: 90px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_subject_ids as $sid): ?>
                                        <?php
                                        if (!isset($subjectsById[$sid])) {
                                            continue;
                                        }
                                        $s = $subjectsById[$sid];
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge badge-course">
                                                    <?php echo htmlspecialchars($s['department'] . ' ' . $s['level']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                            <td class="text-end">
                                                <form method="post" action="admin_dashboard.php" class="mb-0">
                                                    <input type="hidden" name="action" value="remove_subject">
                                                    <input type="hidden" name="selected_tutor_id" value="<?php echo $selected_tutor_id; ?>">
                                                    <input type="hidden" name="subject_id" value="<?php echo $sid; ?>">
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

                    <!-- Add new subject -->
                    <h2 class="h6 mt-3 mb-2 section-title">Add qualification</h2>
                    <form method="post" action="admin_dashboard.php" class="row g-2 align-items-end">
                        <input type="hidden" name="action" value="add_subject">
                        <input type="hidden" name="selected_tutor_id" value="<?php echo $selected_tutor_id; ?>">

                        <div class="col-md-9">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select id="subject_id" name="subject_id" class="form-select" required>
                                <option value="">Choose a subject</option>
                                <?php foreach ($available_subjects_for_add as $s): ?>
                                    <option value="<?php echo (int)$s['subject_id']; ?>">
                                        <?php
                                        echo htmlspecialchars(
                                            $s['department'] . ' ' . $s['level'] . ' – ' . $s['subject_name']
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-success">
                                Add
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>
</body>
</html>

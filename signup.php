<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

$errors = [];
$subjects = [];

// Fetch subjects for tutor subject selection
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role             = $_POST['account_type'] ?? '';
    $tutor_subjects   = $_POST['tutor_subjects'] ?? [];

    // Validation
    if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
        $errors[] = "Please fill in all required fields.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!in_array($role, ['student', 'tutor'], true)) {
        $errors[] = "Please select a valid account type.";
    }

    // If tutor, require at least one subject
    if ($role === 'tutor' && empty($tutor_subjects)) {
        $errors[] = "Please select at least one subject you can tutor.";
    }

    // Check if email already exists in Accounts
    if (empty($errors)) {
        $checkSql = "SELECT account_id FROM Accounts WHERE email = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $errors[] = "An account with this email already exists.";
        }

        mysqli_stmt_close($checkStmt);
    }

    // If everything is good, insert into Accounts, Users, Students/Tutors, TutorQualification
    if (empty($errors)) {
        $parts = preg_split('/\s+/', $full_name, 2);
        $first_name = $parts[0] ?? '';
        $last_name  = $parts[1] ?? '';

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            mysqli_begin_transaction($conn);

            // Accounts
            $accSql = "INSERT INTO Accounts (email, password, role) VALUES (?, ?, ?)";
            $accStmt = mysqli_prepare($conn, $accSql);
            mysqli_stmt_bind_param($accStmt, "sss", $email, $hashedPassword, $role);
            mysqli_stmt_execute($accStmt);
            $account_id = mysqli_insert_id($conn);
            mysqli_stmt_close($accStmt);

            // Users
            $userSql = "INSERT INTO Users (account_id, first_name, last_name) VALUES (?, ?, ?)";
            $userStmt = mysqli_prepare($conn, $userSql);
            mysqli_stmt_bind_param($userStmt, "iss", $account_id, $first_name, $last_name);
            mysqli_stmt_execute($userStmt);
            $user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($userStmt);

            if ($role === 'student') {
                // Students
                $studSql = "INSERT INTO Students (user_id) VALUES (?)";
                $studStmt = mysqli_prepare($conn, $studSql);
                mysqli_stmt_bind_param($studStmt, "i", $user_id);
                mysqli_stmt_execute($studStmt);
                mysqli_stmt_close($studStmt);

            } else { // tutor
                // Tutors
                $tutorSql = "INSERT INTO Tutors (user_id, max_concurrent_sessions) VALUES (?, 1)";
                $tutorStmt = mysqli_prepare($conn, $tutorSql);
                mysqli_stmt_bind_param($tutorStmt, "i", $user_id);
                mysqli_stmt_execute($tutorStmt);
                mysqli_stmt_close($tutorStmt);

                // TutorQualification
                if (!empty($tutor_subjects)) {
                    $qualSql = "
                        INSERT INTO TutorQualification (user_id, subject_id, proficiency_level)
                        VALUES (?, ?, ?)
                    ";
                    $qualStmt = mysqli_prepare($conn, $qualSql);
                    $prof_level = 'advanced'; // default

                    foreach ($tutor_subjects as $sid) {
                        $sid = (int)$sid;
                        mysqli_stmt_bind_param($qualStmt, "iis", $user_id, $sid, $prof_level);
                        mysqli_stmt_execute($qualStmt);
                    }
                    mysqli_stmt_close($qualStmt);
                }
            }

            mysqli_commit($conn);

            $_SESSION['signup_success'] = "Account created successfully. Please log in.";
            header("Location: login.php");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Something went wrong while creating your account. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Account | Tutoring System</title>
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
            background: radial-gradient(circle at top left, #0d6efd 0, #111827 45%, #020617 100%);
            color: #e5e7eb;
        }
        .auth-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            background: rgba(15, 23, 42, 0.9);
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
        }
        .auth-card h1 {
            color: #f9fafb;
        }
        .form-control, .form-select {
            background-color: #020617;
            border-color: #374151;
            color: #e5e7eb;
        }
        .form-control:focus, .form-select:focus {
            background-color: #020617;
            border-color: #60a5fa;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.35);
            color: #f9fafb;
        }
        .form-label {
            color: #e5e7eb;
        }
        .btn-primary {
            background: linear-gradient(to right, #2563eb, #4f46e5);
            border: none;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.45);
        }
        .btn-primary:hover {
            background: linear-gradient(to right, #1d4ed8, #4338ca);
            transform: translateY(-1px);
        }
        .small-link {
            color: #9ca3af;
        }
        .small-link a {
            color: #60a5fa;
            text-decoration: none;
        }
        .small-link a:hover {
            text-decoration: underline;
        }
        #tutor-subjects-section {
            display: none; /* shown only when Tutor is selected */
        }
        .subjects-box {
            max-height: 220px;
            overflow-y: auto;
            padding: 0.75rem;
            border-radius: 0.75rem;
            background: rgba(15,23,42,0.8);
            border: 1px solid rgba(148, 163, 184, 0.4);
        }
        .subjects-box h6 {
            color: #9ca3af;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="auth-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold mb-1">Create your account</h1>
                        <p class="small text-muted mb-0">
                            Join the tutoring system as a student or tutor.
                        </p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err): ?>
                                <div><?php echo htmlspecialchars($err); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="signup.php" method="post" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full name</label>
                            <input
                                type="text"
                                class="form-control"
                                id="full_name"
                                name="full_name"
                                required
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                placeholder="e.g. Hamza Nazim"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">University email</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                placeholder="you@university.edu"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="account_type" class="form-label">Account type</label>
                            <select
                                class="form-select"
                                id="account_type"
                                name="account_type"
                                required
                            >
                                <option value="" disabled <?php echo empty($_POST['account_type']) ? 'selected' : ''; ?>>
                                    Select account type
                                </option>
                                <option value="student" <?php echo (($_POST['account_type'] ?? '') === 'student') ? 'selected' : ''; ?>>
                                    Student
                                </option>
                                <option value="tutor" <?php echo (($_POST['account_type'] ?? '') === 'tutor') ? 'selected' : ''; ?>>
                                    Tutor
                                </option>
                            </select>
                        </div>

                        <!-- Tutor-only subjects section -->
                        <div class="mb-3" id="tutor-subjects-section">
                            <label class="form-label">Subjects you can tutor</label>
                            <p class="small text-muted mb-2">
                                Select the subjects/levels you have completed and feel confident teaching.
                            </p>
                            <div class="subjects-box">
                                <?php
                                $current_dept = null;
                                $posted_subjects = $_POST['tutor_subjects'] ?? [];
                                foreach ($subjects as $sub):
                                    if ($current_dept !== $sub['department']):
                                        if ($current_dept !== null) {
                                            echo '<hr class="border-secondary">';
                                        }
                                        $current_dept = $sub['department'];
                                        ?>
                                        <h6 class="text-uppercase mt-1 mb-1">
                                            <?php echo htmlspecialchars($current_dept); ?>
                                        </h6>
                                    <?php endif; ?>

                                    <?php
                                    $checked = in_array((int)$sub['subject_id'], $posted_subjects, true);
                                    ?>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="tutor_subjects[]"
                                            value="<?php echo $sub['subject_id']; ?>"
                                            id="sub-<?php echo $sub['subject_id']; ?>"
                                            <?php echo $checked ? 'checked' : ''; ?>
                                        >
                                        <label class="form-check-label" for="sub-<?php echo $sub['subject_id']; ?>">
                                            <?php echo htmlspecialchars($sub['subject_name']); ?>
                                            <span class="text-muted small">
                                                (Level <?php echo (int)$sub['level']; ?>)
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required
                                placeholder="At least 6 characters"
                            >
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 mb-2">
                            Sign up
                        </button>

                        <p class="small small-link text-center mt-2 mb-0">
                            Already have an account?
                            <a href="login.php">Log in</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
<script>
    const accountTypeSelect = document.getElementById('account_type');
    const tutorSection = document.getElementById('tutor-subjects-section');

    function toggleTutorSection() {
        if (!accountTypeSelect || !tutorSection) return;
        if (accountTypeSelect.value === 'tutor') {
            tutorSection.style.display = 'block';
        } else {
            tutorSection.style.display = 'none';
        }
    }

    if (accountTypeSelect) {
        accountTypeSelect.addEventListener('change', toggleTutorSection);
        // initialize on load (for validation errors / back button)
        toggleTutorSection();
    }
</script>
</body>
</html>

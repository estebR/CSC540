<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

$errors = [];
$success_message = '';

// Show signup success message once
if (!empty($_SESSION['signup_success'])) {
    $success_message = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = "Please enter both email and password.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        // Look up account by email in Accounts table
        $sql = "SELECT account_id, email, password, role FROM Accounts WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$account) {
            $errors[] = "Email not found.";
        } else {
            // Verify password
            if (!password_verify($password, $account['password'])) {
                $errors[] = "Incorrect password.";
            } else {
                $account_id = (int)$account['account_id'];
                $role       = $account['role'];

                // Fetch associated user row
                $userSql = "SELECT user_id, first_name, last_name FROM Users WHERE account_id = ?";
                $userStmt = mysqli_prepare($conn, $userSql);
                mysqli_stmt_bind_param($userStmt, "i", $account_id);
                mysqli_stmt_execute($userStmt);
                $userResult = mysqli_stmt_get_result($userStmt);
                $user = mysqli_fetch_assoc($userResult);
                mysqli_stmt_close($userStmt);

                if (!$user) {
                    $errors[] = "User profile not found for this account.";
                } else {
                    // Optional: update last_login
                    $updateSql = "UPDATE Accounts SET last_login = NOW() WHERE account_id = ?";
                    $updateStmt = mysqli_prepare($conn, $updateSql);
                    mysqli_stmt_bind_param($updateStmt, "i", $account_id);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);

                    // Store session data
                    $_SESSION['account_id'] = $account_id;
                    $_SESSION['user_id']    = (int)$user['user_id'];
                    $_SESSION['role']       = $role;
                    $_SESSION['full_name']  = $user['first_name'] . ' ' . $user['last_name'];

                    // Redirect after successful login
                    header("Location: home.php");
                    exit;
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Log In | Tutoring System</title>
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
            background: radial-gradient(circle at top left, #22c55e 0, #111827 45%, #020617 100%);
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
        .form-control {
            background-color: #020617;
            border-color: #374151;
            color: #e5e7eb;
        }
        .form-control:focus {
            background-color: #020617;
            border-color: #22c55e;
            box-shadow: 0 0 0 0.2rem rgba(34, 197, 94, 0.35);
            color: #f9fafb;
        }
        .form-label {
            color: #e5e7eb;
        }
        .btn-success {
            background: linear-gradient(to right, #22c55e, #16a34a);
            border: none;
            box-shadow: 0 12px 30px rgba(22, 163, 74, 0.45);
        }
        .btn-success:hover {
            background: linear-gradient(to right, #16a34a, #15803d);
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
                        <h1 class="h3 fw-bold mb-1">Welcome back</h1>
                        <p class="small text-muted mb-0">
                            Log in to access your tutoring dashboard.
                        </p>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $err): ?>
                                <div><?php echo htmlspecialchars($err); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
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

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 mb-2">
                            Log in
                        </button>

                        <p class="small small-link text-center mt-2 mb-0">
                            Don’t have an account yet?
                            <a href="signup.php">Create one</a>
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
</body>
</html>

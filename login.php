<?php
session_start();
include "db.php"; // Connect to MySQL
include "navbar.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email exists
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {

        $user = mysqli_fetch_assoc($result);

        // Verify hashed password
        if (password_verify($password, $user['password'])) {

            // Store user info in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'student') {
                header("Location: home.php");
            } 
            elseif ($user['role'] == 'tutor') {
                header("Location: tutor_dashboard.php");
            } 
            else {
                header("Location: index.php");
            }
            exit();

        } else {
            echo "<script>alert('Incorrect password');</script>";
        }

    } else {
        echo "<script>alert('Email not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #f4f6ff;">




<!-- LOGIN FORM -->
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">

      <div class="card shadow p-4 border-0" style="border-radius: 20px;">
        <h2 class="fw-bold text-center mb-4">Login</h2>

        <form method="POST" action="login.php">

          <div class="mb-3">
            <label class="fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control p-3" placeholder="yourname@example.com" required>
          </div>

          <div class="mb-3">
            <label class="fw-bold">Password</label>
            <input type="password" name="password" class="form-control p-3" placeholder="Enter password" required>
          </div>

          <div class="mb-3 text-end">
            <a href="#" class="text-primary">Forgot Password?</a>
          </div>

          <button class="btn btn-primary w-100 p-3 fw-bold" style="border-radius: 12px;">
            Login
          </button>

        </form>

        <p class="text-center mt-3">
          Don’t have an account?
          <a href="signup.php" class="fw-bold text-primary">Sign up here</a>
        </p>

      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

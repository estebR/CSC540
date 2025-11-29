<?php
session_start();
include "db.php"; // connects to tutoring_system database
include "navbar.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name  = $_POST['full_name'];
    $email = $_POST['email'];
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];
    $role  = $_POST['account_type'];

    // Check passwords match
    if ($pass1 !== $pass2) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password, role)
                VALUES ('$name', '$email', '$hashed', '$role')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Account created successfully!'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #f4f6ff;">




<!-- SIGNUP FORM -->
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">

      <div class="card shadow p-4 border-0" style="border-radius: 20px;">
        <h2 class="fw-bold text-center mb-4">Create Your Account</h2>

        <form method="POST" action="signup.php">

          <div class="mb-3">
            <label class="fw-bold">Full Name</label>
            <input type="text" name="full_name" class="form-control p-3" placeholder="Enter full name" required>
          </div>

          <div class="mb-3">
            <label class="fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control p-3" placeholder="yourname@example.com" required>
          </div>

          <div class="mb-3">
            <label class="fw-bold">Password</label>
            <input type="password" name="password" class="form-control p-3" placeholder="Enter password" required>
          </div>

          <div class="mb-3">
            <label class="fw-bold">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control p-3" placeholder="Re-enter password" required>
          </div>

          <div class="mb-3">
            <label class="fw-bold">Account Type</label>
            <select name="account_type" class="form-select p-3" required>
              <option value="">Select account type</option>
              <option value="student">Student</option>
              <option value="tutor">Tutor</option>
            </select>
          </div>

          <button class="btn btn-primary w-100 p-3 fw-bold" style="border-radius: 12px;">
            Sign Up
          </button>

        </form>

        <p class="text-center mt-3">
          Already have an account?
          <a href="login.php" class="fw-bold text-primary">Login here</a>
        </p>

      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

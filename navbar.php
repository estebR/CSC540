<?php
// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
  <div class="container">
    <a class="navbar-brand" href="index.php">TutorHub</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">

        <!-- Always visible -->
        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="schedule.php">Schedule Appointment</a></li>
        <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>

        <!-- Visible ONLY when NOT logged in -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>
            <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <?php endif; ?>

        <!-- Visible ONLY when logged in -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <a class="nav-link text-warning fw-bold" href="logout.php">Logout</a>
            </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: #0f172a;">
    <div class="container">

        <a class="navbar-brand fw-bold" href="home.php">Tutoring System</a>

        <button class="navbar-toggler" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" 
                aria-controls="navbarNav"
                aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- LEFT SIDE: ALL MENU OPTIONS -->
            <ul class="navbar-nav me-auto">

                <?php if (isset($_SESSION['role'])): ?>

                    <!-- Student options -->
                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link fancy-link" href="schedule.php">Schedule</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link fancy-link" href="contact.php">Contact</a>
                        </li>
                    <?php endif; ?>

                    <!-- Tutor options -->
                    <?php if ($_SESSION['role'] === 'tutor'): ?>
                        <li class="nav-item">
                            <a class="nav-link fancy-link" href="tutor_dashboard.php">
                                Tutor Dashboard
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link fancy-link" href="tutor_appointments.php">
                                Appointments
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Admin options -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link fancy-link" href="admin_dashboard.php">
                                Admin Panel
                            </a>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Guest left-side links -->
                    <li class="nav-item">
                        <a class="nav-link fancy-link" href="login.php">Login</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fancy-link" href="signup.php">Sign Up</a>
                    </li>
                <?php endif; ?>

            </ul>

            <!-- RIGHT SIDE: NAME + LOGOUT -->
            <ul class="navbar-nav ms-auto">

                <?php if (isset($_SESSION['account_id'])): ?>

                    <li class="nav-item">
                        <span class="nav-link text-info">
                            Hi, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="logout.php"
                           style="transition: 0.25s;">
                            Logout
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>

<!-- Smooth glowing effect -->
<style>
    .fancy-link {
        position: relative;
        color: #cbd5f5 !important;
        font-weight: 500;
        transition: 0.25s ease;
    }

    .fancy-link:hover {
        color: #22c55e !important;
        text-shadow: 0 0 10px rgba(34, 197, 94, 0.7);
        transform: translateY(-1px);
    }

    .navbar-brand:hover {
        color: #22c55e !important;
        text-shadow: 0 0 8px rgba(34, 197, 94, 0.7);
        transition: 0.25s;
    }
</style>

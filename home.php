<?php include "navbar.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TutorHub | Student Tutoring System</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #f2f6ff;">

<section class="container py-5 d-flex align-items-center">
  <div class="row align-items-center">
    <div class="col-md-6 mb-4 mb-md-0">
      <h1 class="fw-bold display-5">Unlock Your Academic Potential with Expert Tutoring</h1>
      <p class="mt-3 text-secondary">
        Connect with qualified tutors, schedule personalized sessions, and reach your academic goals through our student-focused tutoring platform.
      </p>

      <div class="mt-4">
        <a href="signup.php" class="btn btn-primary me-3">Find a Tutor</a>
        <a href="login.php" class="btn btn-outline-primary">Schedule a Session</a>
      </div>
    </div>

    <div class="col-md-6 text-center">
      <img
        src="https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=900&q=60"
        class="img-fluid rounded-4 shadow-lg"
        alt="Students studying together"
      >
    </div>
  </div>
</section>

<section class="container py-5">
  <h2 class="text-center fw-bold">Our Services</h2>
  <p class="text-center text-secondary mb-5">
    Flexible tutoring options designed to support students at every stage of their academic journey.
  </p>

  <div class="row g-4 justify-content-center">

    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card h-100">
        <h5 class="fw-bold">One-on-One Tutoring</h5>
        <p class="text-secondary mt-2">
          Personalized sessions tailored to your learning style, pace, and course requirements.
        </p>
        <a href="#" class="fw-bold text-primary text-decoration-none">Learn More →</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card h-100">
        <h5 class="fw-bold">Group Sessions</h5>
        <p class="text-secondary mt-2">
          Collaborate with classmates in small groups and review key concepts together.
        </p>
        <a href="#" class="fw-bold text-primary text-decoration-none">Learn More →</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card h-100">
        <h5 class="fw-bold">Online Tutoring</h5>
        <p class="text-secondary mt-2">
          Join virtual sessions from anywhere with full access to your tutors and resources.
        </p>
        <a href="#" class="fw-bold text-primary text-decoration-none">Learn More →</a>
      </div>
    </div>

  </div>
</section>

<footer class="text-white py-5" style="background-color: #0d1b48;">
  <div class="container d-flex justify-content-between flex-wrap gap-4">

    <div>
      <h4 class="fw-bold">TutorHub</h4>
      <p class="text-light mb-1">
        Empowering students through personalized, high-quality tutoring support.
      </p>
      <p class="text-light mb-0">
        Built to make finding and managing tutoring sessions simple and efficient.
      </p>
    </div>

    <div>
      <h5 class="fw-bold mb-2">Services</h5>
      <p class="mb-1">One-on-One Tutoring</p>
      <p class="mb-1">Group Sessions</p>
      <p class="mb-1">Online Tutoring</p>
      <p class="mb-0">Test Preparation</p>
    </div>

  </div>

  <hr class="border-light mt-4">
  <p class="text-center text-light mb-0">© 2025 TutorHub. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

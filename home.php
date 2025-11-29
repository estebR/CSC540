
<?php include "navbar.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Tutoring System</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body style="background-color: #f2f6ff;">

<!-- ================= NAVBAR ================= -->


<!-- ================= HERO SECTION ================= -->
<section class="container py-5 d-flex align-items-center">
  <div class="row align-items-center">
    <div class="col-md-6">
      <h1 class="fw-bold display-5">Unlock Your Academic Potential with Expert Tutoring</h1>
      <p class="mt-3 text-secondary">
        Connect with qualified tutors, schedule personalized sessions, and achieve academic goals with our comprehensive tutoring platform.
      </p>

      <div class="mt-4">
        <a href="signup.html" class="btn btn-primary me-3">Find a Tutor</a>
        <a href="schedule.html" class="btn btn-outline-primary">Schedule Session</a>
      </div>
    </div>

    <div class="col-md-6 text-center">
      <img src="https://images.unsplash.com/photo-1588072432836-e10032774350?auto=format&fit=crop&w=900&q=60"
           class="img-fluid rounded-4 shadow-lg" alt="students">
    </div>
  </div>
</section>


<!-- ================= SERVICES SECTION ================= -->
<section class="container py-5">
  <h2 class="text-center fw-bold">Our Services</h2>
  <p class="text-center text-secondary mb-5">
    Comprehensive tutoring solutions designed to help students excel in their academic journey.
  </p>

  <div class="row g-4 justify-content-center">
    
    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card">
        <h5 class="fw-bold">One-on-One Tutoring</h5>
        <p class="text-secondary mt-2">Personalized sessions tailored to your learning needs and pace.</p>
        <a href="#" class="fw-bold text-primary">Learn More →</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card">
        <h5 class="fw-bold">Group Sessions</h5>
        <p class="text-secondary mt-2">Collaborative learning environments with peers in small groups.</p>
        <a href="#" class="fw-bold text-primary">Learn More →</a>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm p-4 service-card">
        <h5 class="fw-bold">Online Tutoring</h5>
        <p class="text-secondary mt-2">Virtual sessions accessible from anywhere, anytime.</p>
        <a href="#" class="fw-bold text-primary">Learn More →</a>
      </div>
    </div>

  </div>
</section>


<!-- ================= FOOTER ================= -->
<footer class="text-white py-5" style="background-color: #0d1b48;">
  <div class="container d-flex justify-content-between flex-wrap">

    <div>
      <h4 class="fw-bold">TutorHub</h4>
      <p class="text-light">Empowering students to achieve academic excellence through personalized tutoring.</p>
    </div>

    <div>
      <h5 class="fw-bold">Services</h5>
      <p>One-on-One Tutoring</p>
      <p>Group Sessions</p>
      <p>Online Tutoring</p>
      <p>Test Preparation</p>
    </div>

    <div>
      <h5 class="fw-bold">Subjects</h5>
      <p>Mathematics</p>
      <p>Computer Science</p>
      <p>Chemistry</p>
      <p>Physics</p>
    </div>

    <div>
      <h5 class="fw-bold">Company</h5>
      <p>About Us</p>
      <p>Contact</p>
      <p>Privacy Policy</p>
      <p>Terms of Service</p>
    </div>

  </div>

  <hr class="border-light mt-4">
  <p class="text-center text-light">© 2025 TutorHub. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

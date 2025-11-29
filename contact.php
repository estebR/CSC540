
<?php include "navbar.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Icons (Bootstrap Icons CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="contact.css"> 
</head>

<body style="background-color: #f4f7ff;">


<!-- ================= CONTACT SECTION ================= -->
<section class="container py-5">
  <div class="row justify-content-between">

    <!-- LEFT SIDE INFO -->
    <div class="col-md-5">
      <h2 class="fw-bold mb-3">Get in Touch</h2>
      <p class="text-secondary mb-4">
        Have questions? We're here to help you succeed in your academic journey.
      </p>

      <div class="d-flex align-items-start mb-4">
        <div class="me-3">
          <i class="bi bi-telephone-fill text-primary fs-3"></i>
        </div>
        <div>
          <h6 class="fw-bold">Phone</h6>
          <p class="text-secondary mb-0">(555) 123-4567</p>
        </div>
      </div>

      <div class="d-flex align-items-start mb-4">
        <div class="me-3">
          <i class="bi bi-envelope-fill text-primary fs-3"></i>
        </div>
        <div>
          <h6 class="fw-bold">Email</h6>
          <p class="text-secondary mb-0">info@tutorhub.com</p>
        </div>
      </div>

      <div class="d-flex align-items-start">
        <div class="me-3">
          <i class="bi bi-geo-alt-fill text-primary fs-3"></i>
        </div>
        <div>
          <h6 class="fw-bold">Address</h6>
          <p class="text-secondary mb-0">123 University Ave, Academic City, AC 12345</p>
        </div>
      </div>

    </div>

    
    <!-- RIGHT SIDE FORM -->
    <div class="col-md-6">
      <div class="card shadow p-4 border-0" style="border-radius: 20px;">
        <form>

          <div class="mb-3">
            <label class="form-label fw-bold">Full Name</label>
            <input type="text" class="form-control p-3" placeholder="Enter your name">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Email Address</label>
            <input type="email" class="form-control p-3" placeholder="Enter your email">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Subject</label>
            <input type="text" class="form-control p-3" placeholder="What is this regarding?">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Message</label>
            <textarea class="form-control p-3" rows="5" placeholder="Type your message here..."></textarea>
          </div>

          <button class="btn btn-primary w-100 p-3 fw-bold" style="border-radius: 12px;">
            Send Message
          </button>

        </form>
      </div>
    </div>

  </div>
</section>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

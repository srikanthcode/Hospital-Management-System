<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dr. Kavya Rani - Lotus Women's Hospital</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<!-- ================= SINGLE NAVBAR ================= -->
<nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo3.jpeg" alt="Lotus Women's Hospital" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Lotus Women's Hospital</span>
                <span class="brand-tagline">Women's Healthcare</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#services">Services</a></li>
                <li class="nav-item"><a class="nav-link active" href="index.php#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="nav-btn-emergency" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                    + Emergency
                </button>
                <a href="login.php" class="nav-btn-login">Login</a>
            </div>
        </div>
    </div>
</nav>

<!-- ================= EMERGENCY MODAL ================= -->

<div class="modal fade" id="emergencyModal">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title">
                    Emergency Contacts
                </h5>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body text-center">

                <h5>Hospital Enquiry</h5>

                <p>+91 9876543210</p>

                <a href="tel:+919876543210"
                   class="btn btn-primary mb-3">

                    Call Enquiry

                </a>

                <hr>

                <h5>Ambulance</h5>

                <p>+91 9876543211</p>

                <a href="tel:+919876543211"
                   class="btn btn-danger">

                    Call Ambulance

                </a>

            </div>

        </div>

    </div>

</div>

<!-- ================= DOCTOR HERO ================= -->

<section class="doctor-profile-hero py-5">

    <div class="container text-center">

        <h1 class="fw-bold pink-heading">
            Dr. Kavya Rani
        </h1>

        <p class="text-muted">
            Laparoscopic Surgeon
        </p>

    </div>

</section>

<!-- ================= DOCTOR PROFILE ================= -->

<section class="doctor-profile-section py-5">

    <div class="container">

        <div class="row align-items-center g-5">

            <!-- Doctor Image -->

            <div class="col-md-5 text-center">

                <img src="assets/images/doctor3.jpeg"
                     class="img-fluid rounded shadow doctor-profile-img"
                     alt="Dr. Kavya Rani">

            </div>

            <!-- Doctor Details -->

            <div class="col-md-7">

                <h2 class="pink-heading fw-bold mb-3">
                    Dr. Kavya Rani
                </h2>

                <h5 class="pink-text fw-semibold">
                    Laparoscopic Surgeon
                </h5>

                <p class="mt-3">
                    Dr. Kavya Rani is an experienced laparoscopic surgeon
                    specializing in minimally invasive gynecological procedures
                    with advanced surgical expertise.
                </p>

                <div class="doctor-info mt-4">

                    <p>
                        <strong>Qualification:</strong><br>
                        MBBS, MS
                    </p>

                    <p>
                        <strong>Specialization:</strong><br>
                        Laparoscopic &amp; Hysteroscopic Surgery
                    </p>

                    <p>
                        <strong>Experience:</strong><br>
                        10+ Years
                    </p>

                    <p>
                        <strong>Department:</strong><br>
                        Surgery &amp; Laparoscopy
                    </p>

                    <p>
                        <strong>Consultation:</strong><br>
                        In-person consultation
                    </p>

                </div>

                <a href="login.php"
                   class="btn pink-btn mt-3">

                    Book Appointment

                </a>

                <a href="index.php#doctors"
                   class="btn pink-outline-btn mt-3 ms-2">

                    Back to Doctors

                </a>

            </div>

        </div>

    </div>

</section>

<!-- ================= ABOUT DOCTOR ================= -->

<section class="py-5">

    <div class="container">

        <div class="text-center mb-4">

            <h2 class="pink-heading fw-bold">
                About Dr. Kavya Rani
            </h2>

        </div>

        <div class="doctor-about-box">

            <p>
                Dr. Kavya Rani is an experienced laparoscopic surgeon
                at Lotus Women's Hospital. She specializes in minimally
                invasive gynecological procedures with advanced surgical
                expertise, ensuring safe and effective treatment.
            </p>

            <p>
                She handles complex cases including ectopic pregnancy,
                ovarian cysts, uterine fibroids, endometriosis, and more.
                Her approach focuses on precision and faster patient recovery.
            </p>

            <p>
                She works closely with the hospital's healthcare team to
                provide safe, advanced, and personalized surgical care to patients.
            </p>

        </div>

    </div>

</section>

<!-- ================= AREAS OF EXPERTISE ================= -->

<section class="py-5 bg-light">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="pink-heading fw-bold">
                Areas of Expertise
            </h2>

        </div>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="service-box p-4 text-center h-100">

                    <h5>Laparoscopic Surgery</h5>

                    <p>
                        Minimally invasive surgical procedures with
                        faster recovery and reduced complications.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-box p-4 text-center h-100">

                    <h5>Hysteroscopic Procedures</h5>

                    <p>
                        Advanced diagnostic and therapeutic
                        hysteroscopic treatments for gynecological conditions.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-box p-4 text-center h-100">

                    <h5>Gynecological Surgery</h5>

                    <p>
                        Expert treatment for ovarian cysts, fibroids,
                        endometriosis, and ectopic pregnancy.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ================= CONSULTATION SCHEDULE ================= -->

<section class="py-5">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="pink-heading fw-bold">
                Consultation Schedule
            </h2>

            <p class="text-muted">
                Consultation timings are shown below.
            </p>

        </div>

        <div class="table-responsive">

            <table class="table table-bordered text-center">

                <thead>

                    <tr>
                        <th>Day</th>
                        <th>Consultation Time</th>
                    </tr>

                </thead>

                <tbody>

                    <tr>
                        <td>Monday</td>
                        <td>4:00 PM - 7:00 PM</td>
                    </tr>

                    <tr>
                        <td>Tuesday</td>
                        <td>10:00 AM - 1:00 PM</td>
                    </tr>

                    <tr>
                        <td>Wednesday</td>
                        <td>4:00 PM - 7:00 PM</td>
                    </tr>

                    <tr>
                        <td>Thursday</td>
                        <td>10:00 AM - 1:00 PM</td>
                    </tr>

                    <tr>
                        <td>Friday</td>
                        <td>4:00 PM - 7:00 PM</td>
                    </tr>

                    <tr>
                        <td>Saturday</td>
                        <td>10:00 AM - 1:00 PM</td>
                    </tr>

                    <tr>
                        <td>Sunday</td>
                        <td>Not Available</td>
                    </tr>

                </tbody>

            </table>

        </div>

    </div>

</section>

<!-- ================= APPOINTMENT CTA ================= -->

<section class="py-5">

    <div class="container">

        <div class="text-center p-5 rounded shadow doctor-appointment-box">

            <h2 class="fw-bold">
                Need a Consultation?
            </h2>

            <p>
                Book an appointment with Dr. Kavya Rani
                at Lotus Women's Hospital.
            </p>

            <a href="login.php"
                class="btn pink-btn mt-3">
                Book Appointment
            </a>
            <a href="index.php#doctors"
                class="btn pink-outline-btn mt-3 ms-2">
                Back to Doctors
            </a>

        </div>

    </div>

</section>

<!-- ================= FOOTER ================= -->

<footer class="footer text-center py-4">

    <div class="container">

        <h5>
            Lotus Women's Hospital
        </h5>

        <p class="mb-1">
            Compassionate Care. Advanced Healthcare. Better Lives.
        </p>

        <p class="mb-0">
            © 2026 Lotus Women's Hospital. All Rights Reserved.
        </p>

    </div>

</footer>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->

<script src="assets/js/script.js"></script>

</body>

</html>
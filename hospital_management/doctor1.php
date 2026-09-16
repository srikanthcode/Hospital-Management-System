<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dr. Anitha Devi - Lotus Women's Hospital</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<!-- ================= HEADER START ================= -->

<header class="header">

    <!-- Logo -->
    <div class="logo-area">

        <img src="assets/images/logo3.jpeg" alt="Hospital Logo">

        <div class="hospital-name">
            Lotus Women's Hospital
        </div>

    </div>

    <!-- Emergency Button -->
    <button type="button"
            class="emergency-btn"
            data-bs-toggle="modal"
            data-bs-target="#emergencyModal">

        + Emergency

    </button>

    <!-- Hamburger Menu -->
    <div class="menu-icon" onclick="toggleMenu()">
        ☰
    </div>

</header>

<!-- ================= NAVIGATION ================= -->

<nav class="navbar-custom" id="navbar">

    <a href="index.php">Home</a>

    <a href="about.php">About</a>

    <a href="index.php#services">Services</a>

    <a href="index.php#doctors">Doctors</a>

    <a href="index.php#contact">Contact</a>

    <a href="login.php">Login</a>

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
            Dr. Anitha Devi
        </h1>

        <p class="text-muted">
            Senior Obstetrician &amp; Gynaecologist
        </p>

    </div>

</section>

<!-- ================= DOCTOR PROFILE ================= -->

<section class="doctor-profile-section py-5">

    <div class="container">

        <div class="row align-items-center g-5">

            <!-- Doctor Image -->

            <div class="col-md-5 text-center">

                <img src="assets/images/doctor1.jpeg"
                     class="img-fluid rounded shadow doctor-profile-img"
                     alt="Dr. Anitha Devi">

            </div>

            <!-- Doctor Basic Details -->

            <div class="col-md-7">

                <h2 class="pink-heading fw-bold mb-3">
                    Dr. Anitha Devi
                </h2>

                <h5 class="pink-text fw-semibold">
                    Senior Obstetrician &amp; Gynaecologist
                </h5>

                <p class="mt-3">
                    Dr. Anitha Devi is an experienced specialist dedicated
                    to providing compassionate and personalized healthcare
                    for women.
                </p>

                <div class="doctor-info mt-4">

                    <p>
                        <strong>Qualification:</strong><br>
                        MBBS, MD (Obstetrics &amp; Gynaecology)
                    </p>

                    <p>
                        <strong>Specialization:</strong><br>
                        Obstetrics, Gynaecology, Maternity Care &amp;
                        High-Risk Pregnancy
                    </p>

                    <p>
                        <strong>Experience:</strong><br>
                        15+ Years
                    </p>

                    <p>
                        <strong>Department:</strong><br>
                        Obstetrics &amp; Gynaecology
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
                About Dr. Anitha Devi
            </h2>

        </div>

        <div class="doctor-about-box">

            <p>
                Dr. Anitha Devi is a senior obstetrician and gynaecologist
                at Lotus Women's Hospital. She is committed to providing
                quality healthcare with a patient-centred approach.
            </p>

            <p>
                Her areas of care include pregnancy monitoring, maternity
                care, women's health, and management of high-risk pregnancy.
                She focuses on creating a comfortable environment where
                patients can discuss their healthcare concerns with confidence.
            </p>

            <p>
                At Lotus Women's Hospital, she works with the healthcare team
                to provide safe and personalized care throughout the patient's
                treatment journey.
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

                    <h5>Pregnancy Care</h5>

                    <p>
                        Regular pregnancy monitoring and personalized
                        maternity care.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-box p-4 text-center h-100">

                    <h5>High-Risk Pregnancy</h5>

                    <p>
                        Specialized monitoring and care for high-risk
                        pregnancy cases.
                    </p>

                </div>

            </div>

            <div class="col-md-4">

                <div class="service-box p-4 text-center h-100">

                    <h5>Women's Health</h5>

                    <p>
                        Comprehensive gynaecological care for women
                        at different stages of life.
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
                        <td>10:00 AM - 1:00 PM</td>
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
                Book an appointment with Dr. Anitha Devi
                at Lotus Women's Hospital.
            </p>

            <a href="login.php"
               class="btn pink-btn">

                Book Appointment

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
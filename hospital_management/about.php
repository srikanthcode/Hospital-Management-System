<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Lotus Women's Hospital</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- About CSS -->
    <link rel="stylesheet" href="assets/css/about.css">
</head>

<body>

<!-- ================= HEADER ================= -->

<header class="header">

    <div class="logo-area">
        <img src="assets/images/logo2.jpeg" alt="Hospital Logo">

        <div class="hospital-name">
            Lotus Women's Hospital
        </div>
    </div>

    <button type="button"
            class="emergency-btn"
            data-bs-toggle="modal"
            data-bs-target="#emergencyModal">
        + Emergency
    </button>

    <div class="menu-icon" onclick="toggleMenu()">
        ☰
    </div>

</header>

<!-- Navigation -->

<nav class="navbar-custom" id="navbar">

    <a href="index.php">Home</a>
    <a href="about.php" class="active">About</a>
    <a href="index.php#services">Services</a>
    <a href="index.php#doctors">Doctors</a>
    <a href="index.php#contact">Contact</a>
    <a href="login.php">Login</a>

</nav>

<!-- ================= ABOUT BANNER ================= -->

<section class="about-banner">

    <div class="container text-center">

        <h1>About Us</h1>

        <p>
            Home / About Us
        </p>

    </div>

</section>

<!-- ================= WELCOME SECTION ================= -->

<section class="py-5">

    <div class="container">

        <div class="row align-items-center">

            <!-- Image -->
            <div class="col-lg-6 mb-4">

                <img src="assets/images/hospital-building.jpeg"
                     class="img-fluid rounded shadow"
                     alt="Hospital Building">

            </div>

            <!-- Content -->
            <div class="col-lg-6">

                <h2 class="text-primary fw-bold mb-4">
                    Welcome to Lotus Women's Hospital
                </h2>

                <p>
                    Lotus Women's Hospital is dedicated to providing exceptional healthcare
                    services exclusively for women. Our experienced team of doctors,
                    nurses, and healthcare professionals delivers compassionate,
                    personalized, and high-quality medical care in a safe and modern
                    environment.
                </p>

                <p>
                    We specialize in obstetrics, gynecology, fertility care,
                    laparoscopic surgery, maternity services, neonatal care,
                    preventive health check-ups, and advanced women's healthcare.
                </p>

                <p>
                    Our mission is to ensure every woman receives expert medical
                    treatment with dignity, comfort, and respect throughout every
                    stage of her life.
                </p>

            </div>

        </div>

    </div>

</section>
<!-- ================= OUR STORY ================= -->

<section class="py-5 bg-light">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="text-primary fw-bold">
                Our Story
            </h2>

            <p class="text-muted">
                Caring for women with compassion, trust, and excellence.
            </p>

        </div>

        <div class="row">

            <div class="col-lg-6">

                <p>
                    Lotus Women's Hospital was established with a vision to provide
                    world-class healthcare exclusively for women. From adolescence
                    to motherhood and beyond, we are committed to delivering
                    compassionate, safe, and personalized medical care.
                </p>

                <p>
                    Over the years, our hospital has earned the trust of thousands
                    of families through advanced medical technology, experienced
                    specialists, and a patient-first approach.
                </p>

                <p>
                    Every mother and every woman deserves quality healthcare in a
                    comfortable environment. Our dedicated team works tirelessly to
                    ensure the highest standards of treatment and patient safety.
                </p>

            </div>

            <div class="col-lg-6">

                <img src="assets/images/about-story.jpeg"
                     class="img-fluid rounded shadow"
                     alt="Our Story">

            </div>

        </div>

    </div>

</section>
<!-- ================= FOUNDER'S MESSAGE ================= -->

<section class="py-5 bg-light">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">
                Founder's Message
            </h2>

            <p class="text-muted">
                A message from the founder of Lotus Women's Hospital.
            </p>
        </div>

        <div class="row align-items-center">

            <!-- Founder Image -->
            <div class="col-lg-4 text-center">

                <img src="assets/images/founder.jpeg"
                     class="img-fluid rounded shadow"
                     alt="Founder"
                     style="max-width:300px;">

                <h4 class="mt-3 mb-1">
                    Dr. Lakshmi Narayanan
                </h4>

                <p class="text-muted">
                    Founder, Lotus Women's Hospital
                </p>

            </div>

            <!-- Founder Message -->
            <div class="col-lg-8">

                <div class="card border-0 shadow p-4">

                    <h3 class="text-primary mb-3">
                        A Message from Our Founder
                    </h3>

                    <p>
                        "At Lotus Women's Hospital, our dream has always been to
                        create a place where every woman feels safe, respected,
                        and cared for. We believe that healthcare is not just about
                        treatment—it is about compassion, trust, and hope.
                    </p>

                    <p>
                        Every patient who walks through our doors deserves the
                        highest quality medical care delivered with kindness and
                        dignity. Our dedicated team works tirelessly to ensure the
                        well-being of every mother, every woman, and every family
                        we serve.
                    </p>

                    <p>
                        Thank you for placing your trust in us. We remain committed
                        to serving our community with excellence for generations to
                        come."
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ================= QUOTE ================= -->

<section class="pb-5">

    <div class="container">

        <div class="card border-start border-5 border-primary shadow-sm p-4">

            <blockquote class="blockquote mb-0 text-center">

                <p class="fs-4 fst-italic">
                    "Every woman deserves compassionate care, every mother deserves a safe journey, and every family deserves hope."
                </p>

                <footer class="blockquote-footer mt-3">
                    Founder, Lotus Women's Hospital
                </footer>

            </blockquote>

        </div>

    </div>

</section>
<!-- ================= OUR VISION ================= -->

<section class="container py-5">

    <div class="text-center mb-4">

        <button class="btn btn-primary btn-lg"
                data-bs-toggle="collapse"
                data-bs-target="#visionCollapse"
                aria-expanded="false">

            Our Vision

        </button>

    </div>

    <div class="collapse" id="visionCollapse">

        <div class="card border-0 shadow-lg p-4">

            <div class="row align-items-center">

                <!-- Image -->
                <div class="col-lg-5 mb-4 mb-lg-0">

                    <img src="assets/images/vision.jpeg"
                         class="img-fluid rounded"
                         alt="Our Vision">

                </div>

                <!-- Content -->
                <div class="col-lg-7">

                    <h2 class="text-primary fw-bold mb-3">
                        Our Vision
                    </h2>

                    <p>
                        At <strong>Lotus Women's Hospital</strong>, our vision is to become
                        the most trusted destination for women's healthcare by delivering
                        exceptional medical services with compassion, innovation, and
                        excellence.
                    </p>

                    <p>
                        We strive to create a healthier future for women by providing
                        advanced medical technology, experienced specialists, and
                        personalized care in a safe and welcoming environment.
                    </p>

                    <p>
                        Our goal is to empower every woman with quality healthcare,
                        ensuring dignity, confidence, and well-being at every stage
                        of life.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- ================= OUR MISSION ================= -->

<section class="container py-5">

    <div class="text-center mb-4">

        <button class="btn btn-danger btn-lg"
                data-bs-toggle="collapse"
                data-bs-target="#missionCollapse"
                aria-expanded="false">

            Our Mission

        </button>

    </div>

    <div class="collapse" id="missionCollapse">

        <div class="card border-0 shadow-lg p-4">

            <div class="row align-items-center">

                <!-- Image -->
                <div class="col-lg-5 mb-4 mb-lg-0">

                    <img src="assets/images/mission.jpeg"
                         class="img-fluid rounded"
                         alt="Our Mission">

                </div>

                <!-- Content -->
                <div class="col-lg-7">

                    <h2 class="text-danger fw-bold mb-3">
                        Our Mission
                    </h2>

                    <p>
                        At <strong>Lotus Women's Hospital</strong>, our mission is to provide
                        comprehensive, compassionate, and affordable healthcare services
                        for women at every stage of life.
                    </p>

                    <p>
                        We are committed to delivering safe maternity care, advanced
                        gynecological treatments, fertility services, and preventive
                        healthcare using modern medical technology and evidence-based
                        practices.
                    </p>

                    <p>
                        Through experienced doctors, skilled nurses, and dedicated
                        healthcare professionals, we strive to ensure every patient
                        receives personalized care with dignity, respect, and excellence.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>
<!-- =================  VALUES ================= -->

<section class="container py-5">

    <div class="text-center mb-4">

        <button class="btn btn-success btn-lg"
                data-bs-toggle="collapse"
                data-bs-target="#valuesCollapse"
                aria-expanded="false">

            Our Core Values

        </button>

    </div>

    <div class="collapse" id="valuesCollapse">

        <div class="card border-0 shadow-lg p-4">

            <div class="row align-items-center">

                <!-- Image -->
                <div class="col-lg-5 mb-4 mb-lg-0">

                    <img src="assets/images/values.jpeg"
                         class="img-fluid rounded"
                         alt=" Values">

                </div>

                <!-- Content -->
                <div class="col-lg-7">

                    <h2 class="text-success fw-bold mb-3">
                         Values
                    </h2>

                    <div class="mb-3">
                        <h5>❤️ Compassion</h5>
                        <p>We treat every patient with kindness, empathy, respect, and personalized care.</p>
                    </div>

                    <div class="mb-3">
                        <h5>⭐ Excellence</h5>
                        <p>We strive to deliver the highest standards of medical care through continuous improvement and innovation.</p>
                    </div>

                    <div class="mb-3">
                        <h5>🤝 Integrity</h5>
                        <p>We uphold honesty, ethics, transparency, and professionalism in everything we do.</p>
                    </div>

                    <div class="mb-3">
                        <h5>🌸 Respect</h5>
                        <p>We value every woman and provide healthcare with dignity, privacy, and compassion.</p>
                    </div>

                    <div class="mb-3">
                        <h5>💡 Innovation</h5>
                        <p>We embrace modern medical technology and evidence-based practices to improve patient outcomes.</p>
                    </div>

                </div>

            </div>

        </div>
 </div>
</section>

<!-- ================= HOSPITAL STATISTICS ================= -->

<section class="py-5 bg-light">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">Hospital Statistics</h2>
        </div>

        <div class="row text-center">

            <div class="col-md-3 mb-4">
                <h1 class="text-danger fw-bold">15+</h1>
                <h5>Years of Experience</h5>
            </div>

            <div class="col-md-3 mb-4">
                <h1 class="text-danger fw-bold">5+</h1>
                <h5>Expert Doctors</h5>
            </div>

            <div class="col-md-3 mb-4">
                <h1 class="text-danger fw-bold">5000+</h1>
                <h5>Happy Patients</h5>
            </div>

            <div class="col-md-3 mb-4">
                <h1 class="text-danger fw-bold">24/7</h1>
                <h5>Emergency Care</h5>
            </div>

        </div>

    </div>

</section>

<!-- ================= CALL TO ACTION ================= -->

<section class="py-5 text-center">

    <div class="container">

        <h2 class="fw-bold text-primary">
            Your Health Is Our Priority
        </h2>

        <p class="lead">
            We are committed to providing compassionate and comprehensive healthcare for every woman.
        </p>

        <a href="appointment.php" class="btn btn-danger btn-lg mt-3">
            Book an Appointment
        </a>

    </div>

</section>

<!-- ================= FOOTER ================= -->

<footer class="bg-dark text-white py-4">

    <div class="container text-center">

        <h4>Lotus Women's Hospital</h4>

        <p>
            Dedicated to providing quality healthcare for women with compassion, care, and excellence.
        </p>

        <hr class="bg-light">

        <p class="mb-0">
            © 2026 Lotus Women's Hospital. All Rights Reserved.
        </p>

    </div>

</footer>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/script.js"></script>

</body>
</html>
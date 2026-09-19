<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lotus Women's Hospital</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<!-- ================= SINGLE NAVBAR ================= -->
<nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
        <!-- Logo + Hospital Name -->
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo3.jpeg" alt="Lotus Women's Hospital" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Lotus Women's Hospital</span>
                <span class="brand-tagline">Women's Healthcare</span>
            </div>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Nav Links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>

            <!-- Right Side Buttons -->
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="nav-btn-emergency" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                    + Emergency
                </button>
                <a href="login.php" class="nav-btn-login">Login</a>
            </div>
        </div>
    </div>
</nav>

<!-- Emergency Modal -->

<div class="modal fade" id="emergencyModal">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header bg-danger text-white">

                <h5 class="modal-title">
                    Emergency Contacts
                </h5>

                <button class="btn-close btn-close-white"
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
<!-- ================= HERO SECTION START ================= -->

<section id="home" class="hero-video-section">
    <div class="container-fluid p-0">
        <div class="video-wrapper">
            <video autoplay muted loop playsinline class="hero-video">
                <source src="assets/images/hospital_video.mp4" type="video/mp4">
            </video>
            <div class="video-overlay">
                <div class="video-caption">
                    <h1 class="mh" id="maskedHeading">
                        <span class="mh__measure">
                            <span class="mh__word" data-i="0">Lotus</span>
                            <span class="mh__word" data-i="1">Women's</span>
                            <span class="mh__word" data-i="2">Hospital</span>
                        </span>
                        <svg class="mh__svg" aria-hidden="true">
                            <defs>
                                <clipPath id="mhClip" clipPathUnits="userSpaceOnUse">
                                    <text class="mh__glyph" data-i="0">Lotus</text>
                                    <text class="mh__glyph" data-i="1">Women's</text>
                                    <text class="mh__glyph" data-i="2">Hospital</text>
                                </clipPath>
                            </defs>
                        </svg>
                        <span class="mh__reveal">
                            <span class="mh__clip" style="clip-path:url(#mhClip)">
                                <span class="mh__media">
                                    <video class="mh__source" src="assets/images/hospital_video.mp4" autoplay muted loop playsinline></video>
                                </span>
                            </span>
                        </span>
                    </h1>
                    <p class="typing-text" id="typingText"></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= HERO SECTION END ================= -->
<!-- ================= HOSPITAL OVERVIEW ================= -->

<section id="about" class="hospital-overview py-5">
    <div class="container">

        <h2 class="text-center pink-heading fw-bold mb-4">
            Welcome to Lotus Women's Hospital
        </h2>

        <p>
            <strong>Lotus Women's Hospital</strong> is committed to providing compassionate,
            safe, and personalized healthcare services for women at every stage of life.
            Our experienced team of obstetricians, gynaecologists, nurses, and healthcare
            professionals work together to ensure the highest quality maternity and women's
            healthcare in a comfortable, caring, and modern environment.
        </p>

        <div id="moreContent" style="display:none;">

            <p>
                We provide comprehensive services including pregnancy care, antenatal and
                postnatal care, normal and high-risk deliveries, infertility evaluation,
                family planning, adolescent and menopausal care, preventive health check-ups,
                and advanced gynaecological treatments. Our specialists focus on delivering
                personalized care tailored to every patient's unique healthcare needs.
            </p>

            <p>
                Our hospital is equipped with advanced medical technology, modern labour
                suites, well-equipped operation theatres, neonatal care facilities, and
                experienced medical professionals who are available around the clock. Every
                mother deserves a safe and memorable childbirth experience, and we are
                dedicated to providing exceptional medical care with compassion and respect.
            </p>

            <p>
                At Lotus Women's Hospital, we believe that every woman deserves trusted
                healthcare throughout her life. From adolescence to motherhood and beyond,
                our mission is to provide excellence in women's healthcare while ensuring
                comfort, dignity, and confidence for every patient and her family.
            </p>

        </div>

        <div class="text-center mt-4">
            <button id="readBtn"
                class="btn pink-outline-btn"
                onclick="toggleReadMore()">
                Read More
            </button>
        </div>

    </div>
</section>
<!-- ================= SERVICES SECTION START ================= -->

<section id="services" class="services-section py-5">

    <div class="container">

        <h2 class="text-center pink-heading fw-bold mb-5">
            Services
        </h2>

       <div class="row g-4">

    <!-- Pediatric Services -->
    <div class="col-md-6">
        <a href="pediatric.php" class="text-decoration-none">
            <div class="service-box p-4 text-center h-100">

                <img src="assets/images/pediatric.jpeg"
                     class="img-fluid rounded mb-3"
                     alt="Pediatric Services">

                <h5>Pediatric Services</h5>

                <p>
                    Comprehensive healthcare for infants, children, and adolescents,
                    including vaccinations, routine check-ups, growth monitoring,
                    and treatment by experienced pediatric specialists.
                </p>

            </div>
        </a>
    </div>

    <!-- Gynaecology Services -->
    <div class="col-md-6">
        <a href="gynaecology.php" class="text-decoration-none">
            <div class="service-box p-4 text-center h-100">

                <img src="assets/images/gynaecology.jpeg"
                     class="img-fluid rounded mb-3"
                     alt="Gynaecology Services">

                <h5>Gynaecology Services</h5>

                <p>
                    Comprehensive women's healthcare including diagnosis,
                    treatment, preventive care, fertility counselling,
                    and advanced gynaecological treatments.
                </p>

            </div>
        </a>
    </div>

</div>
        <!-- ================= DETAIL SECTION ================= -->

        <div class="row align-items-center mt-5">

            <!-- Image -->
            <div class="col-md-5">
                <img src="assets/images/laparoscopy.jpeg"
                     class="img-fluid rounded shadow"
                     alt="Laparoscopic Surgery">
            </div>

            <!-- Content -->
            <div class="col-md-7">

                <h4 class="pink-heading fw-bold mb-3">
                    Laparoscopic and Hysteroscopic Surgeries
                </h4>
                <p>
                    <strong>Lotus Women's Hospital</strong> is proud to be one of the leading centres
                    providing advanced laparoscopic and hysteroscopic surgical care for women.
                    We offer minimally invasive gynecological procedures with modern technology
                    and highly skilled surgical expertise, ensuring safe and
                    effective treatment with faster recovery.
                </p>

                <div id="moreServices" style="display:none;">

                    <p>
                        Some of the most complicated and difficult cases have been performed with high
                        patient satisfaction. We handle ectopic pregnancy, ovarian cysts, uterine fibroids,
                        endometriosis, prolapse, Ashermann’s syndrome and more.
                    </p>

                    <p>
                        Procedures include laparoscopic cystectomy, oophorectomy, myomectomy, hysterectomy,
                        endometriosis surgery, diagnostic hysteroscopy, polypectomy and more advanced treatments.
                    </p>

                </div>

                <button class="btn pink-outline-primary mt-3"
                        onclick="toggleServices()"
                        id="serviceBtn">

                    Read More

                </button>

            </div>

        </div>

    </div>

</section>

<!-- ================= SERVICES SECTION END ================= -->
<!-- ================= INFRASTRUCTURE SECTION ================= -->

<section class="infra-section py-5">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold pink-heading">
                Our Infrastructure
            </h2>

            <p class="text-muted">
                Experience world-class facilities designed to provide safe, comfortable, and advanced healthcare for women and newborns.
            </p>
        </div>

        <!-- Infrastructure Slider -->
        <div id="infraSlider" class="carousel slide carousel-fade"
             data-bs-ride="carousel"
             data-bs-interval="3000">

            <!-- Indicators -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="2"></button>
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="3"></button>
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="4"></button>
                <button type="button" data-bs-target="#infraSlider" data-bs-slide-to="5"></button>
            </div>

            <!-- Slides -->
            <div class="carousel-inner rounded shadow">
                <div class="carousel-item active">
                    <img src="assets/images/infra1.jpeg" class="d-block w-100 infra-img" alt="Infra 1">
                </div>
                <div class="carousel-item">
                    <img src="assets/images/infra2.jpeg" class="d-block w-100 infra-img" alt="Infra 2">
                </div>
                <div class="carousel-item">
                    <img src="assets/images/infra3.jpeg" class="d-block w-100 infra-img" alt="Infra 3">
                </div>
                <div class="carousel-item">
                    <img src="assets/images/infra4.jpeg" class="d-block w-100 infra-img" alt="Infra 4">
                </div>
                <div class="carousel-item">
                    <img src="assets/images/infra5.jpeg" class="d-block w-100 infra-img" alt="Infra 5">
                </div>
                <div class="carousel-item">
                    <img src="assets/images/infra6.jpeg" class="d-block w-100 infra-img" alt="Infra 6">
                </div>
            </div>

            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#infraSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#infraSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>

        </div>

    </div>

</section>

<!-- ================= DOCTORS SECTION START ================= -->

<section id="doctors" class="doctors-section py-5">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold pink-heading">
                Our Team of Doctors
            </h2>

            <p class="text-muted">
                Meet our experienced specialists dedicated to providing exceptional women's healthcare.
            </p>
        </div>

        <div class="row g-4">

            <!-- Doctor 1 -->
            <div class="col-md-4">
                <div class="card doctor-card h-100">

                    <img src="assets/images/doctor1.jpeg"
                         class="card-img-top doctor-img"
                         alt="Doctor">

                    <div class="card-body text-center">

                        <h5 class="card-title">
                            Dr. Anitha Devi
                        </h5>

                        <p class="pink-text fw-semibold">
                            Senior Obstetrician & Gynaecologist
                        </p>

                        <p class="text-muted">
                            Expert in maternity care, high-risk pregnancy management and women's health.
                        </p>

                        <a href="doctor1.php" class="btn pink-btn">
                              View Doctor
                        </a>

                    </div>

                </div>
            </div>

            <!-- Doctor 2 -->
            <div class="col-md-4">
                <div class="card doctor-card h-100">

                    <img src="assets/images/doctor2.jpeg"
                         class="card-img-top doctor-img"
                         alt="Doctor">

                    <div class="card-body text-center">

                        <h5 class="card-title">
                            Dr. Priya Sharma
                        </h5>

                        <p class="pink-text fw-semibold">
                            Prediction
                        </p>

                        <p class="text-muted">
                            MBBS, MS 
                        </p>

                        <a href="doctor2.php" class="btn pink-btn">
                            View Doctor
                        </a>

                    </div>

                </div>
            </div>

            <!-- Doctor 3 -->
            <div class="col-md-4">
                <div class="card doctor-card h-100">

                    <img src="assets/images/doctor3.jpeg"
                         class="card-img-top doctor-img"
                         alt="Doctor">

                    <div class="card-body text-center">

                        <h5 class="card-title">
                            Dr. Kavya Rani
                        </h5>

                        <p class="pink-text fw-semibold">
                            Laparoscopic Surgeon
                        </p>

                        <p class="text-muted">
                            Experienced in advanced laparoscopic and hysteroscopic procedures.
                        </p>

                        <a href="doctor3.php" class="btn pink-btn">
                            View Doctor
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>

</section>

<!-- ================= DOCTORS SECTION END ================= -->
<!-- ================= TESTIMONIAL SECTION START ================= -->

<section class="testimonial-section py-5">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold pink-heading">
                Patient Testimonials
            </h2>

            <p class="text-muted">
                Hear what our patients say about their experience at Lotus Women's Hospital.
            </p>
        </div>

        <div class="row g-4">

            <!-- Testimonial 1 -->
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="assets/images/doctor1.jpeg"
                        class="testimonial-img"
                        alt="Dr. priya sharma">
                    

                    <p class="testimonial-text">
                        "The doctors and nurses provided exceptional care throughout my pregnancy. Their kindness and professionalism made my journey to motherhood comfortable and stress-free."
                    </p>

                    <hr>

                    <h5 class="mb-1">Mrs. priya sharma</h5>
                    <small class="text-muted">Prediction</small>

                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="assets/images/doctor2.jpeg"
                    class="testimonial-img"
                    alt="Dr. Anitha Devi">

                    <p class="testimonial-text">
                        "The hospital has excellent facilities, experienced doctors, and caring staff. I received outstanding treatment and felt supported throughout my stay."
                    </p>

                    <hr>

                    <h5 class="mb-1">Mrs. anitha davi</h5>
                    <small class="text-muted">Gynaecology Patient</small>

                </div>
            </div>

            <!-- Testimonial 3 -->
            <div class="col-md-4">
                <div class="testimonial-card">
                    <img src="assets/images/doctor3.jpeg"
                        class="testimonial-img"
                        alt="Dr. kavya rani">

                    <p class="testimonial-text">
                        "I highly recommend Lotus Women's Hospital for its modern infrastructure, compassionate healthcare professionals, and excellent maternity services."
                    </p>

                    <hr>

                    <h5 class="mb-1">Mrs. kavya rani</h5>
                    <small class="text-muted">laparoscopic</small>

                </div>
            </div>

        </div>

    </div>

</section>

<!-- ================= TESTIMONIAL SECTION END ================= -->

<!-- ================= BENEFITS OF OBSTETRICS ================= -->

<section class="benefits-section py-5">

    <div class="container">

        <div class="row align-items-center g-5">

            <!-- Image Side -->
            <div class="col-lg-6">
                <div class="benefits-img-wrapper">
                    <img src="assets/images/Gyn-4.png"
                         class="img-fluid rounded-4 shadow"
                         alt="Obstetrics Care">
                </div>
            </div>

            <!-- Content Side -->
            <div class="col-lg-6">
                <h2 class="benefits-title fw-bold mb-4">
                    Benefits of Obstetrics
                </h2>

                <ul class="benefits-list">

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Pregnancy care (routine check-ups, ultrasounds, and prenatal screenings)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Safe childbirth (normal delivery, cesarean section, and labour management)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>High-risk pregnancy management (gestational diabetes, preeclampsia, twin pregnancies)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Fetal monitoring (tracking baby's growth, heartbeat, and development)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Postpartum care (physical recovery, breastfeeding support, emotional well-being)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Emergency care (handling complications like premature labour or bleeding)</span>
                    </li>

                    <li>
                        <span class="benefit-icon">&#10003;</span>
                        <span>Parental education and counselling (nutrition, birth preparation, newborn care)</span>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</section>

<!-- ================= BENEFITS END ================= -->

 <!-- ================= FAQ SECTION ================= -->

<section class="faq-section py-5">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">
                Frequently Asked Questions
            </h2>

            <p class="text-muted">
                Find answers to common questions about our services.
            </p>
        </div>

        <div class="accordion" id="faqAccordion">

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq1">
                        Do you provide maternity care?
                    </button>
                </h2>

                <div id="faq1" class="accordion-collapse collapse show"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes. We provide complete pregnancy care, delivery, and postnatal services.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq2">
                        Is fertility treatment available?
                    </button>
                </h2>

                <div id="faq2" class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes. We offer fertility consultation, diagnosis, and treatment.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq3">
                        Do you perform laparoscopic surgeries?
                    </button>
                </h2>

                <div id="faq3" class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes. Our specialists perform advanced laparoscopic and hysteroscopic procedures.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#faq4">
                        Can I book an appointment online?
                    </button>
                </h2>

                <div id="faq4" class="accordion-collapse collapse"
                    data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes. You can easily book your appointment through our website.
                    </div>
                </div>
            </div>

        </div>

    </div>

</section>
<!-- ================= CONTACT SECTION ================= -->

<section id="contact" class="contact-section py-5">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold text-primary">
                Contact Us
            </h2>

            <p class="text-muted">
                We're here to help you 24x7.
            </p>
        </div>

        <div class="row">

            <div class="col-md-6">

                <h4>Lotus Women's Hospital</h4>

                <p>
                    25, Main Road,<br>
                    Panruti,<br>
                    Cuddalore District,<br>
                    Tamil Nadu - 607106
                </p>

                <p>
                    +91 9876543210
                </p>

                <p>
                    info@lotushospital.com
                </p>

                <p>
                    Open 24 Hours
                </p>

            </div>

            <div class="col-md-6">

                <iframe
                    src="https://www.google.com/maps?q=Panruti,Tamil+Nadu&output=embed"
                    width="100%"
                    height="300"
                    style="border:0;"
                    loading="lazy">
                </iframe>

            </div>

        </div>

    </div>

</section>

<!-- Bootstrap JS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->

<script src="assets/js/script.js"></script>

</body>
</html>
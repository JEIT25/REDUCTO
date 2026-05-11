<?php
require_once __DIR__ . '/../includes/path_helper.php';
$basePath = getBasePath(__FILE__);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LittleLands - Playground Booking System</title>
    <meta name="description" content="LittleLands - Reserve your spot at the finest playgrounds. Instant confirmation, exclusive playground adventures.">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=homepage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="homepage">
    <!-- ===== Navbar ===== -->
    <nav class="home-navbar" id="homeNav">
        <a href="#" class="brand">
            <div class="navbar-logo-icon" aria-hidden="true" style="font-size: 1.25rem;"><i class="fa-solid fa-shapes"></i></div>
            <span class="brand-text">LittleLands<span class="sub">Playground Booking System</span></span>
        </a>
        <div class="nav-links">
            <a href="../auth/login.php" class="nav-link nav-link-ghost">Login</a>
            <a href="../forms/signup.php" class="nav-link nav-link-gold">Register</a>
        </div>
    </nav>

    <!-- ===== Hero ===== -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span> Now accepting bookings
            </div>
            <h1>The Ultimate Adventure <span class="gold">for Your Kids</span></h1>
            <p class="hero-subtitle">
                Discover safe, fun, and exciting playgrounds across the city.
                Book your perfect spot, pick a play package, and let the fun begin!
            </p>
            <div class="hero-actions">
                <a href="../forms/signup.php" class="hero-btn hero-btn-primary">
                    <i class="fa-solid fa-shapes"></i> Start Playing
                </a>
                <a href="../auth/login.php" class="hero-btn hero-btn-secondary">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </a>
            </div>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number">200+</div>
                    <div class="stat-label">Playgrounds</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Happy Kids</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">4.9★</div>
                    <div class="stat-label">Parent Rating</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== Features ===== -->
    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Instant Booking</h3>
                <p>Secure your spot in seconds. Real-time availability for all our premium playgrounds.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <h3>Safety First</h3>
                <p>All our partner playgrounds follow strict safety protocols and hygiene standards.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-gift"></i></div>
                <h3>Fun Packages</h3>
                <p>Choose from variety of play packages including birthday specials and group discounts.</p>
            </div>
        </div>
    </section>

    <!-- ===== Footer ===== -->
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</div>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        const nav = document.getElementById('homeNav');
        nav.classList.toggle('scrolled', window.scrollY > 50);
    });
</script>
</body>
</html>


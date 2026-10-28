<?php
// index.php - Staycation Hotel Management & Booking
require_once __DIR__ . '/config/db.php';

$pdo = getDBConnection();
$reviewError = null;
$reviewSuccess = false;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['review_form_token'])) {
    $_SESSION['review_form_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    $reviewToken = (string)($_POST['review_form_token'] ?? '');
    $guestName = trim((string)($_POST['guest_name'] ?? ''));
    $guestLocation = trim((string)($_POST['guest_location'] ?? ''));
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
    $comment = trim((string)($_POST['comment'] ?? ''));
    $stayDate = trim((string)($_POST['stay_date'] ?? ''));
    $honeypot = trim((string)($_POST['website'] ?? ''));
    $nameLength = function_exists('mb_strlen') ? mb_strlen($guestName) : strlen($guestName);
    $locationLength = function_exists('mb_strlen') ? mb_strlen($guestLocation) : strlen($guestLocation);
    $commentLength = function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment);
    $stayDateLength = function_exists('mb_strlen') ? mb_strlen($stayDate) : strlen($stayDate);

    if ($honeypot !== '' || $reviewToken === '' || !hash_equals((string)$_SESSION['review_form_token'], $reviewToken)) {
        $reviewError = 'Please reload the page and submit your review again.';
    } elseif ($guestName === '' || $nameLength > 100 || $guestLocation === '' || $locationLength > 100 || $rating === false || $rating < 1 || $rating > 5 || $comment === '' || $commentLength > 2000 || $stayDate === '' || $stayDateLength > 50) {
        $reviewError = 'Please complete every field. Your review must be 2,000 characters or fewer.';
    } else {
        try {
            $reviewInsert = $pdo->prepare('INSERT INTO reviews (guest_name, guest_location, rating, comment, stay_date, is_approved) VALUES (:guest_name, :guest_location, :rating, :comment, :stay_date, 1)');
            $reviewInsert->execute([
                ':guest_name' => $guestName,
                ':guest_location' => $guestLocation,
                ':rating' => $rating,
                ':comment' => $comment,
                ':stay_date' => $stayDate,
            ]);
            $reviewSuccess = true;
            $_SESSION['review_form_token'] = bin2hex(random_bytes(32));
        } catch (PDOException $e) {
            $reviewError = 'We could not save your review right now. Please try again.';
        }
    }
}

// Fetch available rooms from SQL database
try {
    $stmt = $pdo->query("SELECT * FROM rooms WHERE status != 'maintenance' ORDER BY price_per_night DESC");
    $rooms = $stmt->fetchAll();
} catch (Exception $e) {
    $rooms = [];
}

// Fetch approved guest reviews
try {
    $rev_stmt = $pdo->query("SELECT * FROM reviews WHERE is_approved = 1 ORDER BY id DESC LIMIT 3");
    $reviews = $rev_stmt->fetchAll();
} catch (Exception $e) {
    $reviews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staycation — Boutique Hotel & Luxury Rooms</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="site-header">
        <div class="container nav-wrapper">
            <a href="index.php" class="brand-logo">
                <span class="brand-name">STAYCATION</span>
                <span class="brand-sub">Discover your perfect stay</span>
            </a>
            <ul class="nav-links">
                <li><a href="#rooms">Rooms</a></li>
                <li><a href="#philosophy">Architecture</a></li>
                <li><a href="#reviews">Reviews</a></li>
                <li><a href="booking-lookup.php"><i class="fa-solid fa-receipt" style="margin-right:4px;"></i> My Booking</a></li>
                <li><a href="#contact">Contact Us</a></li>
            </ul>
            <div class="nav-actions">
                <a href="#rooms" class="btn btn-primary btn-sm">Book Room</a>
            </div>
        </div>
    </header>

    <!-- Hero Section with Three.js 3D WebGL Background -->
    <section class="hero-section">
        <div id="three-hero-container"></div>
        <div class="container hero-content">
            <div class="badge-pill">Discover your perfect stay</div>
            <h1 class="hero-title">Architectural Serenity.<br><span>Effortless Comfort.</span></h1>
            <p class="hero-desc">Discover Staycation: an intimate luxury sanctuary engineered with monolithic natural stone, warm linen, and boundless ocean views.</p>

            <!-- Quick Booking Bar -->
            <div class="booking-bar-wrapper">
                <form id="quick-booking-form" class="booking-bar">
                    <div class="bar-field">
                        <label class="bar-label" for="bar_check_in"><i class="fa-regular fa-calendar-check"></i> Check In</label>
                        <input type="date" id="bar_check_in" class="bar-input" required>
                    </div>
                    <div class="bar-field">
                        <label class="bar-label" for="bar_check_out"><i class="fa-regular fa-calendar-xmark"></i> Check Out</label>
                        <input type="date" id="bar_check_out" class="bar-input" required>
                    </div>
                    <div class="bar-field">
                        <label class="bar-label" for="bar_guests"><i class="fa-solid fa-user-group"></i> Guests</label>
                        <select id="bar_guests" class="bar-input">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                        </select>
                    </div>
                    <div class="bar-field">
                        <label class="bar-label" for="bar_room_type"><i class="fa-solid fa-bed"></i> Room Type</label>
                        <select id="bar_room_type" class="bar-input">
                            <option value="all">All Rooms</option>
                            <option value="Presidential Suite">Presidential Suite</option>
                            <option value="Ocean Front Villa">Ocean Front Villa</option>
                            <option value="Penthouse Vista">Penthouse Vista</option>
                            <option value="Deluxe Garden Suite">Deluxe Garden Suite</option>
                            <option value="Executive Atelier">Executive Atelier</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding: 16px 28px; width: 100%;">
                            Check Dates <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Rooms Section -->
    <section id="rooms" class="section">
        <div class="container">
            <div class="section-header">
                <div class="badge-pill">Accommodations</div>
                <h2 class="section-title">Rooms</h2>
                <p class="section-subtitle">Every room is an architectural sanctuary crafted with organic materials, floor-to-ceiling panoramic glass, and secluded outdoor living.</p>
            </div>

            <div class="suites-grid">
                <?php foreach ($rooms as $room): 
                    $amenities = array_map('trim', explode(',', $room['amenities']));
                ?>
                    <div class="suite-card">
                        <div class="suite-img-wrap">
                            <img src="<?= htmlspecialchars($room['image_url']) ?>" alt="<?= htmlspecialchars($room['title']) ?>" loading="lazy">
                            <span class="suite-type-tag"><?= htmlspecialchars($room['type']) ?></span>
                            <div class="suite-price-badge">
                                <div class="amt">₹<?= number_format($room['price_per_night'], 0) ?></div>
                                <div class="per">/ Night</div>
                            </div>
                        </div>
                        <div class="suite-body">
                            <h3><?= htmlspecialchars($room['title']) ?></h3>
                            <div class="suite-meta-bar">
                                <span><i class="fa-solid fa-users"></i> Up to <?= $room['capacity'] ?> Guests</span>
                                <span><i class="fa-solid fa-vector-square"></i> <?= $room['size_sqft'] ?> sq ft</span>
                                <span><i class="fa-solid fa-bed"></i> <?= htmlspecialchars($room['bed_type']) ?></span>
                            </div>
                            <p class="suite-desc"><?= htmlspecialchars($room['description']) ?></p>
                            <div class="suite-amenities-tags">
                                <?php foreach (array_slice($amenities, 0, 3) as $am): ?>
                                    <span class="amenity-pill"><?= htmlspecialchars($am) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($amenities) > 3): ?>
                                    <span class="amenity-pill">+<?= count($amenities) - 3 ?> more</span>
                                <?php endif; ?>
                            </div>
                            <div class="suite-actions">
                                <button class="btn btn-primary btn-sm btn-open-booking" 
                                    data-room='<?= htmlspecialchars(json_encode($room), ENT_QUOTES, 'UTF-8') ?>'>
                                    Reserve Room
                                </button>
                                <a href="#contact" class="btn btn-outline btn-sm">Contact Us</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Architectural Narrative / Philosophy -->
    <section id="philosophy" class="section" style="background: var(--bg-subtle);">
        <div class="container">
            <div class="narrative-grid">
                <div class="narrative-image-stack">
                    <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80" alt="Staycation Architecture" class="narrative-main-img">
                    <div class="narrative-float-card">
                        <div class="num">100%</div>
                        <div class="lbl">Zero-emission geothermal climate system & passive natural cooling</div>
                    </div>
                </div>
                <div>
                    <div class="badge-pill">Architectural Ethos</div>
                    <h2 class="section-title" style="text-align: left; margin-bottom: 24px;">Where Brutalism Dissolves into Warm Stillness</h2>
                    <p style="margin-bottom: 20px; font-size: 1.05rem; line-height: 1.8;">Conceived by avant-garde minimalist architects, Staycation was chiseled directly into volcanic cliff faces. Raw board-formed concrete and monolithic black volcanic stone stand in gentle conversation with warm Japanese oak, fine natural linen, and soft ambient illumination.</p>
                    <p style="margin-bottom: 30px; font-size: 1.05rem; line-height: 1.8;">Here, luxury is not decorative noise. It is the absence of friction: sound-dampened acoustic envelopes, curated tactile materials, and an uninterrupted visual plane stretching toward the endless horizon.</p>
                    
                    <div style="display: flex; gap: 32px; border-top: 1px solid var(--border-color); padding-top: 24px;">
                        <div>
                            <div style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--accent-gold); font-weight: 700;">2,150</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em;">Max Room Sq Ft</div>
                        </div>
                        <div>
                            <div style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--accent-gold); font-weight: 700;">24/7</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em;">Front Desk Service</div>
                        </div>
                        <div>
                            <div style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--accent-gold); font-weight: 700;">5-Star</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.1em;">Hospitality Award</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Accolades & Guest Reviews -->
    <section id="reviews" class="section">
        <div class="container">
            <div class="section-header">
                <div class="badge-pill">Guest Satisfaction</div>
                <h2 class="section-title">Guest Reviews</h2>
                <p class="section-subtitle">Experiences shared by Staycation guests.</p>
            </div>

            <div class="reviews-grid">
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card">
                        <div class="stars">
                            <?php for ($i = 0; $i < $rev['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                        </div>
                        <div class="review-text">"<?= htmlspecialchars($rev['comment']) ?>"</div>
                        <div class="reviewer-meta">
                            <span class="reviewer-name"><?= htmlspecialchars($rev['guest_name']) ?></span>
                            <span class="reviewer-loc"><?= htmlspecialchars($rev['guest_location']) ?> • Stayed <?= htmlspecialchars($rev['stay_date']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$reviews): ?>
                    <p class="section-subtitle">No reviews have been shared yet. Be the first to leave one.</p>
                <?php endif; ?>
            </div>

            <div style="max-width: 760px; margin: 48px auto 0; padding: 32px; border: 1px solid var(--border-color); border-radius: 16px; background: var(--bg-card);">
                <h3 class="serif" style="font-size: 1.8rem; color: #fff; margin-bottom: 8px;">Share your stay</h3>
                <p style="margin-bottom: 22px; color: var(--text-secondary);">Your review will appear in the guest reviews after you submit it.</p>
                <?php if ($reviewSuccess): ?>
                    <div role="status" style="margin-bottom: 18px; color: #86efac;">Thank you. Your review has been published.</div>
                <?php endif; ?>
                <?php if ($reviewError): ?>
                    <div role="alert" style="margin-bottom: 18px; color: #fca5a5;"><?= htmlspecialchars($reviewError) ?></div>
                <?php endif; ?>
                <form method="POST" action="index.php#reviews" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="review_form_token" value="<?= htmlspecialchars((string)$_SESSION['review_form_token']) ?>">
                    <div style="position: absolute; left: -10000px;" aria-hidden="true">
                        <label for="review_website">Leave this field empty</label>
                        <input type="text" id="review_website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div>
                        <label class="form-label" for="review_guest_name">Your name *</label>
                        <input class="form-input" type="text" id="review_guest_name" name="guest_name" maxlength="100" required>
                    </div>
                    <div>
                        <label class="form-label" for="review_guest_location">City / country *</label>
                        <input class="form-input" type="text" id="review_guest_location" name="guest_location" maxlength="100" placeholder="e.g. Manipal, India" required>
                    </div>
                    <div>
                        <label class="form-label" for="review_rating">Rating *</label>
                        <select class="form-input" id="review_rating" name="rating" required>
                            <option value="">Select a rating</option>
                            <option value="5">5 stars — Excellent</option>
                            <option value="4">4 stars — Very good</option>
                            <option value="3">3 stars — Good</option>
                            <option value="2">2 stars — Fair</option>
                            <option value="1">1 star — Poor</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="review_stay_date">When did you stay? *</label>
                        <input class="form-input" type="text" id="review_stay_date" name="stay_date" maxlength="50" placeholder="e.g. October 2026" required>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="form-label" for="review_comment">Your review *</label>
                        <textarea class="form-input" id="review_comment" name="comment" rows="5" maxlength="2000" required></textarea>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-star"></i> Publish Review</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Reservation Status Lookup Banner -->
    <section class="section" style="padding: 70px 0; background: linear-gradient(135deg, #15151c 0%, #0d0d10 100%); border-top: 1px solid var(--border-gold); border-bottom: 1px solid var(--border-gold);">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
            <div>
                <span class="badge-pill" style="margin-bottom: 10px;">Guest Self-Service</span>
                <h3 style="font-size: 2rem; color: #fff; margin-bottom: 6px;">Already have a booking?</h3>
                <p style="margin: 0; color: var(--text-secondary);">Lookup your booking details, view room access codes, print vouchers, or make adjustments.</p>
            </div>
            <a href="booking-lookup.php" class="btn btn-primary" style="padding: 18px 32px;">
                <i class="fa-solid fa-magnifying-glass"></i> Manage My Reservation
            </a>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact" class="section" style="background: var(--bg-subtle);">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info-card">
                    <div class="badge-pill">Get In Touch</div>
                    <h2 class="serif" style="font-size: 2.5rem; color: #fff; margin-bottom: 20px;">Contact Us</h2>
                    <p style="margin-bottom: 34px;">Have questions about room availability, reservations, or special requests? Reach out to our front desk team anytime. We are here to assist you 24 hours a day.</p>
                    
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #fff; font-size: 0.95rem;">Location</div>
                            <div style="font-size: 0.88rem; color: var(--text-secondary);">Manipal, Karnataka</div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                        <div>
                            <div style="font-weight: 600; color: #fff; font-size: 0.95rem;">Phone Number</div>
                            <div style="font-size: 0.88rem; color: var(--text-secondary);">+91 9477465266</div>
                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="serif" style="font-size: 1.8rem; color: #fff; margin-bottom: 24px;">Send Us a Message</h3>
                    <div id="contact-alert" style="display:none; margin-bottom: 20px;"></div>
                    <form id="contact-form">
                        <div class="booking-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="contact_name">Full Name *</label>
                                <input type="text" id="contact_name" class="form-input" required placeholder="e.g. Eleanor Vance">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contact_email">Email Address *</label>
                                <input type="email" id="contact_email" class="form-input" required placeholder="eleanor@domain.com">
                            </div>
                        </div>
                        <div class="booking-form-grid">
                            <div class="form-group">
                                <label class="form-label" for="contact_phone">Phone Number</label>
                                <input type="tel" id="contact_phone" class="form-input" placeholder="+91 98765 43210">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contact_subject">Subject</label>
                                <input type="text" id="contact_subject" class="form-input" placeholder="e.g. Room Inquiry">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact_message">Your Message *</label>
                            <textarea id="contact_message" class="form-textarea" required placeholder="How can we help you? Write your message here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px;">
                            Send Message <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid" style="grid-template-columns: 2fr 1fr 1fr;">
                <div class="footer-col">
                    <div class="brand-logo" style="margin-bottom: 16px;">
                        <span class="brand-name">STAYCATION</span>
                        <span class="brand-sub">Discover your perfect stay</span>
                    </div>
                    <p style="font-size: 0.88rem; max-width: 340px; margin-bottom: 20px;">A celebration of minimalist architectural elegance and peaceful hospitality. Book your room directly with instant confirmation.</p>
                    <div style="display: flex; gap: 14px; font-size: 1.1rem; color: var(--accent-gold);">
                        <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#"><i class="fa-brands fa-pinterest"></i></a>
                        <a href="#"><i class="fa-brands fa-spotify"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="#rooms">Rooms</a></li>
                        <li><a href="#philosophy">Architecture</a></li>
                        <li><a href="#reviews">Reviews</a></li>
                        <li><a href="booking-lookup.php">Manage My Booking</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <p style="font-size: 0.85rem; line-height: 1.8; color: var(--text-secondary);">
                        Staycation, Manipal<br>
                        Phone: +91 9477465266 <br>
                    </p>
                    <div class="badge-pill" style="margin-top: 14px;">Instant Confirmation</div>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; 2026 Staycation Hotel. All rights reserved.</div>
                <div>Hosted on Localhost</div>
            </div>
        </div>
    </footer>

    <!-- Interactive Luxury Booking Modal -->
    <div id="booking-modal" class="modal-overlay">
        <div class="modal-card">
            <button id="modal-close-btn" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
            <div class="modal-header-banner">
                <img id="modal_room_img" src="" alt="Room Preview">
                <div class="overlay-gradient"></div>
            </div>
            <div class="modal-content-body">
                <div class="badge-pill" id="modal_room_type">Room Selection</div>
                <h2 id="modal_suite_title" class="modal-suite-title">Room Title</h2>
                <div class="modal-pricing-row">
                    <span id="modal_price_display" class="price">₹0</span>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;">/ per night (excl. 12% tax)</span>
                </div>

                <form id="modal-booking-form">
                    <input type="hidden" id="modal_room_id" value="">

                    <div class="booking-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="modal_check_in">Arrival Date *</label>
                            <input type="date" id="modal_check_in" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="modal_check_out">Departure Date *</label>
                            <input type="date" id="modal_check_out" class="form-input" required>
                        </div>
                    </div>

                    <div class="booking-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="modal_guest_name">Primary Guest Name *</label>
                            <input type="text" id="modal_guest_name" class="form-input" placeholder="e.g. Julian Sterling" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="modal_guest_email">Email Confirmation *</label>
                            <input type="email" id="modal_guest_email" class="form-input" placeholder="julian@domain.com" required>
                        </div>
                    </div>

                    <div class="booking-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="modal_guest_phone">Phone / Mobile *</label>
                            <input type="tel" id="modal_guest_phone" class="form-input" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="modal_guests">Guest Count *</label>
                            <select id="modal_guests" class="form-input">
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_payment_method">Payment Method *</label>
                        <select id="modal_payment_method" class="form-input">
                            <option value="Credit Card (Online Instant)">Credit Card (Online Instant)</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Pay at Hotel">Pay upon Check-in</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modal_special_requests">Special Requests</label>
                        <textarea id="modal_special_requests" class="form-textarea" placeholder="Estimated arrival time, quiet room, extra pillows..."></textarea>
                    </div>

                    <!-- Financial Summary Box -->
                    <div class="order-summary-box">
                        <div class="summary-line">
                            <span id="sum_nights_label">Rate × Nights</span>
                        <span id="sum_subtotal">₹0.00</span>
                        </div>
                        <div class="summary-line">
                            <span>Hospitality Tax (12%)</span>
                        <span id="sum_tax">₹0.00</span>
                        </div>
                        <div class="summary-line total">
                            <span>Estimated Total Amount</span>
                        <span id="sum_total">₹0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 18px;">
                        Confirm & Reserve Room <i class="fa-solid fa-lock"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Booking Confirmation Voucher Modal -->
    <div id="confirmation-modal" class="modal-overlay">
        <div class="modal-card" style="max-width: 650px;">
            <button id="conf-modal-close-btn" class="modal-close"><i class="fa-solid fa-xmark"></i></button>
            <div style="padding: 44px 40px; text-align: center;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #22c55e; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div class="badge-pill">Reservation Confirmed</div>
                <h2 style="font-size: 2.2rem; color: #fff; margin-bottom: 8px;">Welcome to Staycation</h2>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 28px;">Your reservation is logged and confirmed.</p>

                <div class="order-summary-box" style="text-align: left; background: rgba(255, 255, 255, 0.03);">
                <div class="summary-line">
                    <span>Booking Reference</span>
                        <span id="voucher_ref" style="font-weight: 700; color: var(--accent-gold); font-family: monospace; font-size: 1.1rem;">STAY-000000</span>
                    </div>
                    <div class="summary-line">
                        <span>Guest Name</span>
                        <span id="voucher_guest" style="color: #fff; font-weight: 600;">-</span>
                    </div>
                    <div class="summary-line">
                        <span>Email Address</span>
                        <span id="voucher_email" style="color: #fff;">-</span>
                    </div>
                    <div class="summary-line">
                        <span>Room Assigned</span>
                        <span id="voucher_suite" style="color: #fff; font-weight: 600;">-</span>
                    </div>
                    <div class="summary-line">
                        <span>Stay Itinerary</span>
                        <span id="voucher_dates" style="color: #fff;">-</span>
                    </div>
                    <div class="summary-line">
                        <span>Occupancy</span>
                        <span id="voucher_guests" style="color: #fff;">-</span>
                    </div>
                    <div class="summary-line">
                        <span>Status</span>
                        <span id="voucher_payment" style="color: #86efac; font-weight: 600;">-</span>
                    </div>
                    <div class="summary-line total">
                        <span>Total Paid</span>
                        <span id="voucher_total" style="color: var(--accent-gold);">₹0.00</span>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 24px;">
                    <button class="btn btn-outline" style="flex: 1;" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Print Voucher
                    </button>
                    <a href="booking-lookup.php" class="btn btn-primary" style="flex: 1;">
                        Manage Booking
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts: Three.js CDN, Hero Canvas Script, Main Controller -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="assets/js/three_hero.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

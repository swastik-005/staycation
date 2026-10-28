<?php
// booking-lookup.php - Staycation Guest Reservation Portal & Electronic Folio
require_once __DIR__ . '/config/db.php';

$pdo = getDBConnection();
$phone = '';
$email = '';
$bookings = [];
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string)($_POST['phone'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
    if (strlen($phoneDigits) === 12 && substr($phoneDigits, 0, 2) === '91') {
        $phoneDigits = substr($phoneDigits, 2);
    }
    if (strlen($phoneDigits) !== 10 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter the valid phone number and email address used for your booking.';
    } else {
    try {
        $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.type AS room_type, r.room_number, r.image_url, r.bed_type, r.view_type, r.size_sqft, r.amenities FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE LOWER(b.guest_email) = LOWER(:email) ORDER BY b.created_at DESC");
        $stmt->execute([':email' => $email]);
        foreach ($stmt->fetchAll() as $booking) {
            $storedDigits = preg_replace('/\D+/', '', (string)$booking['guest_phone']) ?? '';
            if (strlen($storedDigits) === 12 && substr($storedDigits, 0, 2) === '91') {
                $storedDigits = substr($storedDigits, 2);
            }
            if ($storedDigits === $phoneDigits) {
                $bookings[] = $booking;
            }
        }
        if (!$bookings) {
            $error = 'No booking matches those contact details. Check them and try again.';
        }
    } catch (Exception $e) {
        $error = 'Could not load booking details. Please try again later.';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservation | Staycation Hotel Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .lookup-wrap {
            padding: 120px 0 80px;
            min-height: 80vh;
        }
        .voucher-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.7);
            margin-top: 36px;
        }
        .voucher-header {
            padding: 30px 40px;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .voucher-body {
            padding: 40px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }
        .status-confirmed { background: rgba(34, 197, 94, 0.15); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.3); }
        .status-checked-in { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-checked-out { background: rgba(156, 163, 175, 0.15); color: #d1d5db; border: 1px solid rgba(156, 163, 175, 0.3); }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

        @media print {
            body { background: #fff !important; color: #000 !important; }
            .site-header, .site-footer, .lookup-search-box, .no-print { display: none !important; }
            .voucher-card { border: 1px solid #000 !important; box-shadow: none !important; background: #fff !important; color: #000 !important; }
            .voucher-header, .voucher-body { color: #000 !important; }
            h2, h3, .serif { color: #000 !important; }
            p, span, div { color: #222 !important; }
        }
        @media (max-width: 800px) {
            .voucher-body { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="site-header scrolled">
        <div class="container nav-wrapper">
            <a href="index.php" class="brand-logo">
                <span class="brand-name">STAYCATION</span>
                <span class="brand-sub">Hotel Management</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php#rooms">Rooms</a></li>
                <li><a href="index.php#contact">Contact Us</a></li>
            </ul>
            <div class="nav-actions">
                <a href="index.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-left"></i> Home</a>
            </div>
        </div>
    </header>

    <div class="lookup-wrap">
        <div class="container" style="max-width: 960px;">
            <div class="no-print">
                <div class="badge-pill">Guest Self-Service Portal</div>
                <h1 class="serif" style="font-size: 2.8rem; color: #fff; margin-bottom: 12px;">Staycation Guest Folio Lookup</h1>
                <?php if (!$bookings): ?>
                    <p style="margin-bottom: 30px;">Enter the phone number and email address used for your reservation to see your booking details.</p>
                    <div class="lookup-search-box" style="background: var(--bg-card); padding: 30px; border-radius: 16px; border: 1px solid var(--border-color);">
                        <form method="POST" action="booking-lookup.php" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end;">
                            <div>
                                <label class="form-label" for="phone_input">Phone Number *</label>
                                <input type="tel" id="phone_input" name="phone" class="form-input" placeholder="+91 98765 43210" value="<?= htmlspecialchars($phone) ?>" required>
                            </div>
                            <div>
                                <label class="form-label" for="email_input">Email Address *</label>
                                <input type="email" id="email_input" name="email" class="form-input" placeholder="Email used for booking" value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary" style="padding: 14px 28px; width: 100%;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Find Booking
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <p style="margin-bottom: 30px; color: #86efac;">Your bookings are listed below.</p>
                    <a href="booking-lookup.php" class="btn btn-outline">Look up another booking</a>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 18px; border-radius: 12px; margin-top: 24px;">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

            </div>

            <?php foreach ($bookings as $booking): ?>
                <div class="voucher-card">
                    <div class="voucher-header">
                        <div>
                            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--accent-gold); font-weight: 600;">Staycation Electronic Guest Folio</div>
                            <h2 style="font-size: 1.8rem; color: #fff; margin-top: 4px;">Reservation Folio #<?= htmlspecialchars($booking['booking_reference']) ?></h2>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">Registered in SQL on <?= date('F j, Y', strtotime($booking['created_at'])) ?></div>
                        </div>
                        <div style="display: flex; gap: 12px; align-items: center;" class="no-print">
                            <span class="status-badge status-<?= htmlspecialchars($booking['booking_status']) ?>">
                                <i class="fa-solid fa-circle-dot"></i> <?= ucfirst(htmlspecialchars($booking['booking_status'])) ?>
                            </span>
                            <button class="btn btn-outline btn-sm" onclick="window.print()">
                                <i class="fa-solid fa-print"></i> Print Folio
                            </button>
                        </div>
                    </div>

                    <div class="voucher-body">
                        <div>
                            <h3 class="serif" style="font-size: 1.6rem; color: #fff; margin-bottom: 8px;"><?= htmlspecialchars($booking['room_title']) ?></h3>
                            <div style="font-size: 0.88rem; color: var(--accent-gold); margin-bottom: 20px;">
                                Room <?= htmlspecialchars($booking['room_number']) ?> • <?= htmlspecialchars($booking['room_type']) ?>
                            </div>

                            <img src="<?= htmlspecialchars($booking['image_url']) ?>" alt="Suite" style="width: 100%; height: 260px; object-fit: cover; border-radius: 12px; margin-bottom: 24px;">

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                                <div style="background: rgba(255,255,255,0.02); padding: 16px; border-radius: 10px; border: 1px solid var(--border-color);">
                                    <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--accent-gold); letter-spacing: 0.1em; font-weight: 600;">Check-In Date</div>
                                    <div style="font-size: 1.1rem; color: #fff; font-weight: 600; margin-top: 4px;"><?= date('D, M j, Y', strtotime($booking['check_in'])) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-secondary);">From 3:00 PM</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.02); padding: 16px; border-radius: 10px; border: 1px solid var(--border-color);">
                                    <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--accent-gold); letter-spacing: 0.1em; font-weight: 600;">Check-Out Date</div>
                                    <div style="font-size: 1.1rem; color: #fff; font-weight: 600; margin-top: 4px;"><?= date('D, M j, Y', strtotime($booking['check_out'])) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-secondary);">Until 12:00 PM</div>
                                </div>
                            </div>

                            <?php if (!empty($booking['special_requests'])): ?>
                                <div style="background: rgba(255,255,255,0.02); padding: 18px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 24px;">
                                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--accent-gold); font-weight: 600;">Special Requests Logged</div>
                                    <p style="color: #ddd; font-size: 0.9rem; margin-top: 6px;"><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div style="background: rgba(255,255,255,0.02); padding: 26px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 24px;">
                                <h4 style="font-size: 1.1rem; color: #fff; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Guest Profile</h4>
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Full Name</div>
                                    <div style="font-size: 1rem; color: #fff; font-weight: 600;"><?= htmlspecialchars($booking['guest_name']) ?></div>
                                </div>
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Email</div>
                                    <div style="font-size: 0.95rem; color: #fff;"><?= htmlspecialchars($booking['guest_email']) ?></div>
                                </div>
                                <div style="margin-bottom: 12px;">
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Phone</div>
                                    <div style="font-size: 0.95rem; color: #fff;"><?= htmlspecialchars($booking['guest_phone']) ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Registered Guests</div>
                                    <div style="font-size: 0.95rem; color: #fff;"><?= $booking['num_guests'] ?> Guest(s)</div>
                                </div>
                            </div>

                            <div style="background: rgba(255,255,255,0.02); padding: 26px; border-radius: 12px; border: 1px solid var(--border-gold);">
                                <h4 style="font-size: 1.1rem; color: #fff; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Billing & Folio Summary</h4>
                                <div class="summary-line">
                                    <span>Stay Duration</span>
                                    <span style="color: #fff;"><?= $booking['total_nights'] ?> Night(s)</span>
                                </div>
                                <div class="summary-line">
                                    <span>Subtotal</span>
                                    <span style="color: #fff;">₹<?= number_format($booking['subtotal'], 2) ?></span>
                                </div>
                                <div class="summary-line">
                                    <span>Tax & Service (12%)</span>
                                    <span style="color: #fff;">₹<?= number_format($booking['tax_amount'], 2) ?></span>
                                </div>
                                <div class="summary-line total">
                                    <span>Total Settled</span>
                                    <span style="color: var(--accent-gold);">₹<?= number_format($booking['total_price'], 2) ?></span>
                                </div>
                                <div style="margin-top: 14px; font-size: 0.8rem; color: var(--text-secondary);">
                                    Payment Method: <strong style="color: #fff;"><?= htmlspecialchars($booking['payment_method']) ?></strong>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container" style="text-align: center; color: var(--text-muted); font-size: 0.82rem;">
            &copy; 2026 Staycation Hotel Management Software • Electronic Guest Portal
        </div>
    </footer>
</body>
</html>

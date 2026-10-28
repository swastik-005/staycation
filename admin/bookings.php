<?php
// admin/bookings.php - Staycation Reservations & Folio Management
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$msg = null;
$error = null;

// Handle Status & Payment Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_booking') {
        $id = intval($_POST['booking_id'] ?? 0);
        $booking_status = $_POST['booking_status'] ?? 'confirmed';
        $payment_status = $_POST['payment_status'] ?? 'paid';

        try {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$booking_status, $payment_status, $id]);
            $msg = "Reservation folio #{$id} updated in SQL database successfully.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_booking') {
        $id = intval($_POST['booking_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Reservation record deleted from database.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'create_walkin') {
        $room_id = intval($_POST['room_id'] ?? 0);
        $guest_name = trim($_POST['guest_name'] ?? '');
        $guest_email = trim($_POST['guest_email'] ?? '');
        $guest_phone = trim($_POST['guest_phone'] ?? '');
        $check_in = trim($_POST['check_in'] ?? '');
        $check_out = trim($_POST['check_out'] ?? '');
        $num_guests = intval($_POST['num_guests'] ?? 1);
        $payment_method = trim($_POST['payment_method'] ?? 'Front Desk Terminal (Cash/Card)');
        $special_requests = trim($_POST['special_requests'] ?? 'Front-desk walk-in registration');

        if ($room_id && !empty($guest_name) && !empty($check_in) && !empty($check_out)) {
            $nights = max(1, round((strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24)));
            // Fetch room
            $r_stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
            $r_stmt->execute([$room_id]);
            $r_data = $r_stmt->fetch();
            if ($r_data) {
                $subtotal = floatval($r_data['price_per_night']) * $nights;
                $tax = round($subtotal * 0.12, 2);
                $total = $subtotal + $tax;
                $ref = 'STAY-' . strtoupper(substr(uniqid(), -6));

                $ins = $pdo->prepare("
                    INSERT INTO bookings (
                        booking_reference, room_id, guest_name, guest_email, guest_phone,
                        check_in, check_out, total_nights, num_guests, subtotal, tax_amount, total_price,
                        booking_status, payment_status, payment_method, special_requests
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?,
                        'checked-in', 'paid', ?, ?
                    )
                ");
                $ins->execute([
                    $ref, $room_id, $guest_name, $guest_email, $guest_phone,
                    $check_in, $check_out, $nights, $num_guests, $subtotal, $tax, $total,
                    $payment_method, $special_requests
                ]);
                $msg = "Walk-in check-in registered in SQL! Folio Reference: {$ref}";
            }
        } else {
            $error = "Please fill in all required fields for the walk-in reservation.";
        }
    }
}

// Filters & Search
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT b.*, r.title AS room_title, r.room_number, r.type AS room_type 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== 'all') {
    $query .= " AND b.booking_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (b.booking_reference LIKE :s OR b.guest_name LIKE :s OR b.guest_email LIKE :s OR r.room_number LIKE :s)";
    $params[':s'] = "%{$search}%";
}

$query .= " ORDER BY b.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Fetch rooms for walkin dropdown
$all_rooms = $pdo->query("SELECT id, title, room_number, price_per_night FROM rooms ORDER BY room_number ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations Folio | Staycation Hotel Management Software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            list-style: none;
        }
        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            color: var(--text-gray);
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            text-decoration: none;
            transition: all 0.2s;
        }
        .filter-tab.active, .filter-tab:hover {
            background: rgba(197, 168, 128, 0.15);
            color: var(--accent-gold);
            border-color: var(--border-gold);
        }
        .search-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-subtle);
            padding: 8px 14px;
            border-radius: 8px;
            color: #fff;
            font-size: 0.88rem;
            outline: none;
        }
        .walkin-modal {
            display: <?= (isset($_GET['action']) && $_GET['action'] === 'new') ? 'flex' : 'none' ?>;
            position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);
            z-index: 999; align-items: center; justify-content: center; padding: 20px;
        }
        .walkin-card {
            background: var(--bg-card); border: 1px solid var(--border-gold); border-radius: 16px;
            width: 100%; max-width: 650px; padding: 32px; box-shadow: 0 25px 60px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <span class="sidebar-brand">STAYCATION</span>
            <span class="sidebar-sub">Hotel Management Software</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Operations Overview</a></li>
            <li><a href="bookings.php" class="active"><i class="fa-solid fa-calendar-days"></i> Reservations Folio</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-bed"></i> Room Inventory</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Guest Inquiries</a></li>
            <li><a href="export_bookings.php"><i class="fa-solid fa-file-export"></i> Export Folio (CSV)</a></li>
            <li><a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings" target="_blank" style="color:var(--accent-gold);"><i class="fa-solid fa-database"></i> XAMPP SQL Database &nearr;</a></li>
            <li><a href="../setup.php"><i class="fa-solid fa-wrench"></i> Setup & Seed Migration</a></li>
        </ul>
        <div class="sidebar-footer">
            <div style="font-size: 0.82rem; color: var(--text-gray);">
                <div style="color: #fff; font-weight: 600;"><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
                <div style="font-size: 0.72rem; text-transform: capitalize; color: var(--accent-gold);"><?= htmlspecialchars($_SESSION['admin_role']) ?></div>
            </div>
            <a href="logout.php" title="Sign Out" style="color: #ef4444; font-size: 1.1rem;"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <h1 class="topbar-title">Staycation Reservations & Folios</h1>
            <div class="topbar-actions">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings" target="_blank" class="btn-ui btn-dark" title="View bookings table in XAMPP phpMyAdmin">
                    <i class="fa-solid fa-database"></i> SQL Table View &nearr;
                </a>
                <a href="export_bookings.php" class="btn-ui btn-dark"><i class="fa-solid fa-download"></i> Export CSV</a>
                <a href="bookings.php?action=new" class="btn-ui btn-gold"><i class="fa-solid fa-plus"></i> Walk-in Registration</a>
            </div>
        </header>

        <div class="admin-body">
            <?php if ($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs & Search -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <a href="bookings.php?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">All Folios (<?= count($bookings) ?>)</a>
                    <a href="bookings.php?status=confirmed" class="filter-tab <?= $status_filter === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
                    <a href="bookings.php?status=checked-in" class="filter-tab <?= $status_filter === 'checked-in' ? 'active' : '' ?>">Checked In</a>
                    <a href="bookings.php?status=checked-out" class="filter-tab <?= $status_filter === 'checked-out' ? 'active' : '' ?>">Checked Out</a>
                    <a href="bookings.php?status=cancelled" class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
                </div>

                <form method="GET" action="bookings.php" style="display: flex; gap: 8px;">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <input type="text" name="search" class="search-input" placeholder="Search reference, guest, suite..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-ui btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="bookings.php?status=<?= urlencode($status_filter) ?>" class="btn-ui btn-dark" title="Clear Search"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Bookings Master Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Folio Ref</th>
                                <th>Guest Info</th>
                                <th>Assigned Room</th>
                                <th>Stay Dates</th>
                                <th>Financials ($)</th>
                                <th>Status Controls</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">
                                        No reservations match current criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td>
                                            <a href="../booking-lookup.php?ref=<?= urlencode($b['booking_reference']) ?>" target="_blank" style="font-family: monospace; font-weight: 700; color: var(--accent-gold); text-decoration: underline;">
                                                <?= htmlspecialchars($b['booking_reference']) ?>
                                            </a>
                                            <div style="font-size: 0.72rem; color: var(--text-gray);"><?= date('M j, Y', strtotime($b['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($b['guest_name']) ?></strong><br>
                                            <small style="color: var(--text-gray);"><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($b['guest_email']) ?></small><br>
                                            <small style="color: var(--text-gray);"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($b['guest_phone']) ?></small>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($b['room_title']) ?></strong><br>
                                            <small style="color: var(--accent-gold);">Room <?= htmlspecialchars($b['room_number']) ?> (<?= htmlspecialchars($b['room_type']) ?>)</small>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?= date('M j', strtotime($b['check_in'])) ?> &rarr; <?= date('M j, Y', strtotime($b['check_out'])) ?></strong><br>
                                            <small style="color: var(--text-gray);"><?= $b['total_nights'] ?> Nights • <?= $b['num_guests'] ?> Guest(s)</small>
                                        </td>
                                        <td>
                                            <div style="font-size: 1rem; font-weight: 700; color: #fff;">₹<?= number_format($b['total_price'], 2) ?></div>
                                            <small style="color: var(--text-gray);"><?= htmlspecialchars($b['payment_method']) ?></small>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: flex; flex-direction: column; gap: 6px;">
                                                <input type="hidden" name="action" value="update_booking">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">

                                                <select name="booking_status" onchange="this.form.submit()" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); padding: 4px 8px; border-radius: 4px; font-size: 0.78rem;">
                                                    <option value="confirmed" <?= $b['booking_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="checked-in" <?= $b['booking_status'] === 'checked-in' ? 'selected' : '' ?>>Checked In</option>
                                                    <option value="checked-out" <?= $b['booking_status'] === 'checked-out' ? 'selected' : '' ?>>Checked Out</option>
                                                    <option value="cancelled" <?= $b['booking_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>

                                                <select name="payment_status" onchange="this.form.submit()" style="background: rgba(255,255,255,0.05); color: #86efac; border: 1px solid var(--border-subtle); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem;">
                                                    <option value="paid" <?= $b['payment_status'] === 'paid' ? 'selected' : '' ?>>Payment: Paid</option>
                                                    <option value="pending" <?= $b['payment_status'] === 'pending' ? 'selected' : '' ?>>Payment: Pending</option>
                                                    <option value="refunded" <?= $b['payment_status'] === 'refunded' ? 'selected' : '' ?>>Payment: Refunded</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px;">
                                                <a href="../booking-lookup.php?ref=<?= urlencode($b['booking_reference']) ?>" target="_blank" class="btn-ui btn-dark" title="View Folio / Print"><i class="fa-solid fa-receipt"></i></a>
                                                <form method="POST" onsubmit="return confirm('Permanently remove this booking record?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_booking">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn-ui btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Walk-in Booking Modal -->
    <div class="walkin-modal" id="walkin-modal">
        <div class="walkin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.5rem; color: #fff;">Staycation Walk-in Check-in</h2>
                <a href="bookings.php" style="color: var(--text-gray); font-size: 1.2rem;"><i class="fa-solid fa-xmark"></i></a>
            </div>

            <form method="POST" action="bookings.php">
                <input type="hidden" name="action" value="create_walkin">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="stat-label">Room Assigned *</label>
                        <select name="room_id" required style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                            <?php foreach ($all_rooms as $rm): ?>
                                <option value="<?= $rm['id'] ?>">Room <?= $rm['room_number'] ?> - <?= $rm['title'] ?> (₹<?= number_format($rm['price_per_night'], 0) ?>/nt)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="stat-label">Guest Count</label>
                        <select name="num_guests" style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="stat-label">Arrival Date *</label>
                        <input type="date" name="check_in" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                    </div>
                    <div>
                        <label class="stat-label">Departure Date *</label>
                        <input type="date" name="check_out" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                    <div>
                        <label class="stat-label">Guest Full Name *</label>
                        <input type="text" name="guest_name" required placeholder="Guest Name" style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                    </div>
                    <div>
                        <label class="stat-label">Email Address</label>
                        <input type="email" name="guest_email" placeholder="guest@domain.com" style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label class="stat-label">Phone / Mobile</label>
                    <input type="tel" name="guest_phone" placeholder="+91 98765 43210" style="width: 100%; padding: 10px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); border-radius: 6px;">
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="bookings.php" class="btn-ui btn-dark">Cancel</a>
                    <button type="submit" class="btn-ui btn-gold">Complete Front-Desk Check-In</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

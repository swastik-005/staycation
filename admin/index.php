<?php
// admin/index.php - Staycation Hotel Management Software Dashboard
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();

// Handle quick booking status update from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $allowed = ['confirmed', 'checked-in', 'checked-out', 'cancelled'];
    if ($booking_id && in_array($new_status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE bookings SET booking_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $booking_id]);
        header('Location: index.php?msg=status_updated');
        exit;
    }
}

// 1. Hotel Management Metrics calculations
// Gross Revenue from valid bookings
$rev_stmt = $pdo->query("SELECT SUM(total_price) as total_rev FROM bookings WHERE booking_status != 'cancelled'");
$total_revenue = floatval($rev_stmt->fetch()['total_rev'] ?? 0);

// Total Reservations registered
$book_stmt = $pdo->query("SELECT COUNT(*) as total_count FROM bookings");
$total_bookings = intval($book_stmt->fetch()['total_count'] ?? 0);

// Total Rooms & Vacant/Available Rooms
$rooms_stmt = $pdo->query("SELECT COUNT(*) as total_rooms, SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms FROM rooms");
$rooms_info = $rooms_stmt->fetch();
$total_rooms = intval($rooms_info['total_rooms'] ?? 0);
$available_rooms = intval($rooms_info['available_rooms'] ?? 0);

// Hotel Occupancy Rate
$active_guests_stmt = $pdo->query("
    SELECT COUNT(*) as occupied_count 
    FROM bookings 
    WHERE booking_status = 'checked-in'
       OR (booking_status = 'confirmed' AND CURRENT_DATE BETWEEN check_in AND check_out)
");
$occupied_count = intval($active_guests_stmt->fetch()['occupied_count'] ?? 0);
$occupancy_rate = ($total_rooms > 0) ? round(($occupied_count / $total_rooms) * 100, 1) : 0;

// Unread Messages / Inquiries
$msg_stmt = $pdo->query("SELECT COUNT(*) as unread_count FROM messages WHERE status = 'unread'");
$unread_messages = intval($msg_stmt->fetch()['unread_count'] ?? 0);

// 2. Fetch Recent 8 Reservations
$recent_bookings_stmt = $pdo->query("
    SELECT b.*, r.title as room_title, r.room_number 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.id DESC LIMIT 8
");
$recent_bookings = $recent_bookings_stmt->fetchAll();

// 3. Fetch Recent Messages
$recent_msgs_stmt = $pdo->query("SELECT * FROM messages ORDER BY id DESC LIMIT 4");
$recent_messages = $recent_msgs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staycation — Hotel Management Software Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <span class="sidebar-brand">STAYCATION</span>
            <span class="sidebar-sub">Hotel Management Software</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Operations Overview</a></li>
            <li><a href="bookings.php"><i class="fa-solid fa-calendar-days"></i> Reservations Folio</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-bed"></i> Room Inventory</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Guest Inquiries (<?= $unread_messages ?>)</a></li>
            <li><a href="export_bookings.php"><i class="fa-solid fa-file-export"></i> Export Folio (CSV)</a></li>
            <li><a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=hotel_management_db" target="_blank" style="color:var(--accent-gold);"><i class="fa-solid fa-database"></i> XAMPP SQL Database &nearr;</a></li>
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

    <!-- Main Content Area -->
    <main class="admin-main">
        <header class="admin-topbar">
            <h1 class="topbar-title">Staycation Front Desk & Operations Console</h1>
            <div class="topbar-actions">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings" target="_blank" class="btn-ui btn-dark" title="View bookings table in XAMPP phpMyAdmin">
                    <i class="fa-solid fa-database"></i> SQL Bookings Table &nearr;
                </a>
                <a href="../index.php" target="_blank" class="btn-ui btn-dark">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Guest Website
                </a>
                <a href="bookings.php?action=new" class="btn-ui btn-gold">
                    <i class="fa-solid fa-plus"></i> Walk-in Check-in
                </a>
            </div>
        </header>

        <div class="admin-body">
            <!-- Metric Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Total Gross Revenue</span>
                    <div class="stat-value">₹<?= number_format($total_revenue, 2) ?></div>
                    <span class="stat-sub"><i class="fa-solid fa-arrow-trend-up"></i> Confirmed & active folios</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Central Reservations</span>
                    <div class="stat-value"><?= $total_bookings ?></div>
                    <span class="stat-sub">Registered SQL records</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Occupancy Rate</span>
                    <div class="stat-value"><?= $occupancy_rate ?>%</div>
                    <span class="stat-sub"><?= $occupied_count ?> of <?= $total_rooms ?> suites occupied</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Vacant Rooms Available</span>
                    <div class="stat-value"><?= $available_rooms ?></div>
                    <span class="stat-sub">Ready for immediate arrival</span>
                </div>
            </div>

            <!-- SQL Database Quick Navigator Banner -->
            <div style="background: rgba(197, 168, 128, 0.08); border: 1px solid var(--border-gold); border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <i class="fa-solid fa-server" style="font-size: 1.5rem; color: var(--accent-gold);"></i>
                    <div>
                        <strong style="color: #fff; font-size: 0.95rem;">XAMPP MySQL Database Active: <code>hotel_management_db</code></strong>
                        <div style="font-size: 0.8rem; color: var(--text-gray);">Live SQL records stored in Apache/MariaDB. Click tables to inspect raw data:</div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;">
                        <i class="fa-solid fa-table"></i> bookings &nearr;
                    </a>
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=rooms" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;">
                        <i class="fa-solid fa-table"></i> rooms &nearr;
                    </a>
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=users" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;">
                        <i class="fa-solid fa-table"></i> users &nearr;
                    </a>
                    <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;">
                        <i class="fa-solid fa-table"></i> messages &nearr;
                    </a>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <div class="table-card">
                <div class="table-header-bar">
                    <div class="table-title"><i class="fa-solid fa-calendar-check" style="color:var(--accent-gold); margin-right:8px;"></i> Recent Guest Reservations (SQL Table: <code>bookings</code>)</div>
                    <div style="display: flex; gap: 10px;">
                        <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;"><i class="fa-solid fa-database"></i> View in phpMyAdmin</a>
                        <a href="bookings.php" class="btn-ui btn-gold" style="font-size:0.75rem;">Manage All Reservations &rarr;</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Folio Ref</th>
                                <th>Guest Details</th>
                                <th>Assigned Room</th>
                                <th>Dates & Nights</th>
                                <th>Total (₹)</th>
                                <th>Folio Status</th>
                                <th>Quick Front-Desk Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_bookings)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">
                                        No reservations on record yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_bookings as $b): ?>
                                    <tr>
                                        <td>
                                            <a href="../booking-lookup.php?ref=<?= urlencode($b['booking_reference']) ?>" target="_blank" style="font-family: monospace; font-weight: 700; color: var(--accent-gold); text-decoration: underline;">
                                                <?= htmlspecialchars($b['booking_reference']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($b['guest_name']) ?></strong><br>
                                            <small style="color: var(--text-gray);"><?= htmlspecialchars($b['guest_email']) ?></small>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($b['room_title']) ?></strong><br>
                                            <small style="color: var(--accent-gold);">Room <?= htmlspecialchars($b['room_number']) ?></small>
                                        </td>
                                        <td>
                                            <?= date('M j', strtotime($b['check_in'])) ?> &rarr; <?= date('M j, Y', strtotime($b['check_out'])) ?><br>
                                            <small style="color: var(--text-gray);"><?= $b['total_nights'] ?> Night(s) • <?= $b['num_guests'] ?> Guest(s)</small>
                                        </td>
                                        <td>
                                            <strong style="color: #fff;">₹<?= number_format($b['total_price'], 2) ?></strong><br>
                                            <small style="color: #86efac;"><?= htmlspecialchars($b['payment_status']) ?></small>
                                        </td>
                                        <td>
                                            <span class="chip chip-<?= htmlspecialchars($b['booking_status']) ?>">
                                                <?= ucfirst(htmlspecialchars($b['booking_status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline-flex; gap: 6px;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <?php if ($b['booking_status'] === 'confirmed'): ?>
                                                    <button type="submit" name="new_status" value="checked-in" class="btn-ui btn-dark" style="padding:4px 8px; font-size:0.75rem;" title="Check In">Check In</button>
                                                    <button type="submit" name="new_status" value="cancelled" class="btn-ui btn-danger" style="padding:4px 8px; font-size:0.75rem;" title="Cancel" onclick="return confirm('Cancel this reservation?')">Cancel</button>
                                                <?php elseif ($b['booking_status'] === 'checked-in'): ?>
                                                    <button type="submit" name="new_status" value="checked-out" class="btn-ui btn-gold" style="padding:4px 8px; font-size:0.75rem;" title="Check Out">Check Out</button>
                                                <?php else: ?>
                                                    <span style="font-size: 0.75rem; color: var(--text-gray);">&mdash;</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Inquiries Section -->
            <div class="table-card">
                <div class="table-header-bar">
                    <div class="table-title"><i class="fa-solid fa-bell" style="color:var(--accent-gold); margin-right:8px;"></i> Recent Guest Inquiries (SQL Table: <code>messages</code>)</div>
                    <div style="display:flex; gap:10px;">
                        <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages" target="_blank" class="btn-ui btn-dark" style="font-size:0.75rem;"><i class="fa-solid fa-database"></i> View in phpMyAdmin</a>
                        <a href="messages.php" class="btn-ui btn-dark" style="font-size:0.75rem;">View All Inquiries &rarr;</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Guest / Inquirer</th>
                                <th>Subject</th>
                                <th>Message Excerpt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_messages)): ?>
                                <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-gray);">No guest messages logged yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_messages as $m): ?>
                                    <tr>
                                        <td><small><?= date('M j, g:ia', strtotime($m['created_at'])) ?></small></td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($m['name']) ?></strong><br>
                                            <small style="color: var(--text-gray);"><?= htmlspecialchars($m['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($m['subject']) ?></td>
                                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($m['message']) ?>
                                        </td>
                                        <td>
                                            <span class="chip <?= $m['status'] === 'unread' ? 'chip-cancelled' : 'chip-checked-in' ?>">
                                                <?= ucfirst($m['status']) ?>
                                            </span>
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
</body>
</html>

<?php
// admin/messages.php - Staycation Guest Communication & Inquiries
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['message_id'] ?? 0);
    if ($_POST['action'] === 'toggle_status') {
        $new_status = $_POST['new_status'] ?? 'read';
        $stmt = $pdo->prepare("UPDATE messages SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        $msg = "Inquiry marked as {$new_status} in SQL database.";
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Message record removed.";
    }
}

$messages = $pdo->query("SELECT * FROM messages ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Inquiries | Staycation Hotel Management Software</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <span class="sidebar-brand">STAYCATION</span>
            <span class="sidebar-sub">Hotel Management Software</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Operations Overview</a></li>
            <li><a href="bookings.php"><i class="fa-solid fa-calendar-days"></i> Reservations Folio</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-bed"></i> Room Inventory</a></li>
            <li><a href="messages.php" class="active"><i class="fa-solid fa-envelope"></i> Guest Inquiries</a></li>
            <li><a href="export_bookings.php"><i class="fa-solid fa-file-export"></i> Export Folio (CSV)</a></li>
            <li><a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages" target="_blank" style="color:var(--accent-gold);"><i class="fa-solid fa-database"></i> XAMPP SQL Database &nearr;</a></li>
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
            <h1 class="topbar-title">Staycation Guest Inquiries & Communications</h1>
            <div class="topbar-actions">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages" target="_blank" class="btn-ui btn-dark" title="View messages table in XAMPP phpMyAdmin">
                    <i class="fa-solid fa-database"></i> SQL Messages Table &nearr;
                </a>
            </div>
        </header>

        <div class="admin-body">
            <?php if ($msg): ?>
                <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date Received</th>
                                <th>Guest Name</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($messages)): ?>
                                <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-gray);">No messages logged in database.</td></tr>
                            <?php else: ?>
                                <?php foreach ($messages as $m): ?>
                                    <tr>
                                        <td><small><?= date('M j, Y g:ia', strtotime($m['created_at'])) ?></small></td>
                                        <td>
                                            <strong style="color: #fff;"><?= htmlspecialchars($m['name']) ?></strong><br>
                                            <a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color:var(--accent-gold); font-size:0.8rem;"><?= htmlspecialchars($m['email']) ?></a><br>
                                            <small style="color: var(--text-gray);"><?= htmlspecialchars($m['phone']) ?></small>
                                        </td>
                                        <td><strong><?= htmlspecialchars($m['subject']) ?></strong></td>
                                        <td style="max-width: 380px; line-height: 1.5; color: #ddd;">
                                            <?= nl2br(htmlspecialchars($m['message'])) ?>
                                        </td>
                                        <td>
                                            <span class="chip <?= $m['status'] === 'unread' ? 'chip-cancelled' : 'chip-confirmed' ?>">
                                                <?= ucfirst($m['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline-flex; gap:6px;">
                                                <input type="hidden" name="message_id" value="<?= $m['id'] ?>">
                                                <?php if ($m['status'] === 'unread'): ?>
                                                    <button type="submit" name="action" value="toggle_status" class="btn-ui btn-gold" style="padding:4px 8px; font-size:0.75rem;">Mark Read</button>
                                                    <input type="hidden" name="new_status" value="read">
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="toggle_status" class="btn-ui btn-dark" style="padding:4px 8px; font-size:0.75rem;">Mark Unread</button>
                                                    <input type="hidden" name="new_status" value="unread">
                                                <?php endif; ?>
                                                <button type="submit" name="action" value="delete" class="btn-ui btn-danger" style="padding:4px 8px; font-size:0.75rem;" onclick="return confirm('Delete message?');"><i class="fa-solid fa-trash"></i></button>
                                            </form>
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

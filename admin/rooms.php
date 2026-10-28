<?php
// admin/rooms.php - Staycation Room Inventory & Rate Management
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();
$msg = null;
$error = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_room') {
        $room_number = trim($_POST['room_number'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $type = trim($_POST['type'] ?? 'Deluxe Suite');
        $price = floatval($_POST['price_per_night'] ?? 500);
        $capacity = intval($_POST['capacity'] ?? 2);
        $size_sqft = intval($_POST['size_sqft'] ?? 800);
        $bed_type = trim($_POST['bed_type'] ?? 'King Size');
        $view_type = trim($_POST['view_type'] ?? 'Ocean View');
        $image_url = trim($_POST['image_url'] ?? 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80');
        $description = trim($_POST['description'] ?? '');
        $amenities = trim($_POST['amenities'] ?? '');
        $status = $_POST['status'] ?? 'available';

        if (!empty($room_number) && !empty($title)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO rooms (room_number, title, type, price_per_night, capacity, size_sqft, bed_type, view_type, image_url, description, amenities, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$room_number, $title, $type, $price, $capacity, $size_sqft, $bed_type, $view_type, $image_url, $description, $amenities, $status]);
                $msg = "Room {$room_number} added to SQL inventory successfully.";
            } catch (Exception $e) {
                $error = "Error adding room: " . $e->getMessage();
            }
        } else {
            $error = "Room number and title are required.";
        }
    } elseif ($_POST['action'] === 'update_room') {
        $id = intval($_POST['room_id'] ?? 0);
        $price = floatval($_POST['price_per_night'] ?? 500);
        $status = $_POST['status'] ?? 'available';
        $title = trim($_POST['title'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE rooms SET price_per_night = ?, status = ?, title = ? WHERE id = ?");
            $stmt->execute([$price, $status, $title, $id]);
            $msg = "Room details updated in database.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_room') {
        $id = intval($_POST['room_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Room removed from catalog.";
        } catch (Exception $e) {
            $error = "Cannot delete room with active reservation history. Change status to 'maintenance' instead.";
        }
    }
}

// Fetch all rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Inventory | Staycation Hotel Management Software</title>
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
            <li><a href="rooms.php" class="active"><i class="fa-solid fa-bed"></i> Room Inventory</a></li>
            <li><a href="messages.php"><i class="fa-solid fa-envelope"></i> Guest Inquiries</a></li>
            <li><a href="export_bookings.php"><i class="fa-solid fa-file-export"></i> Export Folio (CSV)</a></li>
            <li><a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=rooms" target="_blank" style="color:var(--accent-gold);"><i class="fa-solid fa-database"></i> XAMPP SQL Database &nearr;</a></li>
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
            <h1 class="topbar-title">Staycation Room Inventory & Housekeeping</h1>
            <div class="topbar-actions">
                <a href="http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=rooms" target="_blank" class="btn-ui btn-dark" title="View rooms table in XAMPP phpMyAdmin">
                    <i class="fa-solid fa-database"></i> SQL Rooms Table &nearr;
                </a>
                <button onclick="document.getElementById('add-room-modal').style.display='flex'" class="btn-ui btn-gold">
                    <i class="fa-solid fa-plus"></i> Add New Room
                </button>
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

            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Room No.</th>
                                <th>Photo</th>
                                <th>Room Title & Category</th>
                                <th>Nightly Rate</th>
                                <th>Room Specs</th>
                                <th>Operational Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $r): ?>
                                <tr>
                                    <td>
                                        <span style="font-family: monospace; font-size: 1.1rem; font-weight: 700; color: var(--accent-gold);">
                                            <?= htmlspecialchars($r['room_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <img src="<?= htmlspecialchars($r['image_url']) ?>" alt="Room" style="width: 70px; height: 50px; object-fit: cover; border-radius: 6px;">
                                    </td>
                                    <td>
                                        <strong style="color: #fff;"><?= htmlspecialchars($r['title']) ?></strong><br>
                                        <small style="color: var(--text-gray);"><?= htmlspecialchars($r['type']) ?></small>
                                    </td>
                                    <td>
                                        <span style="font-size: 1rem; font-weight: 700; color: #fff;">₹<?= number_format($r['price_per_night'], 2) ?></span>
                                    </td>
                                    <td>
                                        <small style="color: var(--text-gray);">
                                            <?= $r['capacity'] ?> Guests • <?= $r['size_sqft'] ?> sq ft<br>
                                            <?= htmlspecialchars($r['bed_type']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_room">
                                            <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                            <input type="hidden" name="price_per_night" value="<?= $r['price_per_night'] ?>">
                                            <input type="hidden" name="title" value="<?= htmlspecialchars($r['title']) ?>">
                                            <select name="status" onchange="this.form.submit()" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--border-subtle); padding: 4px 8px; border-radius: 4px; font-size: 0.78rem;">
                                                <option value="available" <?= $r['status'] === 'available' ? 'selected' : '' ?>>Available (Vacant)</option>
                                                <option value="occupied" <?= $r['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                                <option value="maintenance" <?= $r['status'] === 'maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this room record?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_room">
                                            <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn-ui btn-danger" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Room Modal -->
    <div id="add-room-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:var(--bg-card); border:1px solid var(--border-gold); border-radius:16px; width:100%; max-width:680px; padding:32px; max-height:90vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:1.6rem; color:#fff;">Add Room to Staycation Inventory</h2>
                <button onclick="document.getElementById('add-room-modal').style.display='none'" style="background:transparent; border:none; color:var(--text-gray); font-size:1.3rem; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="add_room">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label class="stat-label">Room / Suite Number *</label>
                        <input type="text" name="room_number" required placeholder="e.g. 701" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                    </div>
                    <div>
                        <label class="stat-label">Room Category</label>
                        <select name="type" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                            <option value="Presidential Suite">Presidential Suite</option>
                            <option value="Ocean Front Villa">Ocean Front Villa</option>
                            <option value="Penthouse Vista">Penthouse Vista</option>
                            <option value="Deluxe Garden Suite">Deluxe Garden Suite</option>
                            <option value="Executive Atelier">Executive Atelier</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="stat-label">Display Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Staycation Horizon Penthouse" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div>
                        <label class="stat-label">Price / Night ($)</label>
                        <input type="number" step="0.01" name="price_per_night" value="850" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                    </div>
                    <div>
                        <label class="stat-label">Max Guests</label>
                        <input type="number" name="capacity" value="2" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                    </div>
                    <div>
                        <label class="stat-label">Area (Sq Ft)</label>
                        <input type="number" name="size_sqft" value="1200" required style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="stat-label">Image URL</label>
                    <input type="url" name="image_url" value="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                </div>

                <div style="margin-bottom:14px;">
                    <label class="stat-label">Room Description</label>
                    <textarea name="description" rows="2" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;"></textarea>
                </div>

                <div style="margin-bottom:20px;">
                    <label class="stat-label">Amenities (Comma-separated)</label>
                    <input type="text" name="amenities" value="Private Infinity Pool, Marshall Acoustics, Butler Service, Rain Shower" style="width:100%; padding:10px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border-subtle); border-radius:6px;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" onclick="document.getElementById('add-room-modal').style.display='none'" class="btn-ui btn-dark">Cancel</button>
                    <button type="submit" class="btn-ui btn-gold">Save to SQL Inventory</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

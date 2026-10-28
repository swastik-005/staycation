<?php
// api/check_availability.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();

$check_in = $_GET['check_in'] ?? null;
$check_out = $_GET['check_out'] ?? null;
$guests = intval($_GET['guests'] ?? 1);
$type = $_GET['type'] ?? '';

if (!$check_in || !$check_out) {
    echo json_encode(['success' => false, 'error' => 'Please provide valid check-in and check-out dates.']);
    exit;
}

$in_time = strtotime($check_in);
$out_time = strtotime($check_out);

if ($out_time <= $in_time) {
    echo json_encode(['success' => false, 'error' => 'Check-out date must be after check-in date.']);
    exit;
}

$nights = max(1, round(($out_time - $in_time) / (60 * 60 * 24)));

// Query rooms that are available and not booked in this range
$query = "
    SELECT r.* 
    FROM rooms r
    WHERE r.status = 'available'
      AND r.capacity >= :guests
      AND r.id NOT IN (
          SELECT b.room_id 
          FROM bookings b
          WHERE b.booking_status IN ('confirmed', 'checked-in')
            AND (
                (b.check_in <= :check_out AND b.check_out >= :check_in)
            )
      )
";

$params = [
    ':guests' => $guests,
    ':check_in' => $check_in,
    ':check_out' => $check_out
];

if (!empty($type) && $type !== 'all') {
    $query .= " AND r.type = :type";
    $params[':type'] = $type;
}

$query .= " ORDER BY r.price_per_night ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();

    foreach ($rooms as &$room) {
        $room['nights'] = $nights;
        $room['subtotal'] = floatval($room['price_per_night']) * $nights;
        $room['tax'] = round($room['subtotal'] * 0.12, 2);
        $room['total_price'] = $room['subtotal'] + $room['tax'];
        $room['amenities_list'] = array_map('trim', explode(',', $room['amenities']));
    }

    echo json_encode([
        'success' => true,
        'count' => count($rooms),
        'nights' => $nights,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'rooms' => $rooms
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


<?php
// api/book_room.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Get JSON or form body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$room_id = intval($input['room_id'] ?? 0);
$guest_name = trim($input['guest_name'] ?? '');
$guest_email = trim($input['guest_email'] ?? '');
$guest_phone = trim($input['guest_phone'] ?? '');
$check_in = trim($input['check_in'] ?? '');
$check_out = trim($input['check_out'] ?? '');
$num_guests = max(1, intval($input['num_guests'] ?? 1));
$special_requests = trim($input['special_requests'] ?? '');
$payment_method = trim($input['payment_method'] ?? 'Mastercard / Visa (Instant)');

// Validation
if (!$room_id || empty($guest_name) || empty($guest_email) || empty($guest_phone) || empty($check_in) || empty($check_out)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

$in_time = strtotime($check_in);
$out_time = strtotime($check_out);

if ($out_time <= $in_time) {
    echo json_encode(['success' => false, 'error' => 'Check-out date must be after check-in date.']);
    exit;
}

$nights = max(1, round(($out_time - $in_time) / (60 * 60 * 24)));

$pdo = getDBConnection();

try {
    // 1. Verify Room exists and get price
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch();

    if (!$room) {
        echo json_encode(['success' => false, 'error' => 'The selected suite does not exist.']);
        exit;
    }

    if ($num_guests > $room['capacity']) {
        echo json_encode(['success' => false, 'error' => "This suite accommodates a maximum of {$room['capacity']} guests."]);
        exit;
    }

    // 2. Check for date collision
    $conflict_stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE room_id = ? 
          AND booking_status IN ('confirmed', 'checked-in')
          AND ((check_in <= ? AND check_out >= ?))
    ");
    $conflict_stmt->execute([$room_id, $check_out, $check_in]);
    if ($conflict_stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This suite is no longer available for the chosen dates. Please choose another date range or suite.']);
        exit;
    }

    // 3. Calculate financial totals
    $price_per_night = floatval($room['price_per_night']);
    $subtotal = $price_per_night * $nights;
    $tax_amount = round($subtotal * 0.12, 2);
    $total_price = $subtotal + $tax_amount;

    // 4. Generate unique reference code
    $booking_reference = 'STAY-' . strtoupper(substr(uniqid(), -6));

    // 5. Insert booking
    $insert = $pdo->prepare("
        INSERT INTO bookings (
            booking_reference, room_id, guest_name, guest_email, guest_phone,
            check_in, check_out, total_nights, num_guests,
            subtotal, tax_amount, total_price,
            booking_status, payment_status, payment_method, special_requests
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            'confirmed', 'paid', ?, ?
        )
    ");

    $insert->execute([
        $booking_reference, $room_id, $guest_name, $guest_email, $guest_phone,
        $check_in, $check_out, $nights, $num_guests,
        $subtotal, $tax_amount, $total_price,
        $payment_method, $special_requests
    ]);

    $booking_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Reservation confirmed successfully!',
        'booking' => [
            'id' => $booking_id,
            'booking_reference' => $booking_reference,
            'room_title' => $room['title'],
            'room_number' => $room['room_number'],
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'check_in' => $check_in,
            'check_out' => $check_out,
            'nights' => $nights,
            'num_guests' => $num_guests,
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'total_price' => $total_price,
            'payment_status' => 'paid',
            'payment_method' => $payment_method
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


<?php
// api/get_booking.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$reference = trim($_GET['reference'] ?? '');
$email = trim($_GET['email'] ?? '');

if (empty($reference)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a booking reference.']);
    exit;
}

$pdo = getDBConnection();

try {
    $sql = "
        SELECT b.*, r.title AS room_title, r.type AS room_type, r.room_number, r.image_url AS room_image,
               r.bed_type, r.view_type, r.size_sqft, r.amenities
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE UPPER(b.booking_reference) = UPPER(?)
    ";
    $params = [$reference];

    if (!empty($email)) {
        $sql .= " AND LOWER(b.guest_email) = LOWER(?)";
        $params[] = $email;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'No reservation found matching the provided details.']);
        exit;
    }

    $booking['amenities_list'] = array_map('trim', explode(',', $booking['amenities']));

    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


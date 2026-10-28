<?php
// api/cancel_booking.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$reference = trim($input['reference'] ?? '');
$email = trim($input['email'] ?? '');

if (empty($reference) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Reference number and email are required for cancellation.']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE UPPER(booking_reference) = UPPER(?) AND LOWER(guest_email) = LOWER(?)");
    $stmt->execute([$reference, $email]);
    $booking = $stmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Reservation not found.']);
        exit;
    }

    if ($booking['booking_status'] === 'cancelled') {
        echo json_encode(['success' => false, 'error' => 'This reservation has already been cancelled.']);
        exit;
    }

    $update = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled', payment_status = 'refunded' WHERE id = ?");
    $update->execute([$booking['id']]);

    echo json_encode([
        'success' => true,
        'message' => "Reservation #{$reference} has been successfully cancelled and refund initiated."
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


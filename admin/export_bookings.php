<?php
// admin/export_bookings.php - CSV Export of Bookings Folio
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT b.booking_reference, b.guest_name, b.guest_email, b.guest_phone,
           r.title AS suite_title, r.room_number,
           b.check_in, b.check_out, b.total_nights, b.num_guests,
           b.subtotal, b.tax_amount, b.total_price,
           b.booking_status, b.payment_status, b.payment_method,
           b.special_requests, b.created_at
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.id DESC
");

$bookings = $stmt->fetchAll();

$filename = "staycation_bookings_" . date('Y_m_d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$output = fopen('php://output', 'w');

// CSV Header
fputcsv($output, [
    'Reference ID', 'Guest Name', 'Email', 'Phone',
    'Suite Title', 'Suite No',
    'Check In', 'Check Out', 'Nights', 'Guests',
    'Subtotal (INR)', 'Tax (INR)', 'Total (INR)',
    'Booking Status', 'Payment Status', 'Payment Method',
    'Special Requests', 'Created At'
]);

// Rows
foreach ($bookings as $b) {
    fputcsv($output, [
        $b['booking_reference'],
        $b['guest_name'],
        $b['guest_email'],
        $b['guest_phone'],
        $b['suite_title'],
        $b['room_number'],
        $b['check_in'],
        $b['check_out'],
        $b['total_nights'],
        $b['num_guests'],
        $b['subtotal'],
        $b['tax_amount'],
        $b['total_price'],
        $b['booking_status'],
        $b['payment_status'],
        $b['payment_method'],
        $b['special_requests'],
        $b['created_at']
    ]);
}

fclose($output);
exit;


<?php
include('db_connect.php');
// only accept POST for booking creation — otherwise return to the booking screen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: book_service.php'); exit; }

$client_id = intval($_POST['client_id'] ?? 0);
$service_id = intval($_POST['service_id'] ?? 0);
$staff_id = isset($_POST['staff_id']) && $_POST['staff_id'] !== '' ? intval($_POST['staff_id']) : null;
$scheduled_raw = trim((string)($_POST['scheduled_at'] ?? ''));

if ($client_id <= 0 || $service_id <= 0) {
    header('Location: book_service.php?client_id=' . intval($client_id) . '&error=missing');
    exit;
}

// normalize and validate scheduled_at (HTML datetime-local -> MySQL DATETIME)
$scheduled_at = null;
if ($scheduled_raw !== '') {
    $normalized = str_replace('T', ' ', $scheduled_raw);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
        $normalized .= ':00';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalized);
    if ($dt === false) {
        header('Location: book_service.php?client_id=' . intval($client_id) . '&error=missing');
        exit;
    }
    $scheduled_at = $dt->format('Y-m-d H:i:s');
}

// build INSERT using prepared statements and explicit branches to allow NULL values
if ($staff_id === null && $scheduled_at === null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (client_id, service_id, staff_id, scheduled_at) VALUES (?, ?, NULL, NULL)");
    mysqli_stmt_bind_param($stmt, 'ii', $client_id, $service_id);
} elseif ($staff_id === null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (client_id, service_id, staff_id, scheduled_at) VALUES (?, ?, NULL, ?)");
    mysqli_stmt_bind_param($stmt, 'iis', $client_id, $service_id, $scheduled_at);
} elseif ($scheduled_at === null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (client_id, service_id, staff_id, scheduled_at) VALUES (?, ?, ?, NULL)");
    mysqli_stmt_bind_param($stmt, 'iii', $client_id, $service_id, $staff_id);
} else {
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (client_id, service_id, staff_id, scheduled_at) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iiss', $client_id, $service_id, $staff_id, $scheduled_at);
}

if (! $stmt) {
    header('Location: book_service.php?client_id=' . intval($client_id) . '&error=sql');
    exit;
}

$ok = mysqli_stmt_execute($stmt);
if ($ok) {
    $booking_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    header('Location: payment.php?booking_id=' . intval($booking_id));
    exit;
} else {
    mysqli_stmt_close($stmt);
    header('Location: book_service.php?client_id=' . intval($client_id) . '&error=sql');
    exit;
}

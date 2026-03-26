<?php
// actions/booking.php
require_once '../includes/auth.php';
require_once '../includes/config.php';
requireLogin();

$pdo    = getDB();
$action = $_POST['action'] ?? '';
$user   = currentUser();
$redirectDate = $_POST['redirect_date'] ?? date('Y-m-d');

if ($action === 'edit_room') {
    requireAdminIT();
    $roomId      = (int)($_POST['room_id'] ?? 0);
    $name        = trim($_POST['room_name'] ?? '');
    $description = trim($_POST['room_description'] ?? '');
    $capacity    = max(1, (int)($_POST['room_capacity'] ?? 1));
    $color       = $_POST['room_color'] ?? 'room-blue';
    $allowed     = ['room-red','room-blue','room-yellow','room-green','room-purple','room-orange'];
    if (!$roomId || !$name || !in_array($color, $allowed)) redirect('error', $redirectDate);
    $stmt = $pdo->prepare("UPDATE meeting_rooms SET name=?, description=?, capacity=?, color_class=? WHERE id=?");
    $stmt->execute([$name, $description, $capacity, $color, $roomId]);
    redirect('room_updated', $redirectDate);
}

if ($action === 'add_room') {
    requireAdminIT();
    $name        = trim($_POST['room_name'] ?? '');
    $description = trim($_POST['room_description'] ?? '');
    $capacity    = max(1, (int)($_POST['room_capacity'] ?? 1));
    $color       = $_POST['room_color'] ?? 'room-blue';
    $allowed     = ['room-red','room-blue','room-yellow','room-green','room-purple'];
    if (!$name || !in_array($color, $allowed)) redirect('error', $redirectDate);
    $stmt = $pdo->prepare("INSERT INTO meeting_rooms (name, description, capacity, color_class) VALUES (?,?,?,?)");
    $stmt->execute([$name, $description, $capacity, $color]);
    redirect('room_added', $redirectDate);
}

if ($action === 'delete_room') {
    requireAdminIT();
    $roomId = (int)($_POST['room_id'] ?? 0);
    if (!$roomId) redirect('error', $redirectDate);
    // Delete all bookings for this room first
    $stmt = $pdo->prepare("DELETE FROM room_bookings WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $stmt = $pdo->prepare("DELETE FROM meeting_rooms WHERE id = ?");
    $stmt->execute([$roomId]);
    redirect('room_deleted', $redirectDate);
}

if ($action === 'add') {
    $stmt = $pdo->prepare("
        INSERT INTO room_bookings (room_id, booked_by_id, booked_by_name, booking_date, start_time, end_time, purpose)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        (int)$_POST['room_id'],
        $user['id'],
        $user['name'],
        $_POST['booking_date'],
        $_POST['start_time'],
        $_POST['end_time'],
        trim($_POST['purpose']),
    ]);
    redirect('booked', $redirectDate);
}

if ($action === 'edit') {
    $id = (int)$_POST['id'];
    // Check ownership
    $stmt = $pdo->prepare("SELECT booked_by_id FROM room_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if (!$booking || (!isAdmin() && $booking['booked_by_id'] != $user['id'])) {
        redirect('error', $redirectDate);
    }

    $stmt = $pdo->prepare("
        UPDATE room_bookings SET
            room_id = ?, booking_date = ?, start_time = ?, end_time = ?, purpose = ?
        WHERE id = ?
    ");
    $stmt->execute([
        (int)$_POST['room_id'],
        $_POST['booking_date'],
        $_POST['start_time'],
        $_POST['end_time'],
        trim($_POST['purpose']),
        $id,
    ]);
    redirect('updated', $redirectDate);
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT booked_by_id FROM room_bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if (!$booking || (!isAdmin() && $booking['booked_by_id'] != $user['id'])) {
        redirect('error', $redirectDate);
    }

    $stmt = $pdo->prepare("DELETE FROM room_bookings WHERE id = ?");
    $stmt->execute([$id]);
    redirect('cancelled', $redirectDate);
}

function redirect($toast, $date) {
    header("Location: ../dashboard.php?page=rooms&date=" . urlencode($date) . "&toast=$toast&toast_type=success");
    exit;
}

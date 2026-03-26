<?php
// pages/rooms.php
require_once __DIR__ . '/../includes/config.php';
$pdo  = getDB();
$user = currentUser();

$viewDate = $_GET['date'] ?? date('Y-m-d');
$rooms = $pdo->query("SELECT * FROM meeting_rooms ORDER BY id")->fetchAll();

$stmt = $pdo->prepare("
    SELECT rb.*, mr.name as room_name, mr.color_class, mr.capacity
    FROM room_bookings rb
    JOIN meeting_rooms mr ON rb.room_id = mr.id
    WHERE rb.booking_date = ?
    ORDER BY rb.room_id, rb.start_time
");
$stmt->execute([$viewDate]);
$dayBookings = $stmt->fetchAll();

$bookingsByRoom = [];
foreach ($dayBookings as $b) { $bookingsByRoom[$b['room_id']][] = $b; }

$startHour   = 7;
$endHour     = 20;
$slotMinutes = 30;
$totalSlots  = (($endHour - $startHour) * 60) / $slotMinutes;

function toMinutes($timeStr) {
    [$h, $m] = explode(':', $timeStr);
    return (int)$h * 60 + (int)$m;
}

$roomSlotMap = [];
foreach ($rooms as $rm) {
    $rid   = $rm['id'];
    $slots = array_fill(0, $totalSlots, null);
    foreach ($bookingsByRoom[$rid] ?? [] as $b) {
        $bStart    = toMinutes($b['start_time']);
        $bEnd      = toMinutes($b['end_time']);
        $gridStart = $startHour * 60;
        for ($s = 0; $s < $totalSlots; $s++) {
            $slotStart = $gridStart + $s * $slotMinutes;
            $slotEnd   = $slotStart + $slotMinutes;
            if ($slotStart < $bEnd && $slotEnd > $bStart) { $slots[$s] = $b; }
        }
    }
    $roomSlotMap[$rid] = $slots;
}

function getBookingFirstSlot($booking, $startHour, $slotMinutes) {
    $bStartMin = toMinutes($booking['start_time']);
    $gridStart = $startHour * 60;
    return max(0, (int)(($bStartMin - $gridStart) / $slotMinutes));
}
function getBookingRowspan($booking, $slotMinutes) {
    $bStartMin = toMinutes($booking['start_time']);
    $bEndMin   = toMinutes($booking['end_time']);
    return max(1, (int)(ceil(($bEndMin - $bStartMin) / $slotMinutes)));
}
?>

<div class="page-header">
    <div>
        <h2>Meeting Room Booking</h2>
        <p class="page-subtitle">Timetable — <?= date('l, d F Y', strtotime($viewDate)) ?></p>
    </div>
    <div style="display:flex; gap:.5rem;">
        <?php if (isAdminIT()): ?>
        <button class="btn btn-outline" onclick="openModal('manageRoomsModal')">Manage Rooms</button>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="openBookingModal(null)">+ New Booking</button>
    </div>
</div>

<div class="date-nav">
    <a href="?page=rooms&date=<?= date('Y-m-d', strtotime($viewDate . ' -1 day')) ?>" class="btn btn-outline btn-sm">← Prev</a>
    <form method="GET" class="date-form">
        <input type="hidden" name="page" value="rooms">
        <input type="date" name="date" value="<?= $viewDate ?>" onchange="this.form.submit()" class="date-input">
    </form>
    <a href="?page=rooms&date=<?= date('Y-m-d', strtotime($viewDate . ' +1 day')) ?>" class="btn btn-outline btn-sm">Next →</a>
    <a href="?page=rooms&date=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Today</a>
</div>

<div class="card timetable-card">
    <div class="timetable-scroll">
        <table class="timetable">
            <thead>
                <tr>
                    <th class="tt-time-col">Time</th>
                    <?php foreach ($rooms as $rm): ?>
                    <th class="tt-room-col">
                        <div class="tt-room-header tt-header-<?= $rm['color_class'] ?>">
                            <span class="tt-dot tt-dot-<?= $rm['color_class'] ?>"></span>
                            <div class="tt-room-info">
                                <strong><?= htmlspecialchars($rm['name']) ?></strong>
                            </div>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $skipSlot = [];
            for ($s = 0; $s < $totalSlots; $s++):
                $slotMinAbs = $startHour * 60 + $s * $slotMinutes;
                $slotH      = intdiv($slotMinAbs, 60);
                $slotM      = $slotMinAbs % 60;
                $timeLabel  = sprintf('%02d:%02d', $slotH, $slotM);
                $isHour     = ($slotM === 0);
            ?>
            <tr class="tt-row <?= $isHour ? 'tt-row-hour' : 'tt-row-half' ?>">
                <td class="tt-time-cell <?= $isHour ? 'tt-time-major' : 'tt-time-minor' ?>">
                    <?php if ($isHour): ?><span class="tt-hour-label"><?= $timeLabel ?></span><?php else: ?><span class="tt-half-label">— :30</span><?php endif; ?>
                </td>

                <?php foreach ($rooms as $rm):
                    $rid = $rm['id'];
                    if (!empty($skipSlot[$rid][$s])) continue;

                    $booking = $roomSlotMap[$rid][$s] ?? null;

                    if ($booking):
                        $firstSlot = getBookingFirstSlot($booking, $startHour, $slotMinutes);
                        if ($s === $firstSlot):
                            $rowspan  = getBookingRowspan($booking, $slotMinutes);
                            
                            // ISOLATION FIX: Only Admins OR the Booking Owner can edit
                            $canEdit  = isAdmin() || ($booking['booked_by_id'] == $user['id']);
                            
                            for ($r = 1; $r < $rowspan; $r++) { $skipSlot[$rid][$s + $r] = true; }
                ?>
                        <td rowspan="<?= $rowspan ?>" class="tt-booked-cell tt-booked-<?= $rm['color_class'] ?>">
                            <div class="tt-booking-block">
                                <div class="tt-bk-time"><?= date('H:i', strtotime($booking['start_time'])) ?>–<?= date('H:i', strtotime($booking['end_time'])) ?></div>
                                <div class="tt-bk-purpose"><?= htmlspecialchars($booking['purpose']) ?></div>
                                <div class="tt-bk-by">By: <?= htmlspecialchars($booking['booked_by_name']) ?></div>
                                <?php if ($canEdit): ?>
                                <div class="tt-bk-actions">
                                    <button type="button" class="tt-btn-edit" onclick="openBookingModal(<?= htmlspecialchars(json_encode($booking)) ?>)">✏</button>
                                    <button type="button" class="tt-btn-cancel" onclick="confirmCancelBooking(<?= $booking['id'] ?>)">✕</button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                <?php
                        endif;
                    else:
                ?>
                        <td class="tt-free-cell" onclick="openBookingModalWithTime('<?= $rm['id'] ?>', '<?= $timeLabel ?>')">
                            <span class="tt-free-hint">＋</span>
                        </td>
                <?php
                    endif;
                endforeach; ?>
            </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal" id="bookingModal">
    <div class="modal-box">
        <div class="modal-header"><h3 id="bookingModalTitle">New Booking</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <div id="conflictWarning" class="alert alert-warning" style="display:none; margin:1rem 1.5rem 0;">⚠️ Time slot overlaps.</div>
        <form method="POST" action="actions/booking.php">
            <input type="hidden" name="action" id="bookingAction" value="add">
            <input type="hidden" name="id" id="bookingId" value="">
            <input type="hidden" name="redirect_date" value="<?= $viewDate ?>">
            <div class="form-grid" style="padding:1.25rem 1.5rem 0;">
                <div class="form-group">
                    <label>Room *</label>
                    <select name="room_id" id="b_room" required onchange="checkConflict()">
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $rm): ?><option value="<?= $rm['id'] ?>"><?= htmlspecialchars($rm['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Date *</label><input type="date" name="booking_date" id="b_date" value="<?= $viewDate ?>" required onchange="checkConflict()" min="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label>Start Time *</label><input type="time" name="start_time" id="b_start" required onchange="checkConflict()"></div>
                <div class="form-group"><label>End Time *</label><input type="time" name="end_time" id="b_end" required onchange="checkConflict()"></div>
                <div class="form-group form-full"><label>Purpose / Agenda *</label><textarea name="purpose" id="b_purpose" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-primary">Save Booking</button></div>
        </form>
    </div>
</div>

<div class="modal" id="cancelBookingModal">
    <div class="modal-box modal-sm">
        <div class="modal-header"><h3>Cancel Booking</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <p style="padding:1rem 1.5rem 0;">Cancel this booking?</p>
        <form method="POST" action="actions/booking.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="cancelBookingId" value="">
            <input type="hidden" name="redirect_date" value="<?= $viewDate ?>">
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal()">Keep It</button><button type="submit" class="btn btn-danger">Yes, Cancel</button></div>
        </form>
    </div>
</div>

<script>
const existingBookings = <?= json_encode($dayBookings) ?>;

function openBookingModal(data) {
    document.getElementById('conflictWarning').style.display = 'none';
    if (data) {
        document.getElementById('bookingModalTitle').textContent = 'Edit Booking';
        document.getElementById('bookingAction').value = 'edit';
        document.getElementById('bookingId').value = data.id;
        document.getElementById('b_room').value = data.room_id;
        document.getElementById('b_date').value = data.booking_date;
        document.getElementById('b_start').value = (data.start_time || '').substring(0,5);
        document.getElementById('b_end').value   = (data.end_time   || '').substring(0,5);
        document.getElementById('b_purpose').value = data.purpose;
    } else {
        document.getElementById('bookingModalTitle').textContent = 'New Booking';
        document.getElementById('bookingAction').value = 'add';
        document.getElementById('bookingId').value = '';
        document.querySelector('#bookingModal form').reset();
        document.getElementById('b_date').value = '<?= $viewDate ?>';
    }
    openModal('bookingModal');
}

function openBookingModalWithTime(roomId, time) {
    openBookingModal(null);
    document.getElementById('b_room').value  = roomId;
    document.getElementById('b_start').value = time;
    const [h, m] = time.split(':').map(Number);
    const endH = String(h + 1 > 23 ? 23 : h + 1).padStart(2,'0');
    document.getElementById('b_end').value = endH + ':' + String(m).padStart(2,'0');
    checkConflict();
}

function confirmCancelBooking(id) { document.getElementById('cancelBookingId').value = id; openModal('cancelBookingModal'); }

function checkConflict() {
    const roomId = document.getElementById('b_room').value;
    const date   = document.getElementById('b_date').value;
    const start  = document.getElementById('b_start').value;
    const end    = document.getElementById('b_end').value;
    const editId = document.getElementById('bookingId').value;
    if (!roomId || !date || !start || !end) return;
    const conflict = existingBookings.some(b => {
        if (b.room_id != roomId || b.booking_date !== date || (editId && b.id == editId)) return false;
        const bs = (b.start_time||'').substring(0,5);
        const be = (b.end_time||'').substring(0,5);
        return start < be && end > bs;
    });
    document.getElementById('conflictWarning').style.display = conflict ? 'block' : 'none';
}
</script>
<?php

// ============================================================
// book_slot.php — Réservation (POST depuis confirm_booking.php)
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('slots.php');
}

verifyCsrf();

$slotId = (int) ($_POST['slot_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('slots.php');
}

$stmt = $pdo->prepare("SELECT * FROM slots WHERE id = ? LIMIT 1");
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot)                        { flashSet('error', 'Créneau introuvable.');            redirect('slots.php'); }
if ($slot['user_id'] == $userId)   { flashSet('error', 'Vous ne pouvez pas réserver votre propre créneau.'); redirect('slots.php'); }
if ($slot['is_booked'])            { flashSet('error', 'Ce créneau vient d\'être réservé par quelqu\'un d\'autre.'); redirect('slots.php'); }
if ($slot['date'] < date('Y-m-d')) { flashSet('error', 'Ce créneau est expiré.');          redirect('slots.php'); }

try {
    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("
        UPDATE slots SET is_booked = 1, booked_by = ?
        WHERE id = ? AND is_booked = 0
    ");
    $stmtUpdate->execute([$userId, $slotId]);

    if ($stmtUpdate->rowCount() === 0) {
        $pdo->rollBack();
        flashSet('error', 'Ce créneau vient d\'être réservé par quelqu\'un d\'autre.');
        redirect('slots.php');
    }

    $pdo->prepare("INSERT INTO bookings (slot_id, booker_id) VALUES (?, ?)")
        ->execute([$slotId, $userId]);

    $pdo->commit();

    flashSet('success', '✅ Réservation confirmée ! Rendez-vous le ' . date('d/m/Y', strtotime($slot['date'])) . ' à ' . substr($slot['start_time'],0,5) . '.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Booking error: " . $e->getMessage());
    flashSet('error', 'Une erreur est survenue. Veuillez réessayer.');
}

redirect('history.php');
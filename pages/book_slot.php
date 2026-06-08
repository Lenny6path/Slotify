<?php

// ============================================================
// book_slot.php — Réservation d'un créneau (POST uniquement)
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('slots.php');
}

verifyCsrf();

$slotId = (int) (isset($_POST['slot_id']) ? $_POST['slot_id'] : 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('slots.php');
}

// --- Récupérer le créneau avec verrouillage ---
$stmt = $pdo->prepare("SELECT * FROM slots WHERE id = ? LIMIT 1");
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot) {
    redirect('slots.php');
}

// Anti auto-booking
if ($slot['user_id'] == $userId) {
    redirect('slots.php');
}

// Déjà réservé
if ($slot['is_booked']) {
    redirect('slots.php');
}

// Créneau expiré
if ($slot['date'] < date('Y-m-d')) {
    redirect('slots.php');
}

// --- Transaction : update slot + insert booking ---
try {
    $pdo->beginTransaction();

    // 1. Marquer le slot comme réservé
    $stmtUpdate = $pdo->prepare("
        UPDATE slots
        SET is_booked = 1,
            booked_by = ?
        WHERE id = ?
          AND is_booked = 0
    ");
    $stmtUpdate->execute([$userId, $slotId]);

    if ($stmtUpdate->rowCount() === 0) {
        // Quelqu'un d'autre a réservé entre-temps (race condition)
        $pdo->rollBack();
        redirect('slots.php');
    }

    // 2. Insérer dans l'historique bookings
    $stmtBooking = $pdo->prepare("
        INSERT INTO bookings (slot_id, booker_id) VALUES (?, ?)
    ");
    $stmtBooking->execute([$slotId, $userId]);

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Booking error: " . $e->getMessage());
    redirect('slots.php');
}

redirect('slots.php');
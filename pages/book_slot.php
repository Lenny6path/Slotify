<?php

// ============================================================
// book_slot.php — Réservation d'un créneau
//
// Ce fichier n'affiche rien : il reçoit le POST envoyé depuis
// la page de confirmation, fait toutes les vérifications, écrit
// en base, puis redirige avec un message flash.
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

// On n'accepte que du POST : taper l'URL à la main dans le
// navigateur (= GET) ne doit jamais déclencher une réservation
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

// La réservation se fait en 2 écritures (UPDATE slots + INSERT
// bookings). La transaction garantit le "tout ou rien" : si la
// 2e échoue, la 1ère est annulée. Pas de données à moitié écrites.
try {
    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("
        UPDATE slots SET is_booked = 1, booked_by = ?
        WHERE id = ? AND is_booked = 0
    ");
    $stmtUpdate->execute([$userId, $slotId]);

    // Cas limite mais réel : deux personnes cliquent "Réserver" au
    // même moment. Grâce au "AND is_booked = 0" dans le WHERE, un
    // seul UPDATE passera. L'autre aura rowCount = 0 et sera
    // gentiment prévenu que le créneau vient de partir.
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
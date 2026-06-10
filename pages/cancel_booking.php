<?php

// ============================================================
// cancel_booking.php — Annulation d'une réservation
// Délai limite : 24h avant le créneau
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('history.php');
}

verifyCsrf();

$slotId = (int) ($_POST['slot_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('history.php');
}

// Récupérer le créneau
$stmt = $pdo->prepare("SELECT * FROM slots WHERE id = ? LIMIT 1");
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot) {
    flashSet('error', 'Créneau introuvable.');
    redirect('history.php');
}

// Vérifier que c'est bien l'utilisateur qui a réservé
if ($slot['booked_by'] != $userId) {
    flashSet('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
    redirect('history.php');
}

// Règle métier : annulation possible jusqu'à 24h avant le RDV.
// On reconstruit le datetime complet du créneau (date + heure),
// puis on calcule combien d'heures il reste d'ici là.
$slotDatetime = strtotime($slot['date'] . ' ' . $slot['start_time']);
$now          = time();
$heuresRestantes = ($slotDatetime - $now) / 3600;

if ($heuresRestantes < 24) {
    flashSet('error', 'Annulation impossible : le rendez-vous est dans moins de 24h.');
    redirect('history.php');
}

// L'annulation remet le créneau en vente : is_booked repasse à 0
// et booked_by est vidé. On nettoie aussi la table bookings.
// Même principe de transaction que pour la réservation.
try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE slots SET is_booked = 0, booked_by = NULL WHERE id = ?")
        ->execute([$slotId]);

    $pdo->prepare("DELETE FROM bookings WHERE slot_id = ? AND booker_id = ?")
        ->execute([$slotId, $userId]);

    $pdo->commit();

    flashSet('success', 'Réservation annulée avec succès.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Cancel error: " . $e->getMessage());
    flashSet('error', 'Une erreur est survenue. Veuillez réessayer.');
}

redirect('history.php');
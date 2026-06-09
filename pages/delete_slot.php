<?php

// ============================================================
// delete_slot.php — Suppression d'un créneau
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

verifyCsrf();

$slotId = (int) ($_POST['slot_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("SELECT id, is_booked FROM slots WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$slotId, $userId]);
$slot = $stmt->fetch();

if (!$slot) {
    flashSet('error', 'Créneau introuvable.');
    redirect('dashboard.php');
}

if ($slot['is_booked']) {
    flashSet('error', 'Impossible de supprimer un créneau déjà réservé.');
    redirect('dashboard.php');
}

$pdo->prepare("DELETE FROM slots WHERE id = ? AND user_id = ?")
    ->execute([$slotId, $userId]);

flashSet('success', 'Créneau supprimé.');
redirect('dashboard.php');
<?php

// ============================================================
// delete_slot.php — Suppression d'un créneau (POST uniquement)
// ============================================================

require_once __DIR__ . '/../config/init.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

verifyCsrf();

$slotId = (int) (isset($_POST['slot_id']) ? $_POST['slot_id'] : 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('dashboard.php');
}

// --- Vérifier que le créneau appartient bien à l'utilisateur connecté ---
$stmt = $pdo->prepare("SELECT id, is_booked FROM slots WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$slotId, $userId]);
$slot = $stmt->fetch();

if (!$slot) {
    // N'appartient pas à cet utilisateur → on redirige sans message d'erreur
    redirect('dashboard.php');
}

if ($slot['is_booked']) {
    // On ne peut pas supprimer un créneau déjà réservé
    redirect('dashboard.php');
}

// --- Suppression ---
$stmtDel = $pdo->prepare("DELETE FROM slots WHERE id = ? AND user_id = ?");
$stmtDel->execute([$slotId, $userId]);

redirect('dashboard.php');
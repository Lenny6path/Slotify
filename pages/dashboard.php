<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
?>

<h1>Bienvenue <?= $_SESSION["user_name"] ?> </h1>

<a href="logout.php">Logout</a>




<?php

require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM slots
    WHERE user_id = ?
    ORDER BY date ASC, start_time ASC
");

$stmt->execute([$_SESSION["user_id"]]);
$slots = $stmt->fetchAll();

$today = date("Y-m-d");
?>

    <h1>Dashboard</h1>

    <a href="create_slot.php">+ Créer un créneau</a>

    <hr>

    <h2>Mes créneaux</h2>

<?php foreach ($slots as $slot): ?>

    <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">

        <strong><?= htmlspecialchars($slot["title"]) ?></strong><br>

        📅 <?= $slot["date"] ?> <br>
        🕒 <?= $slot["start_time"] ?> - <?= $slot["end_time"] ?><br>

        <?php if ($slot["date"] < $today): ?>
            🔴 Expiré
        <?php elseif ($slot["is_booked"]): ?>
            🟠 Réservé
        <?php else: ?>
            🟢 Disponible
        <?php endif; ?>

    </div>

<?php endforeach; ?>
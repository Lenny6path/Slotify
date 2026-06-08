<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $stmt = $pdo->prepare("
        UPDATE slots
        SET is_booked = 1
        WHERE id = ?
    ");

    $stmt->execute([$_POST["slot_id"]]);

    header("Location: slots.php");
    exit;
}
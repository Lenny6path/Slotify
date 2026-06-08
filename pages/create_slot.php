<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $today = date("Y-m-d");

    // ❌ interdit les dates passées
    if ($_POST["date"] < $today) {
        die("❌ Impossible de créer un créneau dans le passé");
    }

    $stmt = $pdo->prepare("
        INSERT INTO slots (user_id, title, date, start_time, end_time, is_booked)
        VALUES (?, ?, ?, ?, ?, 0)
    ");

    $stmt->execute([
            $_SESSION["user_id"],
            $_POST["title"],
            $_POST["date"],
            $_POST["start_time"],
            $_POST["end_time"]
    ]);

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Slot</title>
</head>
<body>

<h2>Créer un créneau</h2>

<form method="POST">

    <input type="text" name="title" placeholder="Titre" required><br><br>

    <label>Date</label><br>
    <input type="date" name="date" required><br><br>

    <label>Heure début</label><br>
    <input type="time" name="start_time" required><br><br>

    <label>Heure fin</label><br>
    <input type="time" name="end_time" required><br><br>

    <button type="submit">Créer</button>

</form>

</body>
</html>
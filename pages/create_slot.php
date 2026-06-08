<?php

require_once __DIR__ . '/../config/db.php';

var_dump(isset($pdo));
exit;

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $stmt = $pdo->prepare("
        INSERT INTO slots (user_id, title, date, start_time, end_time)
        VALUES (?, ?, ?, ?, ?)
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




<form method="POST">
    <h2>Créer un créneau</h2>

    <input name="title" placeholder="Titre du rendez-vous" required>
    <input type="date" name="date" required>
    <input type="time" name="start_time" required>
    <input type="time" name="end_time" required>

    <button type="submit">Créer</button>
</form>




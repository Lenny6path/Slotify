<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM slots
    WHERE is_booked = 0
    AND date >= CURDATE()
    AND user_id != ?
    ORDER BY date ASC
");

$stmt->execute([$_SESSION["user_id"]]);
$slots = $stmt->fetchAll();

?>

    <h1>Slots disponibles</h1>

<?php foreach ($slots as $slot): ?>

    <div style="border:1px solid #ccc; padding:10px; margin:10px;">

        <h3><?= $slot["title"] ?></h3>
        <p><?= $slot["date"] ?> | <?= $slot["start_time"] ?> - <?= $slot["end_time"] ?></p>

        <form method="POST" action="book_slot.php">
            <input type="hidden" name="slot_id" value="<?= $slot["id"] ?>">
            <button type="submit">Réserver</button>
        </form>

    </div>

<?php endforeach; ?>
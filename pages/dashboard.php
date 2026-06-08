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

$stmt = $pdo->prepare("SELECT * FROM slots WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$slots = $stmt->fetchAll();
?>

    <h2>Mes créneaux</h2>

<?php foreach ($slots as $slot): ?>
    <div>
        <p><?= $slot["title"] ?></p>
        <p><?= $slot["date"] ?> | <?= $slot["start_time"] ?> - <?= $slot["end_time"] ?></p>
        <p><?= $slot["is_booked"] ? "Réservé" : "Disponible" ?></p>
    </div>
<?php endforeach; ?>
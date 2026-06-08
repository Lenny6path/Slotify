
<?php

// ============================================================
// config/db.php — Connexion PDO à MySQL
// ============================================================

$host     = "localhost";
$dbname   = "slotify_db";
$username = "root";
$password = "root"; // laisser vide si MAMP sans mot de passe : ""

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En production : ne jamais afficher le message d'erreur brut
    error_log("DB Error: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Vérifie MAMP.");
}

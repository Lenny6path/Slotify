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
            // Les erreurs SQL lèvent des exceptions -> on les attrape
            // proprement au lieu d'avoir des échecs silencieux
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // fetch() renvoie des tableaux associatifs ($row['name'])
            // plutôt que doublés avec des index numériques
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requêtes préparées côté MySQL (et non simulées
            // par PHP) : meilleure protection anti-injection
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En production : ne jamais afficher le message d'erreur brut
    error_log("DB Error: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Vérifie MAMP.");
}
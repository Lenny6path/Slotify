<?php

// ============================================================
// logout.php — Déconnexion propre
// ============================================================

require_once __DIR__ . '/../config/init.php';

// Vider toutes les variables de session
$_SESSION = [];

// session_destroy() ne suffit pas : le cookie PHPSESSID reste
// dans le navigateur. On le fait expirer en lui donnant une
// date dans le passé — c'est la façon standard de "supprimer"
// un cookie côté client.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
redirect('login.php');
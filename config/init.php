
<?php

// ============================================================
// config/init.php — Bootstrap : session + DB + helpers
// ============================================================

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// Helpers globaux
// ------------------------------------------------------------

/**
 * Redirige vers une URL et stoppe l'exécution.
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Vérifie que l'utilisateur est connecté, sinon redirige.
 */
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

/**
 * Échappe une valeur pour l'affichage HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère ou récupère le token CSRF de session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF sur les requêtes POST. Stoppe si invalide.
 */
function verifyCsrf(): void
{
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("❌ Requête invalide (CSRF).");
    }
}

/**
 * Affiche un badge de statut HTML Tailwind.
 */
function statusBadge(array $slot): string
{
    if ($slot['is_booked']) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">● Réservé</span>';
    }
    if ($slot['date'] < date('Y-m-d')) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">● Expiré</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">● Disponible</span>';
}
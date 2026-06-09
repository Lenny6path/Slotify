<?php

// ============================================================
// config/init.php — Bootstrap : session + DB + helpers
// ============================================================

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// Auth
// ------------------------------------------------------------

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------
// CSRF
// ------------------------------------------------------------

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $tokenPost    = $_POST['csrf_token']    ?? '';
    $tokenSession = $_SESSION['csrf_token'] ?? '';

    if (!$tokenPost || !hash_equals($tokenSession, $tokenPost)) {
        http_response_code(403);
        die("Requête invalide.");
    }
}

// ------------------------------------------------------------
// Flash messages
// ------------------------------------------------------------

function flashSet(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flashRender(): void
{
    if (empty($_SESSION['flash'])) return;

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $styles = [
        'success' => 'bg-green-50 border-green-200 text-green-700',
        'error'   => 'bg-red-50 border-red-200 text-red-700',
        'info'    => 'bg-blue-50 border-blue-200 text-blue-700',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
    ];
    $icons = [
        'success' => '✅',
        'error'   => '❌',
        'info'    => 'ℹ️',
        'warning' => '⚠️',
    ];

    $cls  = $styles[$flash['type']] ?? $styles['info'];
    $icon = $icons[$flash['type']]  ?? 'ℹ️';

    echo '<div id="flash-msg" class="mb-6 px-4 py-3 border rounded-lg text-sm flex items-center gap-2 ' . $cls . '">';
    echo '<span>' . $icon . '</span>';
    echo '<span>' . e($flash['message']) . '</span>';
    echo '</div>';
    echo '<script>setTimeout(()=>{const f=document.getElementById("flash-msg");if(f){f.style.transition="opacity .5s";f.style.opacity=0;setTimeout(()=>f.remove(),500);}},3500);</script>';
}

// ------------------------------------------------------------
// Badge statut créneau
// ------------------------------------------------------------

function statusBadge(array $slot): string
{
    if ($slot['is_booked']) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">● Réservé</span>';
    }
    if ($slot['date'] < date('Y-m-d')) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">● Expiré</span>';
    }
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">● Disponible</span>';
}

// ------------------------------------------------------------
// Initiales avatar
// ------------------------------------------------------------

function avatar(string $name): string
{
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
    return $initials;
}
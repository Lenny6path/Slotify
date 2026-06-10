<?php

// ============================================================
// config/init.php — Le fichier de démarrage de l'application
//
// Chaque page commence par require ce fichier. Il s'occupe de :
//   1. ouvrir la connexion à la base (via db.php)
//   2. démarrer la session PHP
//   3. fournir toutes les petites fonctions utilitaires
//      qu'on utilise partout dans le site
// ============================================================

require_once __DIR__ . '/db.php';

// On ne démarre la session que si elle ne l'est pas déjà,
// sinon PHP balance un warning "session already started"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------
// Navigation & authentification
// ------------------------------------------------------------

// Redirige vers une page et coupe net l'exécution du script.
// Le exit est OBLIGATOIRE : sans lui, le code qui suit le
// header() continuerait de s'exécuter (faille de sécu classique).
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

// À appeler en haut de chaque page réservée aux connectés.
// Pas de user_id en session = retour à la case login.
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

// Échappe une chaîne avant de l'afficher en HTML.
// C'est NOTRE protection contre les failles XSS : tout ce qui
// vient de l'utilisateur (nom, titre, bio...) passe par là.
// Le nom court "e" c'est pour pouvoir écrire e($var) partout
// sans alourdir le code.
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------
// Protection CSRF
//
// Le principe : chaque formulaire embarque un jeton secret
// stocké en session. Au moment du POST on compare les deux.
// Un site malveillant ne peut pas connaître ce jeton, donc il
// ne peut pas soumettre de formulaire à notre place.
// ------------------------------------------------------------

// Génère le jeton une seule fois par session, puis le réutilise.
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 octets aléatoires -> 64 caractères hexadécimaux
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifie le jeton reçu en POST. hash_equals() compare les deux
// chaînes en temps constant (évite les attaques par timing).
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
// Messages flash
//
// Un "flash" c'est un message affiché UNE seule fois après une
// redirection (ex: "Créneau créé !"). On le stocke en session,
// on l'affiche sur la page suivante, puis on le supprime.
// ------------------------------------------------------------

// Enregistre le message. Types possibles : success / error / info / warning
function flashSet(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Affiche le message s'il y en a un, puis le supprime aussitôt
// (c'est ça qui garantit qu'il ne s'affiche qu'une fois).
// Appelée automatiquement par layoutHeader().
function flashRender(): void
{
    if (empty($_SESSION['flash'])) return;

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    // Couleurs et icônes selon le type de message
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
    // Petit bonus UX : le message disparaît tout seul après 3,5s
    echo '<script>setTimeout(()=>{const f=document.getElementById("flash-msg");if(f){f.style.transition="opacity .5s";f.style.opacity=0;setTimeout(()=>f.remove(),500);}},3500);</script>';
}

// ------------------------------------------------------------
// Badges d'affichage (petites pastilles colorées)
// ------------------------------------------------------------

// Statut d'un créneau : Réservé (rouge) / Expiré (gris) / Disponible (vert).
// L'ordre des if compte : un créneau réservé reste "Réservé"
// même si sa date est passée.
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

// Type de rendez-vous : Visio (violet) ou Présentiel (orange).
// Le ?? 'presentiel' couvre les vieux créneaux créés avant
// l'ajout de la colonne type.
function typeBadge(array $slot): string
{
    if (($slot['type'] ?? 'presentiel') === 'visio') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">🎥 Visio</span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">📍 Présentiel</span>';
}

// ------------------------------------------------------------
// Formatage
// ------------------------------------------------------------

// Affiche un prix proprement : "25,50 €" à la française,
// ou "Gratuit" en vert si pas de prix renseigné.
// Pas de type hint sur $price car il peut arriver en string
// (depuis MySQL), en float, ou en null.
function formatPrice($price): string
{
    if ($price === null || $price === '' || (float)$price == 0.0) {
        return '<span class="text-green-600 font-semibold">Gratuit</span>';
    }
    return '<span class="font-semibold text-gray-900">' . number_format((float)$price, 2, ',', ' ') . ' €</span>';
}

// Transforme une adresse texte en lien Google Maps cliquable.
// urlencode() gère les espaces, accents et caractères spéciaux.
// Astuce : ça nous donne la géolocalisation sans payer d'API !
function mapsLink(string $address): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
}

// Calcule les initiales d'un nom pour l'avatar rond.
// "Jean Dupont" -> "JD", "Marie" -> "M"
function avatar(string $name): string
{
    $parts = explode(' ', trim($name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
    return $initials;
}

// ------------------------------------------------------------
// Plans Free / Pro (la partie "monétisation")
//
// Modèle freemium classique : le plan gratuit est limité en
// nombre de créneaux actifs, le plan Pro est illimité.
// Le passage en Pro est simulé (pas de vrai paiement ici).
// ------------------------------------------------------------

// La fameuse limite du plan gratuit. En constante pour ne
// la modifier qu'à UN seul endroit si on change d'avis.
const FREE_PLAN_SLOT_LIMIT = 10;

// Renvoie 'free' ou 'pro' pour l'utilisateur connecté.
// On met le résultat en cache dans la session : sans ça,
// chaque page ferait une requête SQL juste pour la navbar.
// Le cache est vidé au login et lors d'un changement de plan.
function getUserPlan(PDO $pdo): string
{
    if (empty($_SESSION['user_id'])) {
        return 'free';
    }
    if (!isset($_SESSION['user_plan'])) {
        $stmt = $pdo->prepare("SELECT plan FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['user_plan'] = $stmt->fetchColumn() ?: 'free';
    }
    return $_SESSION['user_plan'];
}

// Raccourci pratique pour les conditions
function isPro(PDO $pdo): bool
{
    return getUserPlan($pdo) === 'pro';
}

// Compte les créneaux "actifs" = dont la date n'est pas passée.
// Les créneaux expirés ne comptent pas dans le quota : ce serait
// injuste de bloquer quelqu'un à cause de vieux créneaux.
function countActiveSlots(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND date >= CURDATE()");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// Le cœur de la logique freemium. Renvoie un tableau de 3 valeurs :
//   [0] peut-il créer un créneau ? (bool)
//   [1] combien en utilise-t-il ?  (int)
//   [2] quelle est sa limite ?     (int, ou null = illimité pour les Pro)
// Usage : list($canCreate, $used, $limit) = canCreateSlot($pdo, $userId);
function canCreateSlot(PDO $pdo, int $userId): array
{
    if (isPro($pdo)) {
        return [true, countActiveSlots($pdo, $userId), null];
    }
    $used = countActiveSlots($pdo, $userId);
    return [$used < FREE_PLAN_SLOT_LIMIT, $used, FREE_PLAN_SLOT_LIMIT];
}

// Pastille "⭐ PRO" dorée ou "Free" grise, affichée
// dans le dashboard et la page profil.
function planBadge(PDO $pdo): string
{
    if (isPro($pdo)) {
        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold bg-gradient-to-r from-amber-400 to-yellow-500 text-white">⭐ PRO</span>';
    }
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-500">Free</span>';
}
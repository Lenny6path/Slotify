<?php

// ============================================================
// history.php — Historique complet des réservations
//
// Cette page regroupe deux choses différentes :
//   - les RDV "reçus"    : mes créneaux que des clients ont réservés
//   - les RDV "effectués": les créneaux que J'AI réservés chez d'autres pros
// On les affiche ensemble avec un système de filtres.
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// Filtre actif, passé dans l'URL : all / received / made
$filter = $_GET['filter'] ?? 'all';

// --- RDV reçus : mes créneaux réservés par quelqu'un ---
// Le JOIN sur booked_by nous donne le nom du client.
// ATTENTION : on nomme l'alias "direction" et surtout PAS "type",
// car s.* contient déjà une colonne type (presentiel/visio).
// Si on l'appelait "type", il écraserait la vraie valeur et on
// perdrait l'info visio/présentiel. (bug vécu, plus jamais ça)
$stmtReceived = $pdo->prepare("
    SELECT s.*,
           u.name AS client_name,
           'received' AS direction
    FROM slots s
    JOIN users u ON u.id = s.booked_by
    WHERE s.user_id = ? AND s.is_booked = 1
    ORDER BY s.date DESC, s.start_time DESC
");
$stmtReceived->execute([$userId]);
$received = $stmtReceived->fetchAll();

// --- RDV effectués : ceux que j'ai réservés chez les autres ---
// Ici le JOIN est sur user_id pour récupérer le nom du pro.
$stmtMade = $pdo->prepare("
    SELECT s.*,
           u.name AS pro_name,
           'made' AS direction
    FROM slots s
    JOIN users u ON u.id = s.user_id
    WHERE s.booked_by = ? AND s.user_id != ?
    ORDER BY s.date DESC, s.start_time DESC
");
$stmtMade->execute([$userId, $userId]);
$made = $stmtMade->fetchAll();

// --- Application du filtre ---
// Pour "tous", on fusionne les deux listes puis on retrie par
// date+heure décroissante (le plus récent en premier).
if ($filter === 'received') {
    $all = $received;
} elseif ($filter === 'made') {
    $all = $made;
} else {
    $all = array_merge($received, $made);
    usort($all, function ($a, $b) {
        // Concaténer date + heure donne une chaîne triable directement
        return strcmp($b['date'] . $b['start_time'], $a['date'] . $a['start_time']);
    });
}

layoutHeader("Historique");
?>

    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Historique des réservations</h1>
            <p class="text-gray-400 text-sm mt-1">Tous vos rendez-vous passés et à venir.</p>
        </div>

        <!-- Boutons de filtre : le filtre actif est en bleu plein -->
        <div class="flex gap-2 text-sm">
            <a href="?filter=all"
               class="px-4 py-2 rounded-lg transition font-medium <?= $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' ?>">
                Tous (<?= count($received) + count($made) ?>)
            </a>
            <a href="?filter=received"
               class="px-4 py-2 rounded-lg transition font-medium <?= $filter === 'received' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' ?>">
                Reçus (<?= count($received) ?>)
            </a>
            <a href="?filter=made"
               class="px-4 py-2 rounded-lg transition font-medium <?= $filter === 'made' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' ?>">
                Effectués (<?= count($made) ?>)
            </a>
        </div>
    </div>

<?php if (empty($all)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
        <p class="text-5xl mb-4">📭</p>
        <p class="font-semibold text-lg">Aucune réservation pour l'instant.</p>
    </div>

<?php else: ?>

    <div class="space-y-3">
        <?php foreach ($all as $item):
            // Deux infos qui pilotent tout l'affichage de la card :
            $isPast     = $item['date'] < date('Y-m-d');       // RDV déjà passé ?
            $isReceived = $item['direction'] === 'received';   // reçu ou effectué ?
            ?>

            <!-- Les RDV passés sont grisés (opacity) pour les distinguer -->
            <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center justify-between gap-4 hover:shadow-sm transition <?= $isPast ? 'opacity-60' : '' ?>">

                <div class="flex items-center gap-4">
                    <!-- Pastille : 📥 = RDV reçu / 📤 = RDV effectué -->
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg flex-shrink-0
                    <?= $isReceived ? 'bg-green-100' : 'bg-blue-100' ?>">
                        <?= $isReceived ? '📥' : '📤' ?>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-gray-900"><?= e($item['title']) ?></p>
                            <?php if ($isPast): ?>
                                <span class="text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full">Passé</span>
                            <?php else: ?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">À venir</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-400 mt-0.5">
                            <?= date('d/m/Y', strtotime($item['date'])) ?>
                            &nbsp;·&nbsp;
                            <?= substr($item['start_time'],0,5) ?> – <?= substr($item['end_time'],0,5) ?>
                            &nbsp;·&nbsp;
                            <?php if ($isReceived): ?>
                                <span class="text-gray-500">Client : <?= e($item['client_name']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-500">Chez : <?= e($item['pro_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['price']) && (float)$item['price'] > 0): ?>
                                &nbsp;·&nbsp; <span class="font-medium text-gray-600"><?= number_format((float)$item['price'], 2, ',', ' ') ?> €</span>
                            <?php endif; ?>
                        </p>

                        <!-- Accès au lieu / à la visio, uniquement pour les RDV à venir.
                             C'est ici que le client récupère le lien Zoom/Meet : il n'est
                             visible qu'APRÈS réservation, jamais sur la page publique. -->
                        <?php if (!$isPast): ?>
                            <?php if (($item['type'] ?? 'presentiel') === 'visio' && !empty($item['meeting_link'])): ?>
                                <a href="<?= e($item['meeting_link']) ?>" target="_blank"
                                   class="inline-flex items-center gap-1.5 mt-2 text-xs bg-purple-50 text-purple-600 hover:bg-purple-100 border border-purple-200 px-3 py-1.5 rounded-lg transition font-medium">
                                    🎥 Rejoindre la visio
                                </a>
                            <?php elseif (!empty($item['location'])): ?>
                                <a href="<?= e(mapsLink($item['location'])) ?>" target="_blank"
                                   class="inline-flex items-center gap-1.5 mt-2 text-xs bg-orange-50 text-orange-600 hover:bg-orange-100 border border-orange-200 px-3 py-1.5 rounded-lg transition font-medium">
                                    📍 <?= e($item['location']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- On ne peut annuler QUE ses propres réservations futures.
                     Le délai des 24h est vérifié côté serveur dans cancel_booking.php -->
                <?php if (!$isPast && !$isReceived): ?>
                    <form method="POST" action="cancel_booking.php"
                          onsubmit="return confirm('Annuler cette réservation ?')">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="slot_id"    value="<?= $item['id'] ?>">
                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-200 hover:border-red-400 px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                            Annuler
                        </button>
                    </form>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php layoutFooter(); ?>
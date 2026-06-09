<?php

// ============================================================
// history.php — Historique complet des réservations
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// Filtre
$filter = $_GET['filter'] ?? 'all'; // all | mine | booked

// Rendez-vous reçus (créneaux du pro réservés par des clients)
$stmtReceived = $pdo->prepare("
    SELECT s.*,
           u.name  AS client_name,
           'received' AS type
    FROM slots s
    JOIN users u ON u.id = s.booked_by
    WHERE s.user_id = ? AND s.is_booked = 1
    ORDER BY s.date DESC, s.start_time DESC
");
$stmtReceived->execute([$userId]);
$received = $stmtReceived->fetchAll();

// Réservations faites par l'utilisateur (chez d'autres pros)
$stmtMade = $pdo->prepare("
    SELECT s.*,
           u.name  AS pro_name,
           'made' AS type
    FROM slots s
    JOIN users u ON u.id = s.user_id
    WHERE s.booked_by = ? AND s.user_id != ?
    ORDER BY s.date DESC, s.start_time DESC
");
$stmtMade->execute([$userId, $userId]);
$made = $stmtMade->fetchAll();

// Fusionner selon filtre
if ($filter === 'received') {
    $all = $received;
} elseif ($filter === 'made') {
    $all = $made;
} else {
    $all = array_merge($received, $made);
    // Trier par date décroissante
    usort($all, function($a, $b) {
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

        <!-- Filtres -->
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
            $isPast    = $item['date'] < date('Y-m-d');
            $isReceived = $item['type'] === 'received';
            ?>

            <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center justify-between gap-4 hover:shadow-sm transition <?= $isPast ? 'opacity-60' : '' ?>">

                <div class="flex items-center gap-4">
                    <!-- Icône type -->
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
                        </p>
                    </div>
                </div>

                <!-- Action : annuler si futur et réservation faite par moi -->
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
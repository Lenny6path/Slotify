<?php

// ============================================================
// dashboard.php — Tableau de bord du professionnel
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// --- Stats globales ---
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$totalSlots = (int) $stmtTotal->fetchColumn();

$stmtBooked = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND is_booked = 1");
$stmtBooked->execute([$userId]);
$bookedSlots = (int) $stmtBooked->fetchColumn();

$stmtDispo = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND is_booked = 0 AND date >= CURDATE()");
$stmtDispo->execute([$userId]);
$availableSlots = (int) $stmtDispo->fetchColumn();

// Taux de remplissage du mois en cours
$stmtMonthTotal = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$stmtMonthTotal->execute([$userId]);
$monthTotal = (int) $stmtMonthTotal->fetchColumn();

$stmtMonthBooked = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND is_booked = 1 AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$stmtMonthBooked->execute([$userId]);
$monthBooked = (int) $stmtMonthBooked->fetchColumn();

$fillRate = $monthTotal > 0 ? round(($monthBooked / $monthTotal) * 100) : 0;

// --- Prochain RDV réservé ---
$stmtNext = $pdo->prepare("
    SELECT s.*, u.name AS client_name
    FROM slots s
    LEFT JOIN users u ON u.id = s.booked_by
    WHERE s.user_id = ? AND s.is_booked = 1 AND s.date >= CURDATE()
    ORDER BY s.date ASC, s.start_time ASC
    LIMIT 1
");
$stmtNext->execute([$userId]);
$nextBooking = $stmtNext->fetch();

// --- Liste créneaux (10 prochains) ---
$stmtSlots = $pdo->prepare("
    SELECT s.*, u.name AS booked_by_name
    FROM slots s
    LEFT JOIN users u ON u.id = s.booked_by
    WHERE s.user_id = ?
    ORDER BY s.date ASC, s.start_time ASC
    LIMIT 10
");
$stmtSlots->execute([$userId]);
$slots = $stmtSlots->fetchAll();

layoutHeader("Dashboard");
?>

    <!-- En-tête -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bonjour, <?= e($_SESSION['user_name']) ?> 👋</h1>
            <p class="text-gray-400 text-sm mt-1"><?= date('l d F Y') ?></p>
        </div>
        <a href="profil_public.php?id=<?= $userId ?>" target="_blank"
           class="text-xs text-blue-600 border border-blue-200 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
            🔗 Mon lien public
        </a>
    </div>

    <!-- Prochain RDV mis en avant -->
<?php if ($nextBooking): ?>
    <div class="bg-blue-600 text-white rounded-2xl p-5 mb-6 flex items-center justify-between">
        <div>
            <p class="text-xs text-blue-200 font-semibold uppercase tracking-wide mb-1">Prochain rendez-vous</p>
            <p class="text-xl font-bold"><?= e($nextBooking['title']) ?></p>
            <p class="text-blue-100 text-sm mt-1">
                <?= date('d/m/Y', strtotime($nextBooking['date'])) ?>
                &nbsp;·&nbsp;
                <?= substr($nextBooking['start_time'], 0, 5) ?> – <?= substr($nextBooking['end_time'], 0, 5) ?>
                &nbsp;·&nbsp;
                avec <?= e($nextBooking['client_name']) ?>
            </p>
        </div>
        <div class="text-5xl opacity-30">📅</div>
    </div>
<?php endif; ?>

    <!-- Cartes stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total créneaux</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?= $totalSlots ?></p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Réservés</p>
            <p class="text-3xl font-bold text-blue-600 mt-1"><?= $bookedSlots ?></p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Disponibles</p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?= $availableSlots ?></p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Taux ce mois</p>
            <p class="text-3xl font-bold mt-1 <?= $fillRate >= 75 ? 'text-green-600' : ($fillRate >= 40 ? 'text-yellow-500' : 'text-gray-400') ?>">
                <?= $fillRate ?>%
            </p>
            <div class="mt-2 bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full transition-all" style="width: <?= $fillRate ?>%"></div>
            </div>
        </div>

    </div>

    <!-- Tableau créneaux -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Mes créneaux</h2>
        <a href="create_slot.php"
           class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            + Nouveau créneau
        </a>
    </div>

<?php if (empty($slots)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
        <p class="text-5xl mb-4">📅</p>
        <p class="font-semibold text-lg">Aucun créneau créé pour l'instant.</p>
        <a href="create_slot.php" class="mt-3 inline-block text-blue-600 hover:underline text-sm">Créer votre premier créneau →</a>
    </div>
<?php else: ?>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3 font-medium">Titre</th>
                <th class="text-left px-5 py-3 font-medium">Date</th>
                <th class="text-left px-5 py-3 font-medium">Horaire</th>
                <th class="text-left px-5 py-3 font-medium">Statut</th>
                <th class="text-left px-5 py-3 font-medium">Client</th>
                <th class="px-5 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($slots as $slot): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3.5 font-medium text-gray-900"><?= e($slot['title']) ?></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= date('d/m/Y', strtotime($slot['date'])) ?></td>
                    <td class="px-5 py-3.5 text-gray-500"><?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?></td>
                    <td class="px-5 py-3.5"><?= statusBadge($slot) ?></td>
                    <td class="px-5 py-3.5 text-gray-400"><?= $slot['booked_by_name'] ? e($slot['booked_by_name']) : '—' ?></td>
                    <td class="px-5 py-3.5 text-right">
                        <?php if (!$slot['is_booked']): ?>
                            <form method="POST" action="delete_slot.php"
                                  onsubmit="return confirm('Supprimer ce créneau ?')">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="slot_id"   value="<?= $slot['id'] ?>">
                                <button type="submit" class="text-xs text-red-400 hover:text-red-600 hover:underline transition">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-right">
        <a href="slots.php" class="text-sm text-blue-600 hover:underline">Voir tous les créneaux →</a>
    </div>

<?php endif; ?>

<?php layoutFooter(); ?>
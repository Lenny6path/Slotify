<?php

// ============================================================
// dashboard.php — Tableau de bord du professionnel
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// --- Stats ---
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$totalSlots = (int) $stmtTotal->fetchColumn();

$stmtBooked = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND is_booked = 1");
$stmtBooked->execute([$userId]);
$bookedSlots = (int) $stmtBooked->fetchColumn();

$stmtDispo = $pdo->prepare("SELECT COUNT(*) FROM slots WHERE user_id = ? AND is_booked = 0 AND date >= CURDATE()");
$stmtDispo->execute([$userId]);
$availableSlots = (int) $stmtDispo->fetchColumn();

// --- Prochains créneaux (5 max) ---
$stmtSlots = $pdo->prepare("
    SELECT s.*, u.name AS booked_by_name
    FROM slots s
    LEFT JOIN users u ON u.id = s.booked_by
    WHERE s.user_id = ?
    ORDER BY s.date ASC, s.start_time ASC
    LIMIT 5
");
$stmtSlots->execute([$userId]);
$slots = $stmtSlots->fetchAll();

layoutHeader("Dashboard");
?>

    <!-- En-tête -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">
            Bonjour, <?= e($_SESSION['user_name']) ?> 👋
        </h1>
        <p class="text-gray-500 text-sm mt-1">Voici un aperçu de votre planning.</p>
    </div>

    <!-- Cartes stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total créneaux</p>
            <p class="text-3xl font-bold text-gray-900 mt-1"><?= $totalSlots ?></p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Réservés</p>
            <p class="text-3xl font-bold text-blue-600 mt-1"><?= $bookedSlots ?></p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Disponibles</p>
            <p class="text-3xl font-bold text-green-600 mt-1"><?= $availableSlots ?></p>
        </div>

    </div>

    <!-- Section créneaux + actions -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Prochains créneaux</h2>
        <a
                href="create_slot.php"
                class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition"
        >
            + Nouveau créneau
        </a>
    </div>

<?php if (empty($slots)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">📅</p>
        <p class="font-medium">Aucun créneau créé pour l'instant.</p>
        <a href="create_slot.php" class="mt-3 inline-block text-blue-600 hover:underline text-sm">
            Créer votre premier créneau →
        </a>
    </div>
<?php else: ?>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-xs uppercase tracking-wide">
                <th class="text-left px-5 py-3 font-medium">Titre</th>
                <th class="text-left px-5 py-3 font-medium">Date</th>
                <th class="text-left px-5 py-3 font-medium">Horaire</th>
                <th class="text-left px-5 py-3 font-medium">Statut</th>
                <th class="text-left px-5 py-3 font-medium">Réservé par</th>
                <th class="px-5 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($slots as $slot): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3.5 font-medium text-gray-900"><?= e($slot['title']) ?></td>
                    <td class="px-5 py-3.5 text-gray-600">
                        <?= date('d/m/Y', strtotime($slot['date'])) ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-600">
                        <?= substr($slot['start_time'], 0, 5) ?> – <?= substr($slot['end_time'], 0, 5) ?>
                    </td>
                    <td class="px-5 py-3.5">
                        <?= statusBadge($slot) ?>
                    </td>
                    <td class="px-5 py-3.5 text-gray-500">
                        <?= $slot['booked_by_name'] ? e($slot['booked_by_name']) : '—' ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <?php if (!$slot['is_booked']): ?>
                            <form method="POST" action="delete_slot.php"
                                  onsubmit="return confirm('Supprimer ce créneau ?')">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                                <button
                                        type="submit"
                                        class="text-xs text-red-500 hover:text-red-700 hover:underline transition"
                                >
                                    Supprimer
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-right">
        <a href="slots.php" class="text-sm text-blue-600 hover:underline">
            Voir tous les créneaux →
        </a>
    </div>

<?php endif; ?>

<?php layoutFooter(); ?>
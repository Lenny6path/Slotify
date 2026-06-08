<?php

// ============================================================
// slots.php — Vue publique : réserver un créneau
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// Filtre optionnel par date
$filterDate = trim(isset($_GET['date']) ? $_GET['date'] : '');

// --- Récupération des créneaux futurs (tous les pros sauf soi-même si on veut) ---
$query = "
    SELECT s.*, u.name AS owner_name
    FROM slots s
    JOIN users u ON u.id = s.user_id
    WHERE s.date >= CURDATE()
";
$params = [];

if ($filterDate !== '') {
    $query .= " AND s.date = ?";
    $params[] = $filterDate;
}

$query .= " ORDER BY s.date ASC, s.start_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$slots = $stmt->fetchAll();

layoutHeader("Créneaux disponibles");
?>

    <!-- En-tête -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Créneaux disponibles</h1>
            <p class="text-gray-500 text-sm mt-1">Réservez un créneau auprès d'un professionnel.</p>
        </div>

        <!-- Filtre date -->
        <form method="GET" class="flex items-center gap-2">
            <input
                    type="date" name="date"
                    value="<?= e($filterDate) ?>"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
            >
            <button
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition"
            >
                Filtrer
            </button>
            <?php if ($filterDate !== ''): ?>
                <a href="slots.php" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 transition">✕</a>
            <?php endif; ?>
        </form>
    </div>

<?php if (empty($slots)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">🔍</p>
        <p class="font-medium">Aucun créneau disponible pour le moment.</p>
    </div>
<?php else: ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($slots as $slot):
            $isMine    = ($slot['user_id'] == $userId);
            $isBooked  = (bool) $slot['is_booked'];
            $isExpired = ($slot['date'] < date('Y-m-d'));
            ?>

            <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3 hover:shadow-sm transition">

                <!-- Header -->
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-gray-900 text-base leading-tight">
                        <?= e($slot['title']) ?>
                    </h3>
                    <?= statusBadge($slot) ?>
                </div>

                <!-- Infos -->
                <div class="text-sm text-gray-500 space-y-1">
                    <p>📅 <?= date('d/m/Y', strtotime($slot['date'])) ?></p>
                    <p>🕐 <?= substr($slot['start_time'], 0, 5) ?> – <?= substr($slot['end_time'], 0, 5) ?></p>
                    <p>👤 <?= e($slot['owner_name']) ?></p>
                </div>

                <!-- Action -->
                <div class="mt-auto pt-2 border-t border-gray-100">
                    <?php if ($isBooked): ?>
                        <p class="text-sm text-gray-400 italic">Créneau déjà réservé</p>

                    <?php elseif ($isExpired): ?>
                        <p class="text-sm text-gray-400 italic">Créneau expiré</p>

                    <?php elseif ($isMine): ?>
                        <p class="text-sm text-gray-400 italic">Votre propre créneau</p>

                    <?php else: ?>
                        <form method="POST" action="book_slot.php">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>">
                            <button
                                    type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition"
                            >
                                Réserver ce créneau
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php layoutFooter(); ?>
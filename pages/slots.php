<?php

// ============================================================
// slots.php — Vue des créneaux disponibles
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId     = $_SESSION['user_id'];
$filterDate = trim($_GET['date'] ?? '');

$query  = "
    SELECT s.*, u.name AS owner_name, u.id AS owner_id
    FROM slots s
    JOIN users u ON u.id = s.user_id
    WHERE s.date >= CURDATE()
";
$params = [];

if ($filterDate !== '') {
    $query  .= " AND s.date = ?";
    $params[] = $filterDate;
}

$query .= " ORDER BY s.date ASC, s.start_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$slots = $stmt->fetchAll();

layoutHeader("Créneaux disponibles");
?>

    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Créneaux disponibles</h1>
            <p class="text-gray-400 text-sm mt-1">Réservez un créneau auprès d'un professionnel.</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="<?= e($filterDate) ?>"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                Filtrer
            </button>
            <?php if ($filterDate !== ''): ?>
                <a href="slots.php" class="px-3 py-2 text-sm text-gray-400 hover:text-gray-600 transition">✕</a>
            <?php endif; ?>
        </form>
    </div>

<?php if (empty($slots)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
        <p class="text-5xl mb-3">🔍</p>
        <p class="font-semibold text-lg">Aucun créneau disponible pour le moment.</p>
    </div>

<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($slots as $slot):
            $isMine   = ($slot['user_id'] == $userId);
            $isBooked = (bool) $slot['is_booked'];
            ?>
            <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col gap-3 hover:shadow-sm transition">

                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-gray-900"><?= e($slot['title']) ?></h3>
                    <?= statusBadge($slot) ?>
                </div>

                <div class="text-sm text-gray-400 space-y-1">
                    <p>📅 <?= date('d/m/Y', strtotime($slot['date'])) ?></p>
                    <p>🕐 <?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?></p>
                    <p>👤 <a href="profil_public.php?id=<?= $slot['owner_id'] ?>" class="text-blue-500 hover:underline"><?= e($slot['owner_name']) ?></a></p>
                </div>

                <div class="mt-auto pt-3 border-t border-gray-100">
                    <?php if ($isBooked): ?>
                        <p class="text-sm text-gray-300 italic">Déjà réservé</p>
                    <?php elseif ($isMine): ?>
                        <p class="text-sm text-gray-300 italic">Votre créneau</p>
                    <?php else: ?>
                        <a href="confirm_booking.php?slot_id=<?= $slot['id'] ?>"
                           class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-lg transition">
                            Réserver →
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php layoutFooter(); ?>
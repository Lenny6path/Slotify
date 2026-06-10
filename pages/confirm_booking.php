<?php

// ============================================================
// confirm_booking.php — L'étape "Vérifiez avant de valider"
//
// Page intermédiaire entre le clic "Réserver" et la vraie
// réservation. Elle récapitule tout (pro, prestation, date,
// prix, lieu) pour éviter les réservations par erreur.
// Le vrai traitement se fait ensuite dans book_slot.php.
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$slotId = (int) ($_GET['slot_id'] ?? 0);
$userId = $_SESSION['user_id'];

if ($slotId <= 0) {
    redirect('slots.php');
}

// Récupérer le créneau avec les infos du pro
$stmt = $pdo->prepare("
    SELECT s.*, u.name AS pro_name, u.bio AS pro_bio
    FROM slots s
    JOIN users u ON u.id = s.user_id
    WHERE s.id = ? LIMIT 1
");
$stmt->execute([$slotId]);
$slot = $stmt->fetch();

if (!$slot) {
    flashSet('error', 'Créneau introuvable.');
    redirect('slots.php');
}

if ($slot['is_booked']) {
    flashSet('error', 'Ce créneau a déjà été réservé.');
    redirect('slots.php');
}

if ($slot['date'] < date('Y-m-d')) {
    flashSet('error', 'Ce créneau est expiré.');
    redirect('slots.php');
}

if ($slot['user_id'] == $userId) {
    flashSet('error', 'Vous ne pouvez pas réserver votre propre créneau.');
    redirect('slots.php');
}

// Mise en forme pour l'affichage
$dateFormatted = date('l d F Y', strtotime($slot['date']));
$heureDebut    = substr($slot['start_time'], 0, 5);
$heureFin      = substr($slot['end_time'],   0, 5);
// Durée en minutes : différence des deux timestamps / 60
$dureeMin      = (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 60;

layoutHeader("Confirmer la réservation");
?>

    <div class="max-w-lg mx-auto">

        <div class="mb-6">
            <a href="javascript:history.back()" class="text-sm text-gray-400 hover:text-blue-600 transition">← Retour</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Confirmer la réservation</h1>
            <p class="text-gray-400 text-sm mt-1">Vérifiez les détails avant de confirmer.</p>
        </div>

        <!-- Card récapitulatif -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">

            <div class="flex items-center gap-3 pb-5 mb-5 border-b border-gray-100">
                <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-lg flex-shrink-0">
                    <?= avatar($slot['pro_name']) ?>
                </div>
                <div>
                    <p class="font-semibold text-gray-800"><?= e($slot['pro_name']) ?></p>
                    <?php if (!empty($slot['pro_bio'])): ?>
                        <p class="text-xs text-gray-400"><?= e($slot['pro_bio']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">📋</span>
                    <div>
                        <p class="text-xs text-gray-400">Prestation</p>
                        <p class="font-semibold text-gray-800"><?= e($slot['title']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xl">📅</span>
                    <div>
                        <p class="text-xs text-gray-400">Date</p>
                        <p class="font-semibold text-gray-800"><?= $dateFormatted ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xl">🕐</span>
                    <div>
                        <p class="text-xs text-gray-400">Horaire</p>
                        <p class="font-semibold text-gray-800"><?= $heureDebut ?> – <?= $heureFin ?> <span class="text-gray-400 font-normal text-sm">(<?= $dureeMin ?> min)</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xl">💰</span>
                    <div>
                        <p class="text-xs text-gray-400">Tarif</p>
                        <p class="font-semibold text-gray-800"><?= formatPrice($slot['price'] ?? null) ?></p>
                        <?php if (!empty($slot['price']) && (float)$slot['price'] > 0): ?>
                            <p class="text-xs text-gray-400">À régler directement auprès du professionnel.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xl"><?= ($slot['type'] ?? 'presentiel') === 'visio' ? '🎥' : '📍' ?></span>
                    <div>
                        <p class="text-xs text-gray-400">Lieu</p>
                        <?php if (($slot['type'] ?? 'presentiel') === 'visio'): ?>
                            <p class="font-semibold text-gray-800">En visioconférence</p>
                            <p class="text-xs text-gray-400">Le lien vous sera communiqué après confirmation.</p>
                        <?php else: ?>
                            <p class="font-semibold text-gray-800"><?= e($slot['location'] ?? 'Adresse non précisée') ?></p>
                            <?php if (!empty($slot['location'])): ?>
                                <a href="<?= e(mapsLink($slot['location'])) ?>" target="_blank" class="text-xs text-blue-500 hover:underline">Voir sur Google Maps →</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Boutons -->
        <form method="POST" action="book_slot.php">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="slot_id"    value="<?= $slot['id'] ?>">

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-xl transition text-base mb-3">
                ✅ Confirmer la réservation
            </button>
        </form>

        <a href="javascript:history.back()"
           class="block w-full text-center border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium py-3 rounded-xl transition text-sm">
            Annuler
        </a>

    </div>

<?php layoutFooter(); ?>
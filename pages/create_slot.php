<?php

// ============================================================
// create_slot.php — Création d'un créneau
//
// Le formulaire le plus complet du site : titre, date, horaires,
// prix optionnel, et le choix présentiel/visio qui affiche des
// champs différents selon le type (un peu de JS pour ça en bas).
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$errors = [];

// --- Vérification de la limite du plan ---
list($canCreate, $usedSlots, $slotLimit) = canCreateSlot($pdo, $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    // Re-vérification de la limite AU MOMENT du POST. Cacher le
    // formulaire ne suffit pas : quelqu'un pourrait renvoyer la
    // requête à la main (curl, Postman...). La vraie barrière de
    // sécurité est toujours côté serveur.
    if (!$canCreate) {
        flashSet('warning', 'Limite de ' . FREE_PLAN_SLOT_LIMIT . ' créneaux atteinte. Passez Pro pour continuer.');
        redirect('upgrade.php');
    }

    $title        = trim($_POST['title']        ?? '');
    $date         = trim($_POST['date']         ?? '');
    $start_time   = trim($_POST['start_time']   ?? '');
    $end_time     = trim($_POST['end_time']     ?? '');
    $priceRaw     = trim($_POST['price']        ?? '');
    $type         = $_POST['type']              ?? 'presentiel';
    $location     = trim($_POST['location']     ?? '');
    $meeting_link = trim($_POST['meeting_link'] ?? '');

    // --- Validation de base ---
    if ($title === '') {
        $errors[] = "Le titre est obligatoire.";
    }
    if ($date === '') {
        $errors[] = "La date est obligatoire.";
    } elseif ($date < date('Y-m-d')) {
        $errors[] = "Impossible de créer un créneau dans le passé.";
    }
    if ($start_time === '' || $end_time === '') {
        $errors[] = "Les heures de début et de fin sont obligatoires.";
    } elseif ($start_time >= $end_time) {
        $errors[] = "L'heure de fin doit être après l'heure de début.";
    }

    // --- Validation prix ---
    // Le prix est optionnel : vide = créneau gratuit (price = NULL en base)
    $price = null;
    if ($priceRaw !== '') {
        // En France on tape "25,50" — MySQL veut "25.50", on convertit
        $priceRaw = str_replace(',', '.', $priceRaw);
        if (!is_numeric($priceRaw) || (float)$priceRaw < 0) {
            $errors[] = "Le prix doit être un nombre positif.";
        } elseif ((float)$priceRaw > 99999) {
            $errors[] = "Le prix est trop élevé.";
        } else {
            $price = round((float)$priceRaw, 2);
        }
    }

    // --- Validation type + champs conditionnels ---
    if (!in_array($type, ['presentiel', 'visio'], true)) {
        $type = 'presentiel';
    }

    // Selon le type choisi, l'un des deux champs devient obligatoire
    // et l'autre est forcé à NULL. On ne stocke jamais une adresse
    // pour une visio ni un lien pour du présentiel : la base reste cohérente.
    if ($type === 'presentiel') {
        if ($location === '') {
            $errors[] = "L'adresse est obligatoire pour un rendez-vous en présentiel.";
        }
        $meeting_link = null;
    } else { // visio
        if ($meeting_link === '') {
            $errors[] = "Le lien visio (Zoom, Meet…) est obligatoire pour un rendez-vous en visio.";
        } elseif (!filter_var($meeting_link, FILTER_VALIDATE_URL)) {
            $errors[] = "Le lien visio doit être une URL valide (commençant par https://).";
        }
        $location = null; // pas d'adresse en visio
    }

    // --- Doublon ---
    if (empty($errors)) {
        $stmtCheck = $pdo->prepare("
            SELECT id FROM slots
            WHERE user_id = ? AND date = ? AND start_time = ?
            LIMIT 1
        ");
        $stmtCheck->execute([$_SESSION['user_id'], $date, $start_time]);
        if ($stmtCheck->fetch()) {
            $errors[] = "Vous avez déjà un créneau à cette date et cet horaire.";
        }
    }

    // --- Insertion ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO slots (user_id, title, date, start_time, end_time, price, type, location, meeting_link, is_booked)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $date,
                $start_time,
                $end_time,
                $price,
                $type,
                $location,
                $meeting_link
        ]);
        flashSet('success', 'Créneau créé avec succès !');
        redirect('dashboard.php');
    }
}

$selectedType = $_POST['type'] ?? 'presentiel';

layoutHeader("Nouveau créneau");
?>

    <div class="mb-6">
        <a href="dashboard.php" class="text-sm text-gray-400 hover:text-blue-600 transition">← Retour au dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nouveau créneau</h1>
        <p class="text-gray-500 text-sm mt-1">Définissez un créneau disponible pour vos clients.</p>
    </div>

<?php if (!$canCreate): ?>
    <!-- Limite atteinte : on bloque le formulaire et on pousse vers Pro -->
    <div class="max-w-lg bg-white border-2 border-amber-200 rounded-2xl p-8 text-center">
        <div class="text-5xl mb-4">🔒</div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Limite du plan Free atteinte</h2>
        <p class="text-gray-500 text-sm mb-1">
            Vous utilisez <strong><?= $usedSlots ?>/<?= $slotLimit ?></strong> créneaux actifs.
        </p>
        <p class="text-gray-400 text-sm mb-6">
            Supprimez un créneau existant ou passez Pro pour créer des créneaux illimités.
        </p>
        <div class="flex gap-3 justify-center">
            <a href="upgrade.php"
               class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold px-6 py-2.5 rounded-xl transition text-sm">
                ⭐ Passer Pro — 9€/mois
            </a>
            <a href="dashboard.php"
               class="border border-gray-300 text-gray-600 hover:bg-gray-50 font-medium px-6 py-2.5 rounded-xl transition text-sm">
                Gérer mes créneaux
            </a>
        </div>
    </div>

<?php else: ?>

    <?php if ($slotLimit !== null): ?>
        <!-- Compteur de quota pour les comptes Free -->
        <div class="max-w-lg mb-4 flex items-center justify-between bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
            <p class="text-sm text-blue-700">
                <strong><?= $usedSlots ?>/<?= $slotLimit ?></strong> créneaux actifs utilisés
            </p>
            <a href="upgrade.php" class="text-xs text-blue-600 hover:underline font-semibold">Passer Pro →</a>
        </div>
    <?php endif; ?>

    <div class="max-w-lg bg-white border border-gray-200 rounded-xl p-8">

        <?php if (!empty($errors)): ?>
            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm space-y-1">
                <?php foreach ($errors as $err): ?>
                    <p>• <?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Titre -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre du créneau</label>
                <input type="text" name="title"
                       value="<?= e($_POST['title'] ?? '') ?>"
                       placeholder="Ex : Coupe homme, Séance coaching, Cours de maths…"
                       required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <!-- Date -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date"
                       value="<?= e($_POST['date'] ?? '') ?>"
                       min="<?= date('Y-m-d') ?>"
                       required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
            </div>

            <!-- Heures -->
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                    <input type="time" name="start_time"
                           value="<?= e($_POST['start_time'] ?? '') ?>"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                    <input type="time" name="end_time"
                           value="<?= e($_POST['end_time'] ?? '') ?>"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>
            </div>

            <!-- Prix -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix <span class="text-gray-400 font-normal">(optionnel)</span></label>
                <div class="relative">
                    <input type="text" name="price" inputmode="decimal"
                           value="<?= e($_POST['price'] ?? '') ?>"
                           placeholder="Ex : 25"
                           class="w-full px-4 py-2.5 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Laissez vide pour un créneau gratuit. Le paiement se fait directement avec le client.</p>
            </div>

            <!-- Type de rendez-vous -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de rendez-vous</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="presentiel" class="peer sr-only"
                                <?= $selectedType === 'presentiel' ? 'checked' : '' ?>
                               onchange="toggleTypeFields()">
                        <div class="border-2 border-gray-200 peer-checked:border-orange-400 peer-checked:bg-orange-50 rounded-xl p-4 text-center transition">
                            <div class="text-2xl mb-1">📍</div>
                            <p class="text-sm font-semibold text-gray-700">Présentiel</p>
                            <p class="text-xs text-gray-400">Sur place</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="visio" class="peer sr-only"
                                <?= $selectedType === 'visio' ? 'checked' : '' ?>
                               onchange="toggleTypeFields()">
                        <div class="border-2 border-gray-200 peer-checked:border-purple-400 peer-checked:bg-purple-50 rounded-xl p-4 text-center transition">
                            <div class="text-2xl mb-1">🎥</div>
                            <p class="text-sm font-semibold text-gray-700">Visio</p>
                            <p class="text-xs text-gray-400">À distance</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Adresse (présentiel uniquement) -->
            <div id="field-location" class="mb-5 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">📍 Adresse du rendez-vous</label>
                <input type="text" name="location"
                       value="<?= e($_POST['location'] ?? '') ?>"
                       placeholder="Ex : 12 rue de la Paix, 75002 Paris"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                <p class="text-xs text-gray-400 mt-1">Le client pourra ouvrir cette adresse dans Google Maps.</p>
            </div>

            <!-- Lien visio (visio uniquement) -->
            <div id="field-meeting" class="mb-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">🎥 Lien de la visioconférence</label>
                <input type="url" name="meeting_link"
                       value="<?= e($_POST['meeting_link'] ?? '') ?>"
                       placeholder="https://meet.google.com/abc-defg-hij"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-transparent transition">
                <p class="text-xs text-gray-400 mt-1">Zoom, Google Meet, Teams… Le lien sera visible uniquement par le client après réservation.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition text-sm">
                    Créer le créneau
                </button>
                <a href="dashboard.php"
                   class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition text-center">
                    Annuler
                </a>
            </div>

        </form>
    </div>

    <script>
        // Affiche le champ adresse OU le champ lien visio selon le
        // bouton radio coché. Appelée au chargement + à chaque clic.
        // Attention : ce n'est que du confort visuel — la vraie
        // validation est faite en PHP plus haut.
        function toggleTypeFields() {
            const type        = document.querySelector('input[name="type"]:checked').value;
            const locField    = document.getElementById('field-location');
            const meetField   = document.getElementById('field-meeting');

            if (type === 'presentiel') {
                locField.classList.remove('hidden');
                meetField.classList.add('hidden');
            } else {
                locField.classList.add('hidden');
                meetField.classList.remove('hidden');
            }
        }
        // Initialiser au chargement
        toggleTypeFields();
    </script>

<?php endif; // fin du else canCreate ?>

<?php layoutFooter(); ?>
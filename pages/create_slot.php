<?php

// ============================================================
// create_slot.php — Création d'un créneau
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $title      = trim(isset($_POST['title'])      ? $_POST['title']      : '');
    $date       = trim(isset($_POST['date'])       ? $_POST['date']       : '');
    $start_time = trim(isset($_POST['start_time']) ? $_POST['start_time'] : '');
    $end_time   = trim(isset($_POST['end_time'])   ? $_POST['end_time']   : '');

    // --- Validation ---
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

    // --- Doublon : même pro, même date, même heure de début ---
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

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO slots (user_id, title, date, start_time, end_time, is_booked)
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $date,
                $start_time,
                $end_time
        ]);
        redirect('dashboard.php');
    }
}

layoutHeader("Nouveau créneau");
?>

    <!-- En-tête -->
    <div class="mb-6">
        <a href="dashboard.php" class="text-sm text-gray-400 hover:text-blue-600 transition">← Retour au dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nouveau créneau</h1>
        <p class="text-gray-500 text-sm mt-1">Définissez un créneau disponible pour vos clients.</p>
    </div>

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

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre du créneau</label>
                <input
                        type="text" name="title"
                        value="<?= e(isset($_POST['title']) ? $_POST['title'] : '') ?>"
                        placeholder="Ex : Coupe homme, Séance coaching, Cours de maths…"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input
                        type="date" name="date"
                        value="<?= e(isset($_POST['date']) ? $_POST['date'] : '') ?>"
                        min="<?= date('Y-m-d') ?>"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                    <input
                            type="time" name="start_time"
                            value="<?= e(isset($_POST['start_time']) ? $_POST['start_time'] : '') ?>"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                    <input
                            type="time" name="end_time"
                            value="<?= e(isset($_POST['end_time']) ? $_POST['end_time'] : '') ?>"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                </div>
            </div>

            <div class="flex gap-3">
                <button
                        type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition text-sm"
                >
                    Créer le créneau
                </button>
                <a
                        href="dashboard.php"
                        class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition text-center"
                >
                    Annuler
                </a>
            </div>

        </form>
    </div>

<?php layoutFooter(); ?>
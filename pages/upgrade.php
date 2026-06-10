<?php

// ============================================================
// upgrade.php — Page des plans Free / Pro
// Le passage en Pro est SIMULÉ (pas de vrai paiement)
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];

// --- Traitement upgrade / downgrade (simulé) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'upgrade') {
        $pdo->prepare("UPDATE users SET plan = 'pro', plan_since = NOW() WHERE id = ?")
                ->execute([$userId]);
        // Très important : on met aussi à jour le cache en session,
        // sinon la navbar continuerait d'afficher "Free" jusqu'à
        // la prochaine déconnexion
        $_SESSION['user_plan'] = 'pro';
        flashSet('success', '🎉 Bienvenue dans Slotify Pro ! Créneaux illimités débloqués.');
        redirect('dashboard.php');
    }

    if ($action === 'downgrade') {
        $pdo->prepare("UPDATE users SET plan = 'free', plan_since = NULL WHERE id = ?")
                ->execute([$userId]);
        $_SESSION['user_plan'] = 'free';
        flashSet('info', 'Vous êtes repassé au plan gratuit.');
        redirect('upgrade.php');
    }
}

$currentPlan = getUserPlan($pdo);
$activeSlots = countActiveSlots($pdo, $userId);

layoutHeader("Plans & Tarifs");
?>

    <!-- En-tête -->
    <div class="text-center mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900">Choisissez votre plan</h1>
        <p class="text-gray-400 mt-2">Commencez gratuitement, passez Pro quand votre activité décolle.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">

        <!-- Plan Free -->
        <div class="bg-white border-2 <?= $currentPlan === 'free' ? 'border-blue-400' : 'border-gray-200' ?> rounded-2xl p-8 relative">

            <?php if ($currentPlan === 'free'): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">
            Votre plan actuel
        </span>
            <?php endif; ?>

            <h2 class="text-lg font-bold text-gray-800">Free</h2>
            <p class="text-4xl font-extrabold text-gray-900 mt-2">0 €<span class="text-base font-normal text-gray-400">/mois</span></p>
            <p class="text-sm text-gray-400 mt-1">Pour démarrer tranquillement.</p>

            <ul class="mt-6 space-y-3 text-sm">
                <li class="flex items-center gap-2 text-gray-600">
                    <span class="text-green-500">✓</span> <strong><?= FREE_PLAN_SLOT_LIMIT ?> créneaux actifs</strong> maximum
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <span class="text-green-500">✓</span> Page publique partageable
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <span class="text-green-500">✓</span> RDV présentiel & visio
                </li>
                <li class="flex items-center gap-2 text-gray-600">
                    <span class="text-green-500">✓</span> Historique des réservations
                </li>
                <li class="flex items-center gap-2 text-gray-300">
                    <span>✗</span> Statistiques de revenus avancées
                </li>
                <li class="flex items-center gap-2 text-gray-300">
                    <span>✗</span> Support prioritaire
                </li>
            </ul>

            <?php if ($currentPlan === 'pro'): ?>
                <form method="POST" class="mt-8" onsubmit="return confirm('Repasser au plan gratuit ? Vous serez limité à <?= FREE_PLAN_SLOT_LIMIT ?> créneaux actifs.')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="downgrade">
                    <button type="submit" class="w-full border border-gray-300 text-gray-500 hover:bg-gray-50 font-medium py-2.5 rounded-xl transition text-sm">
                        Repasser en Free
                    </button>
                </form>
            <?php else: ?>
                <div class="mt-8 text-center text-sm text-gray-400 py-2.5">
                    Plan actuel — <?= $activeSlots ?>/<?= FREE_PLAN_SLOT_LIMIT ?> créneaux utilisés
                </div>
            <?php endif; ?>

        </div>

        <!-- Plan Pro -->
        <div class="bg-gradient-to-b from-blue-600 to-blue-700 text-white border-2 border-blue-600 rounded-2xl p-8 relative shadow-lg">

            <?php if ($currentPlan === 'pro'): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-400 text-white text-xs font-bold px-3 py-1 rounded-full">
            ⭐ Votre plan actuel
        </span>
            <?php else: ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-400 text-white text-xs font-bold px-3 py-1 rounded-full">
            Recommandé
        </span>
            <?php endif; ?>

            <h2 class="text-lg font-bold">Pro ⭐</h2>
            <p class="text-4xl font-extrabold mt-2">9 €<span class="text-base font-normal text-blue-200">/mois</span></p>
            <p class="text-sm text-blue-200 mt-1">Pour les pros qui veulent tout.</p>

            <ul class="mt-6 space-y-3 text-sm">
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> <strong>Créneaux illimités</strong>
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> Page publique partageable
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> RDV présentiel & visio
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> Historique des réservations
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> Statistiques de revenus avancées
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-amber-300">✓</span> Support prioritaire
                </li>
            </ul>

            <?php if ($currentPlan === 'pro'): ?>
                <div class="mt-8 text-center text-sm text-blue-200 py-2.5">
                    ⭐ Vous profitez de tous les avantages Pro
                </div>
            <?php else: ?>
                <form method="POST" class="mt-8">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="upgrade">
                    <button type="submit" class="w-full bg-white text-blue-700 hover:bg-blue-50 font-bold py-2.5 rounded-xl transition text-sm shadow-sm">
                        Passer Pro maintenant →
                    </button>
                </form>
                <p class="text-center text-xs text-blue-300 mt-3">
                    🎓 Démo projet : aucun vrai paiement, le passage est instantané.
                </p>
            <?php endif; ?>

        </div>

    </div>

    <!-- FAQ -->
    <div class="max-w-2xl mx-auto mt-16">
        <h2 class="text-lg font-bold text-gray-800 text-center mb-6">Questions fréquentes</h2>

        <div class="space-y-3">
            <details class="bg-white border border-gray-200 rounded-xl p-5 group">
                <summary class="font-medium text-gray-700 cursor-pointer text-sm list-none flex items-center justify-between">
                    Que se passe-t-il si je dépasse la limite en Free ?
                    <span class="text-gray-300 group-open:rotate-180 transition">▼</span>
                </summary>
                <p class="text-sm text-gray-400 mt-3">Vous ne pourrez plus créer de nouveaux créneaux tant que vous avez <?= FREE_PLAN_SLOT_LIMIT ?> créneaux actifs. Vos créneaux existants restent visibles et réservables. Supprimez-en ou passez Pro pour continuer.</p>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl p-5 group">
                <summary class="font-medium text-gray-700 cursor-pointer text-sm list-none flex items-center justify-between">
                    Comment fonctionne le paiement des rendez-vous ?
                    <span class="text-gray-300 group-open:rotate-180 transition">▼</span>
                </summary>
                <p class="text-sm text-gray-400 mt-3">Slotify affiche le prix de vos prestations mais ne gère pas le paiement. Vos clients vous règlent directement (sur place, virement…). Vous gardez 100% de vos revenus.</p>
            </details>

            <details class="bg-white border border-gray-200 rounded-xl p-5 group">
                <summary class="font-medium text-gray-700 cursor-pointer text-sm list-none flex items-center justify-between">
                    Puis-je annuler mon abonnement Pro ?
                    <span class="text-gray-300 group-open:rotate-180 transition">▼</span>
                </summary>
                <p class="text-sm text-gray-400 mt-3">Oui, à tout moment depuis cette page. Vous repassez en Free et conservez tous vos créneaux et votre historique.</p>
            </details>
        </div>
    </div>

<?php layoutFooter(); ?>
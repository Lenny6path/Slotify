<?php

// ============================================================
// profil_public.php — La page "vitrine" d'un professionnel
//
// C'est LE lien que le pro partage à ses clients :
//   profil_public.php?id=3
// Elle est accessible SANS être connecté (volontaire : un
// client doit pouvoir consulter les créneaux avant de créer
// un compte). La connexion n'est exigée qu'au moment de réserver.
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

// Le cast (int) neutralise toute tentative d'injection dans l'URL :
// "3abc" devient 3, "abc" devient 0
$proId = (int) ($_GET['id'] ?? 0);
if ($proId <= 0) {
    // index.php est à la racine du projet, un niveau au-dessus
    // du dossier pages/, d'où le ../
    redirect('../index.php');
}

// On ne sélectionne QUE les colonnes publiques : surtout pas
// l'email ni le hash du mot de passe sur une page publique !
$stmtPro = $pdo->prepare("SELECT id, name, bio, created_at FROM users WHERE id = ? LIMIT 1");
$stmtPro->execute([$proId]);
$pro = $stmtPro->fetch();

if (!$pro) {
    http_response_code(404);
    die("Professionnel introuvable.");
}

// Les créneaux affichés ici : disponibles ET futurs uniquement.
// Pas la peine de montrer les créneaux réservés ou expirés à un client.
$stmtSlots = $pdo->prepare("
    SELECT * FROM slots
    WHERE user_id = ? AND is_booked = 0 AND date >= CURDATE()
    ORDER BY date ASC, start_time ASC
");
$stmtSlots->execute([$proId]);
$slots = $stmtSlots->fetchAll();

// On regroupe les créneaux par jour pour pouvoir afficher
// un titre de date au-dessus de chaque groupe ("Lundi 15 juin...")
$slotsByDate = [];
foreach ($slots as $slot) {
    $slotsByDate[$slot['date']][] = $slot;
}

// Deux cas particuliers à gérer dans l'affichage :
$isLoggedIn = !empty($_SESSION['user_id']);                     // visiteur non connecté
$isOwnPage  = $isLoggedIn && $_SESSION['user_id'] == $proId;    // le pro regarde sa propre page

layoutHeader("Réserver avec " . $pro['name']);
?>

    <!-- En-tête du profil : avatar, nom, bio -->
    <div class="mb-10 text-center">
        <div class="w-20 h-20 rounded-full bg-blue-100 text-blue-700 text-2xl font-bold flex items-center justify-center mx-auto mb-4">
            <?= avatar($pro['name']) ?>
        </div>
        <h1 class="text-2xl font-bold text-gray-900"><?= e($pro['name']) ?></h1>
        <?php if (!empty($pro['bio'])): ?>
            <p class="text-gray-500 mt-2 max-w-md mx-auto text-sm leading-relaxed"><?= e($pro['bio']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-300 mt-2">Sur Slotify depuis <?= date('F Y', strtotime($pro['created_at'])) ?></p>
    </div>

<?php if (empty($slots)): ?>
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400 max-w-lg mx-auto">
        <p class="text-4xl mb-3">😴</p>
        <p class="font-semibold">Aucun créneau disponible pour le moment.</p>
        <p class="text-sm mt-1">Revenez plus tard ou contactez directement <?= e($pro['name']) ?>.</p>
    </div>

<?php else: ?>

    <div class="max-w-2xl mx-auto">

        <?php if ($isOwnPage): ?>
            <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm text-center">
                C'est votre page publique. <a href="dashboard.php" class="font-semibold underline">Aller au dashboard →</a>
            </div>
        <?php endif; ?>

        <h2 class="text-lg font-semibold text-gray-800 mb-5">
            <?= count($slots) ?> créneau<?= count($slots) > 1 ? 'x' : '' ?> disponible<?= count($slots) > 1 ? 's' : '' ?>
        </h2>

        <?php foreach ($slotsByDate as $date => $dateSlots): ?>

            <div class="mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    <?= date('l d F Y', strtotime($date)) ?>
                </p>
                <div class="space-y-3">
                    <?php foreach ($dateSlots as $slot): ?>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:shadow-sm transition">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold text-gray-900"><?= e($slot['title']) ?></p>
                                    <?= typeBadge($slot) ?>
                                </div>
                                <p class="text-sm text-gray-400 mt-0.5">
                                    🕐 <?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?>
                                    &nbsp;·&nbsp; <?= formatPrice($slot['price'] ?? null) ?>
                                </p>
                                <?php // L'adresse est publique. Le lien visio, lui, n'apparaît
                                // JAMAIS ici : il n'est révélé qu'après réservation. ?>
                                <?php if (($slot['type'] ?? '') === 'presentiel' && !empty($slot['location'])): ?>
                                    <p class="text-xs text-gray-400 mt-0.5">📍 <?= e($slot['location']) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isLoggedIn): ?>
                                <?php // urlencode garantit une URL propre : sans lui, le 2e "?"
                                // de "?id=3" casserait le paramètre redirect ?>
                                <a href="login.php?redirect=<?= urlencode('profil_public.php?id=' . $proId) ?>"
                                   class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition">
                                    Se connecter pour réserver
                                </a>
                            <?php elseif ($isOwnPage): ?>
                                <span class="text-xs text-gray-300">Votre créneau</span>
                            <?php else: ?>
                                <a href="confirm_booking.php?slot_id=<?= $slot['id'] ?>"
                                   class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition">
                                    Réserver →
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php layoutFooter(); ?>
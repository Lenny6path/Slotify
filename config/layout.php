<?php

// ============================================================
// config/layout.php — Le squelette HTML commun à toutes les pages
//
// Au lieu de copier-coller le <head>, la navbar et le footer
// dans chaque fichier, on les centralise ici. Chaque page fait :
//   layoutHeader("Titre");   <- ouvre la page + navbar
//   ... son contenu ...
//   layoutFooter();          <- ferme la page
// Si on veut changer la navbar, on ne touche qu'à ce fichier.
// ============================================================

// $withNav = false permet d'avoir une page sans navbar
// (utilisé par login et register, où ça n'aurait pas de sens)
function layoutHeader(string $title, bool $withNav = true): void
{
    // global $pdo : on récupère la connexion créée dans db.php
    // pour pouvoir vérifier le plan de l'utilisateur (badge PRO)
    global $pdo;

    $userName  = isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : '';
    $userId    = $_SESSION['user_id'] ?? null;
    $initials  = $userName ? avatar($userName) : '?';
    $isProUser = $userId ? isPro($pdo) : false;
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> — Slotify</title>
        <!-- Tailwind en CDN : zéro installation, parfait pour un projet local.
             En vraie prod on compilerait le CSS pour alléger la page. -->
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body { font-family: 'Inter', 'Segoe UI', sans-serif; }
            /* Petite animation d'apparition du contenu à chaque page */
            .fade-in { animation: fadeIn .25s ease; }
            @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen text-gray-800">

    <?php // La navbar ne s'affiche que pour les utilisateurs connectés ?>
    <?php if ($withNav && $userId): ?>
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
        <div class="max-w-5xl mx-auto px-6 h-14 flex items-center justify-between">

            <a href="dashboard.php" class="text-blue-600 font-bold text-lg tracking-tight flex items-center gap-2">
                📅 <span>Slotify</span>
            </a>

            <div class="flex items-center gap-4 text-sm">
                <a href="dashboard.php"   class="text-gray-600 hover:text-blue-600 transition font-medium">Dashboard</a>
                <a href="slots.php"       class="text-gray-600 hover:text-blue-600 transition">Créneaux</a>
                <a href="history.php"     class="text-gray-600 hover:text-blue-600 transition">Historique</a>
                <a href="create_slot.php" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                    + Nouveau
                </a>

                <?php // Le bouton "Passer Pro" disparaît une fois qu'on est Pro ?>
                <?php if (!$isProUser): ?>
                    <a href="upgrade.php" class="text-xs font-bold text-amber-500 hover:text-amber-600 transition border border-amber-300 hover:border-amber-400 px-2.5 py-1 rounded-lg">
                        ⭐ Passer Pro
                    </a>
                <?php endif; ?>

                <span class="text-gray-200">|</span>

                <?php // L'avatar devient doré pour les comptes Pro, petit détail qui flatte ?>
                <a href="profile.php" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
                <span class="w-7 h-7 rounded-full <?= $isProUser ? 'bg-gradient-to-br from-amber-400 to-yellow-500 text-white' : 'bg-blue-100 text-blue-700' ?> flex items-center justify-center text-xs font-bold flex-shrink-0">
                    <?= $initials ?>
                </span>
                    <!-- hidden sm:flex = le nom est masqué sur mobile pour gagner de la place -->
                    <span class="hidden sm:flex items-center gap-1.5 text-sm">
                        <?= $userName ?>
                        <?php if ($isProUser): ?><span class="text-[10px] font-bold text-amber-500">PRO</span><?php endif; ?>
                    </span>
                </a>
                <a href="logout.php" class="text-xs text-red-400 hover:text-red-600 transition font-medium">Déconnexion</a>
            </div>

        </div>
    </nav>
<?php endif; ?>

    <main class="max-w-5xl mx-auto px-6 py-8 fade-in">
    <?php
    // Les messages flash s'affichent en haut de chaque page,
    // juste après l'ouverture du <main>
    flashRender();
}

function layoutFooter(): void
{
    ?>
    </main>
    <footer class="text-center text-xs text-gray-400 py-8 border-t border-gray-100 mt-4">
        © <?= date('Y') ?> Slotify — Gestion de rendez-vous simplifiée
    </footer>
    </body>
    </html>
    <?php
}
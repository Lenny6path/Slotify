<?php

// ============================================================
// config/layout.php — Header / Footer partagés
// ============================================================

function layoutHeader(string $title, bool $withNav = true): void
{
    $userName = isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : '';
    $userId   = $_SESSION['user_id'] ?? null;
    $initials = $userName ? avatar($userName) : '?';
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> — Slotify</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body { font-family: 'Inter', 'Segoe UI', sans-serif; }
            .fade-in { animation: fadeIn .25s ease; }
            @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen text-gray-800">

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
                <span class="text-gray-200">|</span>
                <a href="profile.php" class="flex items-center gap-2 text-gray-600 hover:text-blue-600 transition">
                <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    <?= $initials ?>
                </span>
                    <span class="hidden sm:inline text-sm"><?= $userName ?></span>
                </a>
                <a href="logout.php" class="text-xs text-red-400 hover:text-red-600 transition font-medium">Déconnexion</a>
            </div>

        </div>
    </nav>
<?php endif; ?>

    <main class="max-w-5xl mx-auto px-6 py-8 fade-in">
    <?php
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
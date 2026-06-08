<?php

// ============================================================
// config/layout.php — Header et footer HTML partagés
// Usage :
//   layoutHeader("Titre de la page");
//   ...contenu...
//   layoutFooter();
// ============================================================

function layoutHeader(string $title, bool $withNav = true): void
{
    $userName = isset($_SESSION['user_name']) ? e($_SESSION['user_name']) : '';
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
            .fade-in { animation: fadeIn .3s ease; }
            @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen text-gray-800">

    <?php if ($withNav): ?>
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-6 h-14 flex items-center justify-between">
            <a href="../pages/dashboard.php" class="text-blue-600 font-bold text-lg tracking-tight">Slotify</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="../pages/dashboard.php" class="text-gray-600 hover:text-blue-600 transition">Dashboard</a>
                <a href="../pages/slots.php" class="text-gray-600 hover:text-blue-600 transition">Créneaux</a>
                <a href="../pages/create_slot.php" class="text-gray-600 hover:text-blue-600 transition">+ Nouveau</a>
                <span class="text-gray-300">|</span>
                <span class="text-gray-500">👤 <?= $userName ?></span>
                <a href="../pages/logout.php" class="text-red-500 hover:text-red-700 transition font-medium">Déconnexion</a>
            </div>
        </div>
    </nav>
<?php endif; ?>

    <main class="max-w-5xl mx-auto px-6 py-8 fade-in">
    <?php
}

function layoutFooter(): void
{
    ?>
    </main>
    <footer class="text-center text-xs text-gray-400 py-6">
        © <?= date('Y') ?> Slotify — Mini SaaS de rendez-vous
    </footer>
    </body>
    </html>
    <?php
}
<?php

// ============================================================
// index.php — Landing page Slotify
// ============================================================

require_once __DIR__ . '/config/init.php';

// Si déjà connecté → dashboard directement
if (!empty($_SESSION['user_id'])) {
    redirect('pages/dashboard.php');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slotify — Gérez vos rendez-vous simplement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', 'Segoe UI', sans-serif; }
        .fade-in { animation: fadeIn .4s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .gradient-hero { background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); }
    </style>
</head>
<body class="bg-white text-gray-800">

<!-- Navbar -->
<nav class="border-b border-gray-100 bg-white/90 backdrop-blur sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-6 h-14 flex items-center justify-between">
        <span class="text-blue-600 font-bold text-lg tracking-tight">📅 Slotify</span>
        <div class="flex items-center gap-3">
            <a href="pages/login.php"    class="text-sm text-gray-600 hover:text-blue-600 transition font-medium px-3 py-1.5">Se connecter</a>
            <a href="pages/register.php" class="text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-1.5 rounded-lg transition">Commencer gratuitement</a>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="gradient-hero py-24 px-6 fade-in">
    <div class="max-w-3xl mx-auto text-center">
        <span class="inline-block bg-blue-100 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full mb-6 tracking-wide uppercase">
            Mini SaaS de rendez-vous
        </span>
        <h1 class="text-5xl font-extrabold text-gray-900 leading-tight mb-6">
            Gérez vos rendez-vous<br>
            <span class="text-blue-600">sans prise de tête</span>
        </h1>
        <p class="text-xl text-gray-500 mb-10 leading-relaxed">
            Créez vos créneaux, partagez votre lien, recevez des réservations.<br>
            Fini les allers-retours par SMS ou WhatsApp.
        </p>
        <div class="flex items-center justify-center gap-4 flex-wrap">
            <a href="pages/register.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3.5 rounded-xl text-base transition shadow-sm">
                Créer mon compte gratuit →
            </a>
            <a href="pages/login.php" class="text-gray-600 hover:text-blue-600 font-medium text-base transition">
                J'ai déjà un compte
            </a>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-20 px-6 bg-white">
    <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Tout ce dont vous avez besoin</h2>
            <p class="text-gray-400 text-lg">Simple, rapide, efficace.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <div class="bg-gray-50 rounded-2xl p-7 text-center hover:shadow-sm transition">
                <div class="text-4xl mb-4">📆</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Créez vos créneaux</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Définissez vos disponibilités en quelques secondes. Date, heure, titre — c'est tout.</p>
            </div>

            <div class="bg-gray-50 rounded-2xl p-7 text-center hover:shadow-sm transition">
                <div class="text-4xl mb-4">🔗</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Partagez votre lien</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Chaque professionnel a une page publique unique. Envoyez le lien à vos clients, ils réservent directement.</p>
            </div>

            <div class="bg-gray-50 rounded-2xl p-7 text-center hover:shadow-sm transition">
                <div class="text-4xl mb-4">✅</div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">Zéro conflit</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Un créneau réservé disparaît automatiquement. Plus de doubles réservations, plus de malentendus.</p>
            </div>

        </div>
    </div>
</section>

<!-- Qui peut l'utiliser -->
<section class="py-20 px-6 bg-gray-50">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-3">Pour qui ?</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $profiles = [
                ['✂️', 'Coiffeurs'],
                ['📚', 'Profs particuliers'],
                ['💪', 'Coachs sportifs'],
                ['💼', 'Freelances'],
            ];
            foreach ($profiles as [$emoji, $label]):
                ?>
                <div class="bg-white rounded-xl p-6 text-center border border-gray-100 hover:border-blue-200 hover:shadow-sm transition">
                    <div class="text-3xl mb-2"><?= $emoji ?></div>
                    <p class="font-semibold text-gray-700 text-sm"><?= $label ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="py-24 px-6 bg-blue-600 text-white text-center">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-extrabold mb-4">Prêt à simplifier votre planning ?</h2>
        <p class="text-blue-100 mb-8 text-lg">Inscription gratuite, aucune carte bancaire requise.</p>
        <a href="pages/register.php" class="inline-block bg-white text-blue-600 font-bold px-8 py-3.5 rounded-xl text-base hover:bg-blue-50 transition shadow">
            Commencer maintenant →
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="text-center text-xs text-gray-400 py-6 border-t border-gray-100">
    © <?= date('Y') ?> Slotify — Mini SaaS de rendez-vous
</footer>

</body>
</html>
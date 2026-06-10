<?php

// ============================================================
// login.php — Connexion
//
// Gère aussi le paramètre ?redirect= : si un visiteur non
// connecté clique "Réserver" sur une page publique, on le
// renvoie sur cette même page après sa connexion au lieu de
// le perdre sur le dashboard.
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

// Déjà connecté ? Rien à faire ici.
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Récupération de la destination post-connexion.
// SÉCURITÉ : on n'accepte que des chemins relatifs internes.
// Sans ce filtre, quelqu'un pourrait fabriquer un lien
// login.php?redirect=https://site-pirate.com (open redirect).
$redirectTo = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if ($redirectTo !== '' && (str_contains($redirectTo, '//') || str_contains($redirectTo, ':'))) {
    $redirectTo = ''; // URL externe détectée -> on ignore
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // password_verify compare le mot de passe tapé avec le hash
        // stocké en base. On ne stocke JAMAIS un mot de passe en clair.
        if ($user && password_verify($password, $user['password'])) {

            // Régénérer l'ID de session après connexion protège contre
            // la "fixation de session" (un attaquant qui aurait imposé
            // son propre ID de session avant le login)
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            // On vide le cache du plan : si un autre compte était
            // connecté avant sur ce navigateur, son plan ne doit
            // pas "déteindre" sur le nouveau
            unset($_SESSION['user_plan']);

            // Retour à la page d'origine si on en avait une, sinon dashboard
            redirect($redirectTo !== '' ? $redirectTo : 'dashboard.php');
        } else {
            // Message volontairement vague : on ne précise pas si c'est
            // l'email ou le mot de passe qui est faux, pour ne pas aider
            // quelqu'un qui testerait des emails au hasard
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

layoutHeader("Connexion", false);
?>

    <div class="min-h-[80vh] flex items-center justify-center">
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue-600 tracking-tight">Slotify</h1>
                <p class="text-gray-500 mt-1 text-sm">Gestion de rendez-vous simplifiée</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Connexion</h2>

                <?php if ($error !== ''): ?>
                    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?php // On fait suivre la destination dans le formulaire,
                    // sinon elle serait perdue au moment du POST ?>
                    <?php if ($redirectTo !== ''): ?>
                        <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                                type="email" name="email"
                                value="<?= e($_POST['email'] ?? '') ?>"
                                placeholder="vous@exemple.fr"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input
                                type="password" name="password"
                                placeholder="••••••••"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                    </div>

                    <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition text-sm"
                    >
                        Se connecter
                    </button>
                </form>
            </div>

            <p class="text-center text-sm text-gray-500 mt-4">
                Pas encore de compte ?
                <a href="register.php" class="text-blue-600 hover:underline font-medium">Créer un compte</a>
            </p>

        </div>
    </div>

<?php layoutFooter(); ?>
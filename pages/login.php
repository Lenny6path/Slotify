<?php

// ============================================================
// login.php — Connexion professionnelle
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

// Déjà connecté → dashboard
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $email    = trim(isset($_POST['email'])    ? $_POST['email']    : '');
    $password =     (isset($_POST['password']) ? $_POST['password'] : '');

    if ($email === '' || $password === '') {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // sécurité : nouvel ID de session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('dashboard.php');
        } else {
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

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                                type="email" name="email"
                                value="<?= e(isset($_POST['email']) ? $_POST['email'] : '') ?>"
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
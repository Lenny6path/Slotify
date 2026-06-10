<?php

// ============================================================
// profile.php — Modifier son profil pro
// ============================================================

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/layout.php';

requireAuth();

$userId = $_SESSION['user_id'];
$errors = [];

// Récupérer les données actuelles
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    // --- Modifier nom / email / bio ---
    if ($action === 'update_info') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio   = trim($_POST['bio']   ?? '');

        if ($name === '')  $errors[] = "Le nom est obligatoire.";
        if ($email === '') $errors[] = "L'email est obligatoire.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

        // Vérifier doublon email (sauf le sien)
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$email, $userId]);
            if ($stmtCheck->fetch()) $errors[] = "Cet email est déjà utilisé par un autre compte.";
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET name = ?, email = ?, bio = ? WHERE id = ?")
                    ->execute([$name, $email, $bio, $userId]);
            $_SESSION['user_name'] = $name;
            flashSet('success', 'Profil mis à jour avec succès !');
            redirect('profile.php');
        }
    }

    // --- Changer le mot de passe ---
    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = "Mot de passe actuel incorrect.";
        } elseif (strlen($new) < 6) {
            $errors[] = "Le nouveau mot de passe doit faire au moins 6 caractères.";
        } elseif ($new !== $confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            flashSet('success', 'Mot de passe modifié avec succès !');
            redirect('profile.php');
        }
    }
}

layoutHeader("Mon profil");
?>

    <div class="mb-8">
        <a href="dashboard.php" class="text-sm text-gray-400 hover:text-blue-600 transition">← Retour au dashboard</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Mon profil</h1>
        <p class="text-gray-400 text-sm mt-1">Gérez vos informations personnelles et votre mot de passe.</p>
    </div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm space-y-1">
        <?php foreach ($errors as $err): ?><p>• <?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Infos générales -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">Informations générales</h2>

            <!-- Avatar -->
            <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                    <?= avatar($user['name']) ?>
                </div>
                <div>
                    <p class="font-semibold text-gray-800 flex items-center gap-2"><?= e($user['name']) ?> <?= planBadge($pdo) ?></p>
                    <p class="text-sm text-gray-400"><?= e($user['email']) ?></p>
                    <p class="text-xs text-gray-300 mt-0.5">Membre depuis <?= date('M Y', strtotime($user['created_at'])) ?></p>
                    <a href="upgrade.php" class="text-xs text-blue-500 hover:underline">Gérer mon plan →</a>
                </div>
            </div>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action"     value="update_info">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                    <input type="text" name="name" value="<?= e($_POST['name'] ?? $user['name']) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?= e($_POST['email'] ?? $user['email']) ?>" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio / Description</label>
                    <textarea name="bio" rows="3" placeholder="Ex : Coiffeur à Paris, 10 ans d'expérience..."
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"><?= e($_POST['bio'] ?? $user['bio'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Visible sur votre page publique.</p>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition text-sm">
                    Sauvegarder les modifications
                </button>
            </form>
        </div>

        <!-- Changer mot de passe -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">Changer le mot de passe</h2>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action"     value="update_password">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                    <input type="password" name="new_password" placeholder="Min. 6 caractères" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                </div>

                <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-2.5 rounded-lg transition text-sm">
                    Changer le mot de passe
                </button>
            </form>

            <!-- Lien public -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm font-medium text-gray-700 mb-2">Votre lien public</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 bg-gray-50 border border-gray-200 text-xs text-gray-500 px-3 py-2 rounded-lg truncate">
                        profil_public.php?id=<?= $userId ?>
                    </code>
                    <button onclick="navigator.clipboard.writeText(window.location.origin+'/pages/profil_public.php?id=<?= $userId ?>');this.textContent='✅ Copié !';setTimeout(()=>this.textContent='Copier',2000);"
                            class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-2 rounded-lg transition whitespace-nowrap">
                        Copier
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1.5">Partagez ce lien à vos clients pour qu'ils puissent réserver.</p>
            </div>
        </div>

    </div>

<?php layoutFooter(); ?>
<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Tous les champs sont obligatoires";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            redirect('index.php');
        }
        $error = "Identifiants incorrects";
    }
}

$pageTitle = 'Connexion';
include __DIR__ . '/includes/layout_top.php';
?>

<section class="auth">
    <div class="auth-hero" aria-hidden="true">
        <span class="ball big"></span>
    </div>
    <h1>Échange tes doubles</h1>
    <p class="muted">30th Celebration &amp; Classic Collection. Connecte-toi avec ton compte Lanoël.</p>

    <?php if (isset($error)): ?>
        <div class="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form">
        <label>Pseudo
            <input type="text" name="username" autocomplete="username" autocapitalize="none" required value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label>Mot de passe
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn primary block">Se connecter</button>
    </form>

    <p class="muted small center">Pas de compte ? <a class="link" href="<?= h(POKE_MAIN_URL) ?>/register.php">Inscris-toi sur lanoel.ch</a></p>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>

<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($username) < 3) {
        $error = "Le pseudo doit contenir au moins 3 caractères";
    } elseif (strlen($password) < 4) {
        $error = "Le mot de passe doit contenir au moins 4 caractères";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->fetch()) {
                $error = "Ce pseudo est déjà utilisé";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $hashedPassword]);
                
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de l'inscription";
        }
    }
}

$pageTitle = "Inscription";
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-box">
        <h1>Inscription</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <a href="login.php" class="btn btn-primary" style="width: 100%;">Se connecter</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Pseudo</label>
                    <input type="text" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">S'inscrire</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--gray);">
                Déjà inscrit ? <a href="login.php" style="color: var(--black); font-weight: 500;">Se connecter</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

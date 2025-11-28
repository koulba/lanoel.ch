<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                if ($user['is_admin']) {
                    redirect('admin/index.php');
                } else {
                    redirect('index.php');
                }
            } else {
                $error = "Identifiants incorrects";
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la connexion";
        }
    }
}

$pageTitle = "Connexion";
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-box">
        <h1>Connexion</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Pseudo</label>
                <input type="text" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Se connecter</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: var(--gray);">
            Pas encore inscrit ? <a href="register.php" style="color: var(--black); font-weight: 500;">S'inscrire</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

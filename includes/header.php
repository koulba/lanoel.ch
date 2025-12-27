<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'Tournoi Gaming' ?></title>
    
    <?php
    // Déterminer le chemin de base
    $isProfile = strpos($_SERVER['PHP_SELF'], '/profile/') !== false;
    $base_path = '';
    
    if (isset($isAdmin) && $isAdmin) {
        $base_path = '../';
    } elseif ($isProfile) {
        $base_path = '../';
    }
    ?>
    
    <link rel="stylesheet" href="<?= $base_path ?>css/style.css">
    <link rel="icon" type="image/x-icon" href="<?= $base_path ?>images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_path ?>images/favicon.ico">
    <link rel="apple-touch-icon" href="<?= $base_path ?>images/favicon.ico">
</head>
<body>
    <header>
        <div class="container">
            <!-- Logo -->
            <h1>
                <a href="<?= $base_path ?>index.php" class="header-logo-link">
                    <img src="<?= $base_path ?>images/lanoel.webp" alt="Tournoi Gaming" class="header-logo-img">
                </a>
            </h1>
            
            <!-- Navigation -->
            <nav>
                <?php if (isLoggedIn()): ?>
                    <?php 
                    // Récupérer l'avatar de l'utilisateur
                    $user_id = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("SELECT avatar, username FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $current_user = $stmt->fetch();
                    
                    // Construire l'URL de l'avatar
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'];
                    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
                    
                    // Remonter au niveau racine
                    if (strpos($script_dir, '/admin') !== false) {
                        $root_path = dirname($script_dir);
                    } elseif (strpos($script_dir, '/profile') !== false) {
                        $root_path = dirname($script_dir);
                    } else {
                        $root_path = $script_dir;
                    }
                    
                    if ($root_path === '/' || $root_path === '\\') {
                        $site_url = $protocol . $host;
                    } else {
                        $site_url = $protocol . $host . $root_path;
                    }
                    
                    if (!empty($current_user['avatar'])) {
                        $avatar_url = $site_url . '/uploads/avatars/' . htmlspecialchars($current_user['avatar']);
                    } else {
                        $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($current_user['username'] ?? $_SESSION['username']) . '&size=40&background=667eea&color=ffffff&bold=true';
                    }
                    ?>
                    
                    <?php if (isAdmin()): ?>
                        <!-- Menu Admin -->
                        <a href="<?= $base_path ?>admin/index.php">Dashboard</a>
                        <a href="<?= $base_path ?>admin/games.php">Jeux</a>
                        <a href="<?= $base_path ?>admin/teams.php">Équipes</a>
                        <a href="<?= $base_path ?>admin/points.php">Points</a>
                    <?php else: ?>
                        <!-- Menu Utilisateur -->
                        <a href="<?= $base_path ?>index.php">Accueil</a>
                        <a href="<?= $base_path ?>vote.php">Voter</a>
                        <a href="<?= $base_path ?>event.php">🔴 Event Live</a>
                        <a href="<?= $base_path ?>overlay_download.php">📥 Overlay</a>
                    <?php endif; ?>
                    
                    <!-- Profil avec Avatar -->
                    <a href="<?= $base_path ?>profile/index.php" class="profile-link" title="Mon profil">
                        <img src="<?= $avatar_url ?>" alt="Avatar" class="header-avatar">
                        <span class="username"><?= htmlspecialchars($current_user['username'] ?? $_SESSION['username']) ?></span>
                    </a>
                    
                    <!-- Bouton Déconnexion -->
                    <a href="<?= $base_path ?>logout.php" class="btn btn-small btn-secondary">Déconnexion</a>
                    
                <?php else: ?>
                    <!-- Menu Non Connecté -->
                    <a href="<?= $base_path ?>event.php">🔴 Event Live</a>
                    <a href="<?= $base_path ?>login.php">Connexion</a>
                    <a href="<?= $base_path ?>register.php" class="btn btn-small btn-primary">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
<?php include 'spotify_player.php'; ?>
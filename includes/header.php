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
                <a href="<?= $base_path ?>index.php" style="text-decoration: none; color: inherit;">
                    <img src="<?= $base_path ?>images/lanoel.webp" alt="Tournoi Gaming" style="height: 48px; vertical-align: middle;">
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
                    <a href="<?= $base_path ?>login.php">Connexion</a>
                    <a href="<?= $base_path ?>register.php" class="btn btn-small btn-primary">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
<?php include 'spotify_player.php'; ?>
    <style>

        /* === HEADER === */
header {
    background: linear-gradient(135deg, #1a1a1f 0%, #2d2d35 100%);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* === NAVIGATION === */
nav {
    display: flex;
    align-items: center;
    gap: 15px;
}

nav a {
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 8px 15px;
    border-radius: 8px;
    white-space: nowrap;
}

nav a:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

/* === PROFIL AVATAR === */
.profile-link {
    display: inline-flex !important;
    align-items: center;
    gap: 10px;
    padding: 5px 15px 5px 5px !important;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
    border: 2px solid transparent;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.profile-link:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.25) 0%, rgba(118, 75, 162, 0.25) 100%);
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.header-avatar {
    width: 35px !important;
    height: 35px !important;
    min-width: 35px;
    min-height: 35px;
    border-radius: 50% !important;
    object-fit: cover !important;
    border: 2px solid #667eea;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    display: block;
    flex-shrink: 0;
}

.profile-link:hover .header-avatar {
    border-color: #764ba2;
    transform: scale(1.15);
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.5);
}

.username {
    font-weight: 600;
    color: #fff;
    font-size: 14px;
    white-space: nowrap;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .header-avatar {
        width: 30px !important;
        height: 30px !important;
        min-width: 30px;
        min-height: 30px;
    }
    
    .username {
        font-size: 13px;
    }
    
    nav {
        gap: 8px;
    }
}
    </style>
<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Récupérer l'ID de l'utilisateur à afficher
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profile_id === 0) {
    redirect('../index.php');
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.*, t.name as team_name, t.points as team_points
    FROM users u
    LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
    WHERE u.id = ?
");
$stmt->execute([$profile_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('../index.php');
}

// Récupérer les votes de l'utilisateur
$stmt = $pdo->prepare("
    SELECT g.name, g.image, v.created_at
    FROM votes v
    JOIN games g ON v.game_id = g.id
    WHERE v.user_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$profile_id]);
$votes = $stmt->fetchAll();

// Vérifier si c'est le profil de l'utilisateur connecté
$is_own_profile = ($profile_id === $_SESSION['user_id']);

$pageTitle = "Profil de " . htmlspecialchars($user['username']);
include '../includes/header.php';
?>

<div class="container profile-view-container">

    <!-- Header du profil -->
    <div class="profile-view-header">

        <!-- Avatar -->
        <div class="profile-view-avatar-wrapper">
            <?php
            $avatar_url = !empty($user['avatar'])
                ? '../uploads/avatars/' . htmlspecialchars($user['avatar'])
                : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&size=200&background=1d1d1f&color=fff&bold=true';
            ?>
            <img src="<?= $avatar_url ?>"
                 alt="Avatar de <?= htmlspecialchars($user['username']) ?>"
                 class="profile-view-avatar">
        </div>

        <!-- Nom d'utilisateur -->
        <h1 class="profile-view-username">
            <?= htmlspecialchars($user['username']) ?>
            <?php if ($user['is_admin']): ?>
                <span class="profile-view-admin-badge">Admin</span>
            <?php endif; ?>
        </h1>

        <!-- Équipe -->
        <?php if ($user['team_name']): ?>
            <div class="profile-view-team">
                Équipe : <strong class="profile-view-team-name"><?= htmlspecialchars($user['team_name']) ?></strong>
                <span class="profile-view-team-points">(<?= $user['team_points'] ?> points)</span>
            </div>
        <?php else: ?>
            <div class="profile-view-no-team">
                Pas encore d'équipe
            </div>
        <?php endif; ?>

        <!-- Bouton d'édition si c'est son propre profil -->
        <?php if ($is_own_profile): ?>
            <a href="index.php" class="btn btn-primary profile-view-edit-btn">
                Modifier mon profil
            </a>
        <?php endif; ?>
    </div>

    <!-- Statistiques -->
    <div class="profile-view-stats">

        <!-- Total de votes -->
        <div class="profile-view-stat-card">
            <div class="profile-view-stat-number">
                <?= count($votes) ?>
            </div>
            <div class="profile-view-stat-label">
                Vote<?= count($votes) > 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Date d'inscription -->
        <div class="profile-view-stat-card">
            <div class="profile-view-stat-date">
                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
            </div>
            <div class="profile-view-stat-label">
                Inscrit le
            </div>
        </div>

        <!-- Badge équipe -->
        <div class="profile-view-stat-card">
            <div class="profile-view-stat-date">
                <?= $user['team_name'] ? 'En équipe' : 'Solo' ?>
            </div>
            <div class="profile-view-stat-label">
                Statut
            </div>
        </div>
    </div>

    <!-- Votes de l'utilisateur -->
    <div class="profile-view-votes-section">
        <h2 class="profile-view-votes-title">
            Jeux votés
        </h2>

        <?php if (empty($votes)): ?>
            <div class="profile-view-no-votes">
                <p>Aucun vote pour le moment</p>
            </div>
        <?php else: ?>
            <div class="profile-view-votes-grid">
                <?php foreach ($votes as $vote): ?>
                    <div class="profile-view-vote-card">

                        <?php if ($vote['image']): ?>
                            <img src="../uploads/<?= htmlspecialchars($vote['image']) ?>"
                                 alt="<?= htmlspecialchars($vote['name']) ?>"
                                 class="profile-view-vote-image">
                        <?php else: ?>
                            <div class="profile-view-vote-placeholder">
                                🎮
                            </div>
                        <?php endif; ?>

                        <div class="profile-view-vote-info">
                            <div class="profile-view-vote-name">
                                <?= htmlspecialchars($vote['name']) ?>
                            </div>
                            <div class="profile-view-vote-date">
                                <?= date('d/m/Y', strtotime($vote['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bouton retour -->
    <div class="profile-view-back">
        <a href="../index.php" class="btn btn-secondary">
            ← Retour à l'accueil
        </a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

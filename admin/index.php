<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Statistiques
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
$totalUsers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM games");
$totalGames = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM teams");
$totalTeams = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM votes");
$totalVotes = $stmt->fetch()['count'];

$pageTitle = "Dashboard Admin";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">📊 Dashboard Administrateur</h2>
    <p class="section-subtitle">Bienvenue, <?= htmlspecialchars($_SESSION['username']) ?></p>
    
    <div class="teams-grid">
        <div class="team-card">
            <div class="admin-stat-icon">👥</div>
            <div class="team-name">Utilisateurs</div>
            <div class="team-points"><?= $totalUsers ?></div>
        </div>

        <div class="team-card">
            <div class="admin-stat-icon">🎮</div>
            <div class="team-name">Jeux</div>
            <div class="team-points"><?= $totalGames ?></div>
        </div>

        <div class="team-card">
            <div class="admin-stat-icon">🏆</div>
            <div class="team-name">Équipes</div>
            <div class="team-points"><?= $totalTeams ?></div>
        </div>

        <div class="team-card">
            <div class="admin-stat-icon">🗳️</div>
            <div class="team-name">Votes</div>
            <div class="team-points"><?= $totalVotes ?></div>
        </div>
    </div>
    
    <div class="admin-actions-grid">
        <a href="games.php" class="btn btn-primary">Gérer les jeux</a>
        <a href="teams.php" class="btn btn-primary">Gérer les équipes</a>
        <a href="points.php" class="btn btn-primary">Gérer les points</a>
        <a href="roulette.php" class="btn btn-primary">🎰 Roulette - Tirage d'équipes</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

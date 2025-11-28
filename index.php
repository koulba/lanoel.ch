<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Vérifier si les votes sont terminés
$votingClosed = isVotingClosed();

// Récupérer le classement des équipes
$stmt = $pdo->query("
    SELECT t.*, 
           u1.username as player1_name,
           u2.username as player2_name
    FROM teams t
    LEFT JOIN users u1 ON t.player1_id = u1.id
    LEFT JOIN users u2 ON t.player2_id = u2.id
    ORDER BY t.points DESC
");
$teams = $stmt->fetchAll();

// Récupérer les jeux les plus votés (incluant les égalités pour le top 8)
$stmt = $pdo->query("
    SELECT g.*, COUNT(v.id) as vote_count
    FROM games g
    LEFT JOIN votes v ON g.id = v.game_id
    GROUP BY g.id
    ORDER BY vote_count DESC
");
$allGames = $stmt->fetchAll();

// Si les votes sont fermés, on prend les 8 meilleurs avec égalités
if ($votingClosed && !empty($allGames)) {
    $topGames = [];
    $minVotes = null;

    foreach ($allGames as $game) {
        // Prendre les 8 premiers
        if (count($topGames) < 8) {
            $topGames[] = $game;
            $minVotes = $game['vote_count'];
        }
        // Ajouter les jeux à égalité avec le 8ème
        elseif ($game['vote_count'] == $minVotes) {
            $topGames[] = $game;
        }
        // Arrêter si on a dépassé les égalités
        else {
            break;
        }
    }
} else {
    // Mode normal : afficher seulement 8 jeux
    $topGames = array_slice($allGames, 0, 8);
}

$pageTitle = "Accueil";
include 'includes/header.php';
?>

<!-- À l'endroit où tu veux afficher la vidéo sur index.php -->

<div style="display: flex; justify-content: center; margin: 40px 0;">
    <iframe width="560" height="315" src="https://www.youtube.com/embed/rfdHv5440s8" title="YouTube video" frameborder="0" allowfullscreen style="width:100%; max-width:560px;"></iframe>
</div>

<div class="container">
    <h2 class="section-title">🏆 Classement Général</h2>
    <p class="section-subtitle">Les meilleures équipes du tournoi</p>
    
    <?php if (empty($teams)): ?>
        <div class="alert alert-info">Aucune équipe n'a encore été créée.</div>
    <?php else: ?>
       <div class="teams-grid">
    <?php foreach ($teams as $index => $team): ?>
        <div class="team-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 2rem; opacity: 0.3;">#<?= $index + 1 ?></span>
                <?php if ($index === 0): ?>
                    <span style="font-size: 2rem;">🥇</span>
                <?php elseif ($index === 1): ?>
                    <span style="font-size: 2rem;">🥈</span>
                <?php elseif ($index === 2): ?>
                    <span style="font-size: 2rem;">🥉</span>
                <?php endif; ?>
            </div>

            <div class="team-name"><?= htmlspecialchars($team['name']) ?></div>
            <div class="team-points"><?= $team['points'] ?> pts</div>
            
            <!-- ✅ NOMS CLIQUABLES -->
            <div class="team-players">
                <?php if ($team['player1_name'] && $team['player2_name']): ?>
                    👥 
                    <a href="profile/view.php?id=<?= $team['player1_id'] ?>" 
                       style="color: #667eea; text-decoration: none; font-weight: 600; transition: all 0.3s;"
                       onmouseover="this.style.color='#764ba2'; this.style.textDecoration='underline';"
                       onmouseout="this.style.color='#667eea'; this.style.textDecoration='none';">
                        <?= htmlspecialchars($team['player1_name']) ?>
                    </a>
                    & 
                    <a href="profile/view.php?id=<?= $team['player2_id'] ?>" 
                       style="color: #667eea; text-decoration: none; font-weight: 600; transition: all 0.3s;"
                       onmouseover="this.style.color='#764ba2'; this.style.textDecoration='underline';"
                       onmouseout="this.style.color='#667eea'; this.style.textDecoration='none';">
                        <?= htmlspecialchars($team['player2_name']) ?>
                    </a>
                <?php else: ?>
                    ⚠️ Équipe incomplète
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
    <?php endif; ?>

        <!-- Compte à rebours jusqu'au 27/11/2025 -->
<div id="countdown" style="text-align:center; margin:40px 0; font-size:2rem; font-weight:bold;">
    <?php if ($votingClosed): ?>
        <div style="color: #e74c3c;">Les votes sont terminés !</div>
        <div style="font-size: 1.2rem; margin-top: 10px; color: #666;">
            Découvrez ci-dessous le classement final des 8 jeux les plus votés
        </div>
    <?php else: ?>
        Fin des votes 27.11 : <span id="timer"></span>
    <?php endif; ?>
</div>

<?php if (!$votingClosed): ?>
<script>
function updateCountdown() {
    // Date cible : 27 novembre 2025 à 23:59:59
    var endDate = new Date("2025-11-27T23:59:59");
    var now = new Date();
    var diff = endDate - now;

    if (diff <= 0) {
        // Recharger la page pour afficher le classement final
        location.reload();
        return;
    }

    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    var minutes = Math.floor((diff / (1000 * 60)) % 60);
    var seconds = Math.floor((diff / 1000) % 60);

    document.getElementById('timer').innerHTML =
        days + "j " + hours + "h " + minutes + "m " + seconds + "s";
}

window.countdownInterval = setInterval(updateCountdown, 1000);
updateCountdown();
</script>
<?php endif; ?>

<style>
#timer {
    color: red;
}

</style>
    <!-- Update -->
    <h2 class="section-title" style="margin-top: 60px;">
        <?php if ($votingClosed): ?>
            Top 8  des jeux votés
        <?php else: ?>
            Jeux les plus votés
        <?php endif; ?>
    </h2>
    <p class="section-subtitle">
        <?php if ($votingClosed): ?>
            Les <?= count($topGames) ?> jeux sélectionnés pour la Lanoel 2025
            <?php if (count($topGames) > 8): ?>
                <br><span style="color: #e74c3c; font-weight: bold;">⚠️ <?= count($topGames) ?> jeux à égalité pour la 8ème place !</span>
            <?php endif; ?>
        <?php else: ?>
            Les jeux préférés de la communauté des lutins 🎅🏻<br>En cas d'égalité entre 1 ou plusieurs jeux, ils seront tirés au sort en même temps que les équipes
        <?php endif; ?>
    </p>

    <?php if (empty($topGames)): ?>
        <div class="alert alert-info">Aucun jeu n'a encore été ajouté.</div>
    <?php else: ?>
        <div class="games-grid">
            <?php foreach ($topGames as $index => $game): ?>
                <div class="game-card" <?php if ($votingClosed): ?>style="border: 3px solid #ffd700; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);"<?php endif; ?>>
                    <?php if ($votingClosed): ?>
                        <div style="position: absolute; top: 10px; left: 10px; background: #ffd700; color: #000; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 1.1rem; z-index: 10;">
                            #<?= $index + 1 ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($game['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                    <?php else: ?>
                        <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                            🎮
                        </div>
                    <?php endif; ?>

                    <div class="game-card-content">
                        <h3><?= htmlspecialchars($game['name']) ?></h3>
                        <div class="vote-count">
                            <span style="font-size: 1.5rem;">👍</span>
                            <strong><?= $game['vote_count'] ?></strong> vote<?= $game['vote_count'] > 1 ? 's' : '' ?>
                        </div>
                        <?php if ($votingClosed): ?>
                            <div style="margin-top: 10px; padding: 8px; background: #d4edda; color: #155724; border-radius: 5px; font-weight: bold;">
                                ✅ Sélectionné
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <!-- SECTION LISTE DES PARTICIPANTS -->
    <h2 class="section-title" style="margin-top: 60px;">👥 Participants au tournoi</h2>
    <p class="section-subtitle">Découvrez tous les lutins inscrits</p>

     <?php
    // Récupérer tous les utilisateurs SAUF l'admin avec leurs infos d'équipe
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.avatar, u.is_admin, u.created_at,
               t.name as team_name, t.points as team_points,
               (SELECT COUNT(*) FROM votes WHERE user_id = u.id) as vote_count
        FROM users u
        LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
        WHERE u.is_admin = 0
        ORDER BY u.username ASC
    ");
    $participants = $stmt->fetchAll();
    ?>

    <?php if (empty($participants)): ?>
        <div class="alert alert-info">Aucun participant pour le moment.</div>
    <?php else: ?>
        <div class="participants-grid">
            <?php foreach ($participants as $participant): ?>
                <a href="profile/view.php?id=<?= $participant['id'] ?>" class="participant-card">
                    <!-- Avatar -->
                    <div class="participant-avatar">
                        <?php 
                        $avatar_url = !empty($participant['avatar']) 
                            ? 'uploads/avatars/' . htmlspecialchars($participant['avatar'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($participant['username']) . '&size=100&background=667eea&color=fff&bold=true';
                        ?>
                        <img src="<?= $avatar_url ?>" alt="Avatar de <?= htmlspecialchars($participant['username']) ?>">
                        
                        <!-- Badge admin -->
                        <?php if ($participant['is_admin']): ?>
                            <span class="admin-badge">🛡️</span>
                        <?php endif; ?>
                    </div>

                    <!-- Informations -->
                    <div class="participant-info">
                        <h3 class="participant-name">
                            <?= htmlspecialchars($participant['username']) ?>
                        </h3>

                        <!-- Équipe -->
                        <?php if ($participant['team_name']): ?>
                            <div class="participant-team">
                                🏆 <?= htmlspecialchars($participant['team_name']) ?>
                            </div>
                            <div class="participant-points">
                                <?= $participant['team_points'] ?> points
                            </div>
                        <?php else: ?>
                            <div class="participant-no-team">
                                ⚠️ Sans équipe
                            </div>
                        <?php endif; ?>

                        <!-- Votes -->
                        <div class="participant-votes">
                            👍 <?= $participant['vote_count'] ?> vote<?= $participant['vote_count'] > 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <!-- Badge "Mon profil" -->
                    <?php if ($participant['id'] === $_SESSION['user_id']): ?>
                        <div class="you-badge">Mon profil</div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Statistiques globales -->
        <div class="participants-stats">
            <div class="stat-box">
                <div class="stat-number"><?= count($participants) ?></div>
                <div class="stat-label">👥 Participants</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php 
                    $teams_count = count(array_unique(array_filter(array_column($participants, 'team_name'))));
                    echo $teams_count;
                    ?>
                </div>
                <div class="stat-label">🏆 Équipes</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php 
                    $total_votes = array_sum(array_column($participants, 'vote_count'));
                    echo $total_votes;
                    ?>
                </div>
                <div class="stat-label">👍 Votes totaux</div>
            </div>
        </div>
    <?php endif; ?>

</div>
</div>



<?php include 'includes/footer.php'; ?>

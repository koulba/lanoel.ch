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
    // Prendre les 8 premiers jeux
    $topGames = array_slice($allGames, 0, 8);

    if (count($allGames) > 8) {
        // Récupérer le nombre de votes du 8ème jeu
        $eighthGameVotes = $topGames[7]['vote_count'];

        // Vérifier si Mario Kart est à la 8ème position (ou parmi les jeux ayant le même nombre de votes que le 8ème)
        $marioKartIsEighth = false;
        foreach ($topGames as $game) {
            if ($game['vote_count'] == $eighthGameVotes && stripos($game['name'], 'Mario Kart') !== false) {
                $marioKartIsEighth = true;
                break;
            }
        }

        // Si Mario Kart n'est PAS le 8ème, on ajoute les jeux à égalité
        if (!$marioKartIsEighth) {
            // Ajouter tous les jeux suivants qui ont le même nombre de votes que le 8ème
            for ($i = 8; $i < count($allGames); $i++) {
                if ($allGames[$i]['vote_count'] == $eighthGameVotes) {
                    $topGames[] = $allGames[$i];
                } else {
                    break;
                }
            }
        }
        // Si Mario Kart EST le 8ème, on ne garde que lui (on ne fait rien, les 8 premiers sont déjà dans $topGames)
    }
} else {
    // Mode normal : afficher seulement 8 jeux
    $topGames = array_slice($allGames, 0, 8);
}

$pageTitle = "Accueil";
include 'includes/header.php';
?>

<!-- À l'endroit où tu veux afficher la vidéo sur index.php -->

<div class="video-container">
    <iframe width="560" height="315" src="https://www.youtube.com/embed/rfdHv5440s8" title="YouTube video" frameborder="0" allowfullscreen></iframe>
</div>

<div class="container">
        <!-- Compte à rebours jusqu'au 27.12.2025 -->
<div id="countdown-container">
    <div class="countdown-wrapper">
        <?php if ($votingClosed): ?>
            <div class="countdown-title">
                🎄 Les votes sont terminés ! 🎄
            </div>
            <div class="countdown-subtitle">
                Découvrez ci-dessous le classement final des 8 jeux sélectionnés
            </div>
        <?php endif; ?>

        <div class="countdown-event-title">
            LANoël 2025
        </div>
        <div class="countdown-event-dates">
            📅 Du 27 au 28 décembre 2025
        </div>

        <div class="countdown-label-text">
            Début du tournoi dans :
        </div>

        <div id="countdown-timer">
            <div class="countdown-block">
                <div class="countdown-number" id="days">0</div>
                <div class="countdown-label">Jours</div>
            </div>
            <div class="countdown-block">
                <div class="countdown-number" id="hours">0</div>
                <div class="countdown-label">Heures</div>
            </div>
            <div class="countdown-block">
                <div class="countdown-number" id="minutes">0</div>
                <div class="countdown-label">Minutes</div>
            </div>
            <div class="countdown-block">
                <div class="countdown-number" id="seconds">0</div>
                <div class="countdown-label">Secondes</div>
            </div>
        </div>

        <div class="countdown-footer">
            Rendez-vous le 27 décembre à 10h00 ! 🎅
        </div>
    </div>
</div>

<script>
function updateLanoelCountdown() {
    // Date cible : 27 décembre 2025 à 10:00:00
    var eventDate = new Date("2025-12-27T10:00:00");
    var now = new Date();
    var diff = eventDate - now;

    if (diff <= 0) {
        document.getElementById('countdown-timer').innerHTML =
            '<div class="countdown-finished">🎉 C\'EST PARTI ! 🎉</div>';
        return;
    }

    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    var minutes = Math.floor((diff / (1000 * 60)) % 60);
    var seconds = Math.floor((diff / 1000) % 60);

    document.getElementById('days').textContent = days;
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
}

window.lanoelCountdownInterval = setInterval(updateLanoelCountdown, 1000);
updateLanoelCountdown();
</script>

    <h2 class="section-title">Classement Général</h2>
    <p class="section-subtitle">Les meilleures équipes du tournoi</p>

    <?php if (empty($teams)): ?>
        <div class="alert alert-info">Aucune équipe n'a encore été créée.</div>
    <?php else: ?>
       <div class="teams-grid">
    <?php foreach ($teams as $index => $team): ?>
        <div class="team-card">
            <div class="team-card-header">
                <span class="team-ranking-number">#<?= $index + 1 ?></span>
                <?php if ($index === 0): ?>
                    <span class="team-medal">🥇</span>
                <?php elseif ($index === 1): ?>
                    <span class="team-medal">🥈</span>
                <?php elseif ($index === 2): ?>
                    <span class="team-medal">🥉</span>
                <?php endif; ?>
            </div>

            <div class="team-name"><?= htmlspecialchars($team['name']) ?></div>
            <div class="team-points"><?= $team['points'] ?> pts</div>

            <!-- NOMS CLIQUABLES -->
            <div class="team-players">
                <?php if ($team['player1_name'] && $team['player2_name']): ?>
                    👥
                    <a href="profile/view.php?id=<?= $team['player1_id'] ?>" class="team-player-link">
                        <?= htmlspecialchars($team['player1_name']) ?>
                    </a>
                    &
                    <a href="profile/view.php?id=<?= $team['player2_id'] ?>" class="team-player-link">
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

    <!-- Update -->
    <h2 class="section-title section-title-spacing">
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
                <br><span class="games-warning">⚠️ <?= count($topGames) ?> Jeux séléctionés pour 8 places !</span>
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
                <div class="game-card <?php if ($votingClosed): ?>selected<?php endif; ?>">
                    <?php if ($votingClosed): ?>
                        <div class="game-selected-badge">
                            #<?= $index + 1 ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($game['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                    <?php else: ?>
                        <div class="game-placeholder">
                            🎮
                        </div>
                    <?php endif; ?>

                    <div class="game-card-content">
                        <h3><?= htmlspecialchars($game['name']) ?></h3>
                        <div class="vote-count">
                            <span class="game-vote-emoji">👍</span>
                            <strong><?= $game['vote_count'] ?></strong> vote<?= $game['vote_count'] > 1 ? 's' : '' ?>
                        </div>
                        <?php if ($votingClosed): ?>
                            <div class="game-selected-status">
                                ✅ Sélectionné
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <!-- SECTION LISTE DES PARTICIPANTS -->
    <h2 class="section-title section-title-spacing">Participants au tournoi</h2>
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

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
        // Ajouter les jeux à égalité avec le 8ème, SAUF si Mario Kart est déjà dans le top 8
        elseif ($game['vote_count'] == $minVotes) {
            // Vérifier si Mario Kart est déjà dans le top 8
            $marioKartInTop8 = false;
            foreach ($topGames as $topGame) {
                if (stripos($topGame['name'], 'Mario Kart') !== false) {
                    $marioKartInTop8 = true;
                    break;
                }
            }

            // Si Mario Kart est dans le top 8, on garde uniquement Mario Kart parmi les égalités
            if ($marioKartInTop8) {
                if (stripos($game['name'], 'Mario Kart') !== false) {
                    $topGames[] = $game;
                }
                // Sinon on ignore les autres jeux à égalité
            } else {
                // Si Mario Kart n'est pas encore dans le top 8, on garde tous les jeux à égalité
                $topGames[] = $game;
            }
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
    <h2 class="section-title">Classement Général</h2>
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
            
            <!-- NOMS CLIQUABLES -->
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

        <!-- Compte à rebours jusqu'au 27.12.2025 -->
<div id="countdown-container" style="text-align:center; margin:50px 0;">
    <div style="background: #000; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); max-width: 800px; margin: 0 auto; border: 2px solid #333;">
        <?php if ($votingClosed): ?>
            <div style="color: #fff; font-size: 2.5rem; font-weight: bold; margin-bottom: 20px;">
                🎄 Les votes sont terminés ! 🎄
            </div>
            <div style="font-size: 1.3rem; color: #f0f0f0; margin-bottom: 10px;">
                Découvrez ci-dessous le classement final des 8 jeux sélectionnés
            </div>
        <?php endif; ?>

        <div style="color: #fff; font-size: 1.8rem; margin-bottom: 15px;">
            🎮 La Lanoel 2025 🎮
        </div>
        <div style="color: #f0f0f0; font-size: 1.2rem; margin-bottom: 25px;">
            📅 Du 27 au 28 décembre 2025
        </div>

        <div style="color: #ffd700; font-size: 1.3rem; margin-bottom: 10px; font-weight: bold;">
            Début du tournoi dans :
        </div>

        <div id="countdown-timer" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
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

        <div style="color: #f0f0f0; font-size: 1rem; margin-top: 20px; opacity: 0.9;">
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
            '<div style="font-size: 2.5rem; color: #ffd700; font-weight: bold;">🎉 C\'EST PARTI ! 🎉</div>';
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

<style>
.countdown-block {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    min-width: 100px;
    transition: all 0.3s ease;
}

.countdown-block:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.countdown-number {
    font-size: 3rem;
    font-weight: bold;
    color: #ffd700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    margin-bottom: 5px;
}

.countdown-label {
    font-size: 1rem;
    color: #f0f0f0;
    text-transform: uppercase;
    letter-spacing: 2px;
}

@media (max-width: 768px) {
    .countdown-number {
        font-size: 2rem;
    }

    .countdown-block {
        min-width: 70px;
        padding: 15px;
    }

    #countdown-container > div {
        padding: 30px 20px !important;
    }
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
                <br><span style="color: #e74c3c; font-weight: bold;">⚠️ <?= count($topGames) ?> Jeux séléctionés pour 8 places !</span>
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
    <h2 class="section-title" style="margin-top: 60px;">Participants au tournoi</h2>
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

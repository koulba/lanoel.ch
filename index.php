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

// Valeurs max pour les barres de progression
$maxTeamPoints = 0;
foreach ($teams as $t) { $maxTeamPoints = max($maxTeamPoints, (int)$t['points']); }
$maxVotes = 0;
foreach ($topGames as $g) { $maxVotes = max($maxVotes, (int)$g['vote_count']); }

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

$pageTitle = "Accueil";
$showLoader = true;
include 'includes/header.php';

// Découpe le titre en lettres animées
function heroLetters($text, &$index) {
    $out = '';
    foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
        $out .= '<span class="ch" style="--i:' . $index++ . '">' . htmlspecialchars($ch) . '</span>';
    }
    return $out;
}
$letterIndex = 0;
?>

<main class="stack">

    <!-- Compte à rebours -->
    <section class="panel hero" id="hero">
        <canvas class="snow" id="snow" aria-hidden="true"></canvas>
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

                <h1 class="hero-title countdown-event-title" aria-label="LANoël 2026">
                    <span class="grad"><?= heroLetters('LANoël', $letterIndex) ?></span>
                    <?php $letterIndex++; ?>
                    <span class="year"><?= heroLetters('2026', $letterIndex) ?></span>
                </h1>
                <div class="countdown-event-dates">
                    📅 Du 27 au 28 décembre 2026
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
    </section>

    <script>
    function updateLanoelCountdown() {
        // Date cible : 27 décembre 2026 à 10:00:00
        var eventDate = new Date("2026-12-27T10:00:00");
        var now = new Date();
        var diff = eventDate - now;

        if (diff <= 0) {
            document.getElementById('countdown-timer').innerHTML =
                '<div class="countdown-finished">🎉 C\'EST PARTI ! 🎉</div>';
            clearInterval(window.lanoelCountdownInterval);
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

    <!-- Classement -->
    <section class="panel panel-pad">
        <h2 class="section-title"><span class="grad">Classement</span> Général</h2>
        <p class="section-subtitle">Les meilleures équipes du tournoi</p>

        <?php if (empty($teams)): ?>
            <div class="alert alert-info">Aucune équipe n'a encore été créée.</div>
        <?php else: ?>
            <?php
            $podiumTeams = array_slice($teams, 0, 3);
            $otherTeams = array_slice($teams, 3);
            $podiumClasses = ['gold', 'silver', 'bronze'];
            $medals = ['🥇', '🥈', '🥉'];
            ?>
            <div class="rank-grid <?= empty($otherTeams) ? 'solo' : '' ?>">
                <div class="podium <?= empty($otherTeams) ? 'teams-grid' : '' ?>">
                    <?php foreach ($podiumTeams as $index => $team): ?>
                        <div class="team-card <?= $podiumClasses[$index] ?>">
                            <div class="team-card-header">
                                <span class="team-ranking-number">#<?= $index + 1 ?></span>
                                <span class="team-medal"><?= $medals[$index] ?></span>
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

                <?php if (!empty($otherTeams)): ?>
                <div class="panel board">
                    <?php foreach ($otherTeams as $i => $team):
                        $pct = $maxTeamPoints > 0 ? round((int)$team['points'] / $maxTeamPoints * 100) : 0;
                    ?>
                        <div class="rank-item">
                            <span class="rank-position">#<?= $i + 4 ?></span>
                            <span class="rank-name">
                                <?= htmlspecialchars($team['name']) ?>
                                <span class="team-players">
                                    <?php if ($team['player1_name'] && $team['player2_name']): ?>
                                        <a href="profile/view.php?id=<?= $team['player1_id'] ?>" class="team-player-link"><?= htmlspecialchars($team['player1_name']) ?></a>
                                        &
                                        <a href="profile/view.php?id=<?= $team['player2_id'] ?>" class="team-player-link"><?= htmlspecialchars($team['player2_name']) ?></a>
                                    <?php else: ?>
                                        ⚠️ Équipe incomplète
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="rank-points"><?= $team['points'] ?><span>pts</span></span>
                            <span class="bar" style="--w:<?= $pct ?>%"><i></i></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Jeux -->
    <section class="panel panel-pad">
        <h2 class="section-title">
            <?php if ($votingClosed): ?>
                <span class="grad">Top 8</span> des jeux votés
            <?php else: ?>
                Jeux les <span class="grad">plus votés</span>
            <?php endif; ?>
        </h2>
        <p class="section-subtitle">
            <?php if ($votingClosed): ?>
                Les <?= count($topGames) ?> jeux sélectionnés pour la Lanoel 2026
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
                <?php foreach ($topGames as $index => $game):
                    $pct = $maxVotes > 0 ? round((int)$game['vote_count'] / $maxVotes * 100) : 0;
                ?>
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
                            <div class="vbar" style="--w:<?= $pct ?>%"><i></i></div>
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
    </section>

    <!-- Participants -->
    <section class="panel panel-pad">
        <h2 class="section-title">Participants <span class="grad">au tournoi</span></h2>
        <p class="section-subtitle">Découvrez tous les lutins inscrits</p>

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
                                : 'https://ui-avatars.com/api/?name=' . urlencode($participant['username']) . '&size=100&background=ff2e2e&color=1a0000&bold=true';
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
            <?php
            $teams_count = count(array_unique(array_filter(array_column($participants, 'team_name'))));
            $total_votes = array_sum(array_column($participants, 'vote_count'));
            ?>
            <div class="participants-stats">
                <div class="stat-box">
                    <div class="stat-number" data-count="<?= count($participants) ?>"><?= count($participants) ?></div>
                    <div class="stat-label">👥 Participants</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" data-count="<?= $teams_count ?>"><?= $teams_count ?></div>
                    <div class="stat-label">🏆 Équipes</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" data-count="<?= $total_votes ?>"><?= $total_votes ?></div>
                    <div class="stat-label">👍 Votes totaux</div>
                </div>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php include 'includes/footer.php'; ?>

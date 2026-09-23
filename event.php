<?php
require_once 'config/database.php';

// Récupérer le classement des équipes
$stmt = $pdo->query("SELECT name, points FROM teams ORDER BY points DESC");
$teams = $stmt->fetchAll();

$pageTitle = "Lanoel2025";
include 'includes/header.php';
?>


<div class="event-container">
    <div class="event-header">
        <h1>LANOEL 2025</h1>
        <p>Suivez l'événement en temps réel avec le stream, les règles et le classement</p>
    </div>

    <!-- Vidéos -->
    <div class="content-grid">
        <div class="video-section">
            <h2>📺 Stream en Direct</h2>
            <div class="video-wrapper">
                <iframe
                    src="https://player.twitch.tv/?channel=vexatwitch&parent=<?= $_SERVER['HTTP_HOST'] ?>"
                    frameborder="0"
                    allowfullscreen="true"
                    scrolling="no">
                </iframe>
            </div>
        </div>

        <div class="video-section">
            <h2>📖 Règles du Jeu</h2>
            <div class="video-wrapper">
                <iframe
                    src="https://www.youtube.com/embed/rfdHv5440s8"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
        </div>
    </div>

    <!-- Programme -->
    <div class="schedule-section">
        <h2>🕐 Programme de l'Événement</h2>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Jeu</th>
                    <th>Heure de début</th>
                    <th>Heure de fin</th>
                    <th>Durée</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $schedule = [
                    // Jour 1 - 27.12.2025
                    ['game' => "Gentlemen's Dispute", 'start' => '14h00', 'end' => '15h30', 'day' => 1],
                    ['game' => 'Codenames', 'start' => '15h30', 'end' => '17h30', 'day' => 1],
                    ['game' => 'Mage Arena', 'start' => '17h30', 'end' => '19h00', 'day' => 1],
                    ['game' => 'Mario Kart', 'start' => '19h00', 'end' => '20h00', 'day' => 1],
                    ['game' => 'Fall Guys', 'start' => '21h00', 'end' => '22h30', 'day' => 1],
                    ['game' => 'Trackmania', 'start' => '22h30', 'end' => '00h00', 'day' => 1],
                    ['game' => 'Skribbl.io', 'start' => '00h00', 'end' => '02h00', 'day' => 2],

                    // Jour 2 - 28.12.2025
                    ['game' => 'BAPBAP', 'start' => '10h00', 'end' => '11h00', 'day' => 2],
                    ['game' => 'GeoGuessr', 'start' => '11h00', 'end' => '12h00', 'day' => 2],
                    ['game' => 'BIPED 2', 'start' => '13h00', 'end' => '19h00', 'day' => 2],
                ];

                // Fonction pour calculer la durée
                function calculateDuration($start, $end) {
                    $start_minutes = intval(substr($start, 0, 2)) * 60 + intval(substr($start, 3, 2));
                    $end_minutes = intval(substr($end, 0, 2)) * 60 + intval(substr($end, 3, 2));

                    if ($end_minutes < $start_minutes) {
                        $end_minutes += 24 * 60; // Ajouter 24h si le jeu se termine le lendemain
                    }

                    $duration = $end_minutes - $start_minutes;
                    $hours = floor($duration / 60);
                    $minutes = $duration % 60;

                    if ($minutes > 0) {
                        return $hours . 'h' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
                    }
                    return $hours . 'h00';
                }

                // Fonction pour vérifier si c'est le jeu en cours
                function isCurrentGame($start, $end, $day) {
                    $now = new DateTime();

                    // Date de début de l'événement (27.12.2025)
                    $eventStartDate = new DateTime('2025-12-27');

                    // Calculer le jour actuel par rapport au début de l'événement
                    $currentDay = 1;
                    if ($now->format('Y-m-d') === '2025-12-27') {
                        $currentDay = 1;
                    } elseif ($now->format('Y-m-d') === '2025-12-28') {
                        $currentDay = 2;
                    } else {
                        // Si on n'est pas dans les dates de l'événement, rien n'est en cours
                        return false;
                    }

                    // Si ce n'est pas le bon jour, ce n'est pas le jeu en cours
                    if ($day !== $currentDay) {
                        return false;
                    }

                    // Convertir en minutes
                    $current_minutes = intval($now->format('H')) * 60 + intval($now->format('i'));
                    $start_minutes = intval(substr($start, 0, 2)) * 60 + intval(substr($start, 3, 2));
                    $end_minutes = intval(substr($end, 0, 2)) * 60 + intval(substr($end, 3, 2));

                    if ($end_minutes < $start_minutes) {
                        // Le jeu se termine le lendemain (ex: 22h30 -> 00h00)
                        return $current_minutes >= $start_minutes || $current_minutes < $end_minutes;
                    }

                    return $current_minutes >= $start_minutes && $current_minutes < $end_minutes;
                }

                foreach ($schedule as $item):
                    $duration = calculateDuration($item['start'], $item['end']);
                    $isCurrent = isCurrentGame($item['start'], $item['end'], $item['day']);
                    $dayLabel = $item['day'] === 2 ? ' <span style="color: var(--primary); font-size: 0.8em;">(Jour 2)</span>' : '';
                ?>
                    <tr class="<?= $isCurrent ? 'current-game' : '' ?>">
                        <td class="game-name"><?= htmlspecialchars($item['game']) ?><?= $dayLabel ?></td>
                        <td class="time-slot"><?= $item['start'] ?></td>
                        <td class="time-slot"><?= $item['end'] ?></td>
                        <td class="time-slot"><?= $duration ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Classement -->
    <div class="ranking-section">
        <h2>🏆 Classement en Direct</h2>
        <div class="ranking-list">
            <?php
            $position = 1;
            $medals = ['🥇', '🥈', '🥉'];
            $classes = ['first', 'second', 'third'];

            foreach ($teams as $team):
                $medal = isset($medals[$position - 1]) ? $medals[$position - 1] : "#$position";
                $class = isset($classes[$position - 1]) ? $classes[$position - 1] : '';
            ?>
                <div class="rank-item <?= $class ?>">
                    <div class="rank-position"><?= $medal ?></div>
                    <div class="rank-name"><?= htmlspecialchars($team['name']) ?></div>
                    <div class="rank-points">
                        <?= $team['points'] ?><span>pts</span>
                    </div>
                </div>
            <?php
                $position++;
            endforeach;
            ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh du classement toutes les 30 secondes (sans recharger la page entière)
function updateRanking() {
    fetch('event_ranking.php')
        .then(response => response.text())
        .then(html => {
            const rankingContainer = document.querySelector('.ranking-list');
            if (rankingContainer) {
                rankingContainer.innerHTML = html;
            }
        })
        .catch(error => console.error('Erreur lors du rafraîchissement:', error));
}

// Rafraîchir toutes les 30 secondes
setInterval(updateRanking, 30000);
</script>

<?php include 'includes/footer.php'; ?>

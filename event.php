<?php
require_once 'config/database.php';

// Récupérer le classement des équipes
$stmt = $pdo->query("SELECT name, points FROM teams ORDER BY points DESC");
$teams = $stmt->fetchAll();

$pageTitle = "Lanoel2025";
include 'includes/header.php';
?>

<style>
.event-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.event-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 20px;
    background: linear-gradient(135deg, var(--primary) 0%, #667eea 100%);
    border-radius: 12px;
    color: white;
}

.event-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.event-header p {
    font-size: 1.2rem;
    opacity: 0.95;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.video-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.video-section h2 {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 10px;
}

.video-wrapper {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    border-radius: 8px;
    background: #000;
}

.video-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.schedule-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.schedule-section h2 {
    margin-bottom: 20px;
    font-size: 1.8rem;
    color: var(--primary);
    text-align: center;
}

.schedule-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--bg);
    border-radius: 8px;
    overflow: hidden;
}

.schedule-table thead {
    background: var(--primary);
    color: white;
}

.schedule-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.schedule-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border);
}

.schedule-table tbody tr:hover {
    background: rgba(124, 58, 237, 0.05);
}

.schedule-table tbody tr:last-child td {
    border-bottom: none;
}

.game-name {
    font-weight: 600;
    color: var(--text);
}

.time-slot {
    color: var(--gray);
    font-size: 0.95rem;
}

.ranking-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 40px;
}

.ranking-section h2 {
    margin-bottom: 20px;
    font-size: 1.8rem;
    color: var(--primary);
    text-align: center;
}

.ranking-list {
    display: grid;
    gap: 15px;
}

.rank-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background: var(--bg);
    border-radius: 8px;
    border-left: 4px solid transparent;
    transition: all 0.3s;
}

.rank-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.rank-item.first {
    border-left-color: #FFD700;
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 0%, var(--bg) 100%);
}

.rank-item.second {
    border-left-color: #C0C0C0;
    background: linear-gradient(90deg, rgba(192, 192, 192, 0.1) 0%, var(--bg) 100%);
}

.rank-item.third {
    border-left-color: #CD7F32;
    background: linear-gradient(90deg, rgba(205, 127, 50, 0.1) 0%, var(--bg) 100%);
}

.rank-position {
    font-size: 2rem;
    font-weight: 700;
    min-width: 60px;
}

.rank-name {
    flex: 1;
    font-size: 1.2rem;
    font-weight: 600;
}

.rank-points {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.rank-points span {
    font-size: 0.9rem;
    color: var(--gray);
    margin-left: 5px;
}

@media (max-width: 968px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .event-header h1 {
        font-size: 2rem;
    }

    .rank-position {
        font-size: 1.5rem;
        min-width: 50px;
    }

    .rank-name {
        font-size: 1rem;
    }

    .rank-points {
        font-size: 1.2rem;
    }
}

.current-game {
    background: linear-gradient(90deg, rgba(124, 58, 237, 0.2) 0%, var(--bg) 100%);
    border-left: 4px solid var(--primary);
}

.current-game .game-name::before {
    content: "🔴 ";
}
</style>

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
// Auto-refresh du classement toutes les 30 secondes
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Ajouter des points
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_points'])) {
    $scoringMode = $_POST['scoring_mode'] ?? 'manual';

    // Récupérer les bonnes valeurs selon le mode
    if ($scoringMode === 'preset') {
        $teamId = $_POST['team_id'] ?? null;
        $gameId = $_POST['game_id'] ?? null;
    } else {
        $teamId = $_POST['manual_team_id'] ?? null;
        $gameId = $_POST['manual_game_id'] ?? null;
    }

    $position = isset($_POST['position']) ? intval($_POST['position']) : null;

    if ($scoringMode === 'preset' && $position && $gameId && $teamId) {
        // Mode barème : récupérer les points selon la position
        $stmt = $pdo->prepare("
            SELECT scoring_mode FROM games WHERE id = ?
        ");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        // Récupérer les points du barème
        $stmt = $pdo->prepare("
            SELECT points FROM scoring_preset_details
            WHERE preset_id = 1 AND position = ?
        ");
        $stmt->execute([$position]);
        $presetPoints = $stmt->fetch();

        if ($presetPoints && $game) {
            $pointsToAdd = $presetPoints['points'];

            if ($game['scoring_mode'] === 'individual') {
                // Mode individuel : demander les points de chaque joueur
                $player1Points = isset($_POST['player1_points']) ? intval($_POST['player1_points']) : 0;
                $player2Points = isset($_POST['player2_points']) ? intval($_POST['player2_points']) : 0;
                $pointsToAdd = $player1Points + $player2Points;

                // Enregistrer avec détails individuels
                $stmt = $pdo->prepare("UPDATE teams SET points = points + ? WHERE id = ?");
                $stmt->execute([$pointsToAdd, $teamId]);

                $stmt = $pdo->prepare("
                    INSERT INTO points_history (team_id, game_id, points, player1_points, player2_points, position, reason)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$teamId, $gameId, $pointsToAdd, $player1Points, $player2Points, $position, "Position $position"]);
            } else {
                // Mode équipe : ajouter directement les points
                $stmt = $pdo->prepare("UPDATE teams SET points = points + ? WHERE id = ?");
                $stmt->execute([$pointsToAdd, $teamId]);

                $stmt = $pdo->prepare("
                    INSERT INTO points_history (team_id, game_id, points, position, reason)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$teamId, $gameId, $pointsToAdd, $position, "Position $position"]);
            }

            $success = "Points ajoutés avec succès ! (+$pointsToAdd pts)";
        }
    } elseif ($scoringMode === 'manual') {
        // Mode manuel
        $points = intval($_POST['manual_points'] ?? 0);
        $reason = $_POST['reason'] ?? '';

        if ($teamId && $points != 0) {
            // Ajouter les points à l'équipe
            $stmt = $pdo->prepare("UPDATE teams SET points = points + ? WHERE id = ?");
            $stmt->execute([$points, $teamId]);

            // Enregistrer dans l'historique
            $stmt = $pdo->prepare("INSERT INTO points_history (team_id, game_id, points, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$teamId, $gameId, $points, $reason]);

            $success = "Points ajoutés avec succès !";
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
        }
    }
}

// Supprimer une entrée de l'historique et ajuster les points
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Récupérer l'entrée
    $stmt = $pdo->prepare("SELECT team_id, points FROM points_history WHERE id = ?");
    $stmt->execute([$id]);
    $entry = $stmt->fetch();
    
    if ($entry) {
        // Retirer les points
        $stmt = $pdo->prepare("UPDATE teams SET points = points - ? WHERE id = ?");
        $stmt->execute([$entry['points'], $entry['team_id']]);
        
        // Supprimer l'entrée
        $stmt = $pdo->prepare("DELETE FROM points_history WHERE id = ?");
        $stmt->execute([$id]);
        
        $success = "Entrée supprimée et points ajustés !";
    }
}

// Récupérer toutes les équipes
$stmt = $pdo->query("SELECT id, name, points FROM teams ORDER BY name");
$teams = $stmt->fetchAll();

// Récupérer tous les jeux avec leur mode de scoring
$stmt = $pdo->query("SELECT id, name, scoring_mode FROM games ORDER BY name");
$games = $stmt->fetchAll();

// Récupérer le barème de points
$stmt = $pdo->query("
    SELECT position, points
    FROM scoring_preset_details
    WHERE preset_id = 1
    ORDER BY position
");
$preset = $stmt->fetchAll();

// Récupérer les joueurs de chaque équipe pour le mode individuel
$stmt = $pdo->query("
    SELECT t.id as team_id,
           u1.username as player1_name,
           u2.username as player2_name
    FROM teams t
    LEFT JOIN users u1 ON t.player1_id = u1.id
    LEFT JOIN users u2 ON t.player2_id = u2.id
");
$teamPlayers = [];
while ($row = $stmt->fetch()) {
    $teamPlayers[$row['team_id']] = [
        'player1' => $row['player1_name'],
        'player2' => $row['player2_name']
    ];
}

// Récupérer l'historique des points
$stmt = $pdo->query("
    SELECT ph.*, t.name as team_name, g.name as game_name,
           u1.username as player1_name, u2.username as player2_name
    FROM points_history ph
    LEFT JOIN teams t ON ph.team_id = t.id
    LEFT JOIN games g ON ph.game_id = g.id
    LEFT JOIN users u1 ON t.player1_id = u1.id
    LEFT JOIN users u2 ON t.player2_id = u2.id
    ORDER BY ph.created_at DESC
    LIMIT 50
");
$history = $stmt->fetchAll();

$pageTitle = "Gestion des points";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <div class="admin-header">
        <h1>⚡ Gestion des Points</h1>
        <a href="index.php" class="btn-secondary">← Retour</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire d'ajout de points -->
    <div class="card">
        <h2>Ajouter des points</h2>

        <!-- Tabs pour choisir le mode -->
        <div style="margin-bottom: 20px; border-bottom: 2px solid var(--border);">
            <button type="button" class="tab-btn active" onclick="switchMode('preset')" id="presetTab">
                🎯 Avec barème
            </button>
            <button type="button" class="tab-btn" onclick="switchMode('manual')" id="manualTab">
                ✏️ Saisie manuelle
            </button>
        </div>

        <form method="POST" class="form" id="pointsForm">
            <input type="hidden" name="scoring_mode" id="scoringMode" value="preset">

            <!-- Mode Barème -->
            <div id="presetMode">
                <div class="form-group">
                    <label>Jeu *</label>
                    <select name="game_id" id="gameSelect" required onchange="updateScoringInfo()">
                        <option value="">Sélectionner un jeu</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?= $game['id'] ?>" data-mode="<?= $game['scoring_mode'] ?>">
                                <?= htmlspecialchars($game['name']) ?>
                                <?= $game['scoring_mode'] === 'individual' ? ' (Individuel)' : ' (Équipe)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Équipe *</label>
                    <select name="team_id" id="teamSelect" required onchange="updatePlayerInfo()">
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>"
                                    data-player1="<?= htmlspecialchars($teamPlayers[$team['id']]['player1'] ?? '') ?>"
                                    data-player2="<?= htmlspecialchars($teamPlayers[$team['id']]['player2'] ?? '') ?>">
                                <?= htmlspecialchars($team['name']) ?> (<?= $team['points'] ?> pts)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Position *</label>
                    <select name="position" id="positionSelect" required onchange="updatePointsPreview()">
                        <option value="">Sélectionner une position</option>
                        <?php foreach ($preset as $p): ?>
                            <option value="<?= $p['position'] ?>" data-points="<?= $p['points'] ?>">
                                <?php
                                $medals = ['🥇', '🥈', '🥉'];
                                echo isset($medals[$p['position']-1]) ? $medals[$p['position']-1] . ' ' : '';
                                ?>
                                <?= $p['position'] ?><?= $p['position'] == 1 ? 'er' : 'ème' ?> place - <?= $p['points'] ?> points
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Zone pour le mode individuel -->
                <div id="individualMode" style="display: none;">
                    <div class="alert" style="background: #e3f2fd; border-left: 4px solid #2196F3; margin: 15px 0;">
                        <strong>Mode Individuel</strong>
                        <p style="margin: 5px 0;">Saisissez la position de chaque joueur. Les points seront additionnés pour l'équipe.</p>
                    </div>

                    <div id="playerInputs"></div>

                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <strong>Total pour l'équipe : <span id="totalPoints" style="color: #4CAF50; font-size: 1.3em;">0</span> points</strong>
                    </div>
                </div>

                <!-- Zone pour le mode équipe -->
                <div id="teamMode" style="display: none;">
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <strong>Points à ajouter : <span id="teamPoints" style="color: #4CAF50; font-size: 1.3em;">0</span> points</strong>
                    </div>
                </div>
            </div>

            <!-- Mode Manuel -->
            <div id="manualMode" style="display: none;">
                <div class="form-group">
                    <label>Équipe *</label>
                    <select name="manual_team_id" id="manualTeamId">
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['id'] ?>">
                                <?= htmlspecialchars($team['name']) ?> (<?= $team['points'] ?> pts)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jeu (optionnel)</label>
                    <select name="manual_game_id" id="manualGameId">
                        <option value="">Aucun jeu spécifique</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?= $game['id'] ?>">
                                <?= htmlspecialchars($game['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Points * (utilisez un nombre négatif pour retirer)</label>
                    <input type="number" name="manual_points" id="manualPoints" placeholder="Ex: 10 ou -5">
                    <small>Entrez un nombre positif pour ajouter, négatif pour retirer</small>
                </div>

                <div class="form-group">
                    <label>Raison (optionnel)</label>
                    <input type="text" name="reason" placeholder="Ex: Bonus spécial">
                </div>
            </div>

            <button type="submit" name="add_points" class="btn-primary" style="width: 100%; margin-top: 20px;">
                💾 Enregistrer les points
            </button>
        </form>
    </div>

    <style>
    .tab-btn {
        background: none;
        border: none;
        padding: 12px 24px;
        cursor: pointer;
        font-size: 1rem;
        color: var(--gray);
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .tab-btn:hover {
        color: var(--text);
    }
    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        font-weight: 600;
    }
    </style>

    <script>
    const presetData = <?= json_encode($preset) ?>;
    const teamPlayersData = <?= json_encode($teamPlayers) ?>;

    function switchMode(mode) {
        const presetMode = document.getElementById('presetMode');
        const manualMode = document.getElementById('manualMode');
        const presetTab = document.getElementById('presetTab');
        const manualTab = document.getElementById('manualTab');
        const scoringMode = document.getElementById('scoringMode');

        // Champs du mode barème
        const gameSelect = document.getElementById('gameSelect');
        const teamSelect = document.getElementById('teamSelect');
        const positionSelect = document.getElementById('positionSelect');

        if (mode === 'preset') {
            presetMode.style.display = 'block';
            manualMode.style.display = 'none';
            presetTab.classList.add('active');
            manualTab.classList.remove('active');
            scoringMode.value = 'preset';

            // Activer la validation pour le mode barème
            gameSelect.setAttribute('required', 'required');
            teamSelect.setAttribute('required', 'required');
            positionSelect.setAttribute('required', 'required');
        } else {
            presetMode.style.display = 'none';
            manualMode.style.display = 'block';
            presetTab.classList.remove('active');
            manualTab.classList.add('active');
            scoringMode.value = 'manual';

            // Désactiver la validation pour le mode barème
            gameSelect.removeAttribute('required');
            teamSelect.removeAttribute('required');
            positionSelect.removeAttribute('required');
        }
    }

    function updateScoringInfo() {
        const gameSelect = document.getElementById('gameSelect');
        const selectedOption = gameSelect.options[gameSelect.selectedIndex];
        const mode = selectedOption.dataset.mode;

        const individualMode = document.getElementById('individualMode');
        const teamMode = document.getElementById('teamMode');

        if (mode === 'individual') {
            individualMode.style.display = 'block';
            teamMode.style.display = 'none';
            updatePlayerInfo();
        } else {
            individualMode.style.display = 'none';
            teamMode.style.display = 'block';
        }

        updatePointsPreview();
    }

    function updatePlayerInfo() {
        const teamSelect = document.getElementById('teamSelect');
        const selectedOption = teamSelect.options[teamSelect.selectedIndex];
        const player1 = selectedOption.dataset.player1;
        const player2 = selectedOption.dataset.player2;

        const playerInputs = document.getElementById('playerInputs');
        playerInputs.innerHTML = `
            <div class="form-group">
                <label>${player1 || 'Joueur 1'} - Position</label>
                <select name="player1_position" id="player1Position" onchange="calculateIndividualPoints()">
                    <option value="">Sélectionner</option>
                    ${presetData.map(p => `<option value="${p.position}" data-points="${p.points}">${p.position}${p.position == 1 ? 'er' : 'ème'} - ${p.points} pts</option>`).join('')}
                </select>
                <input type="hidden" name="player1_points" id="player1Points" value="0">
            </div>
            <div class="form-group">
                <label>${player2 || 'Joueur 2'} - Position</label>
                <select name="player2_position" id="player2Position" onchange="calculateIndividualPoints()">
                    <option value="">Sélectionner</option>
                    ${presetData.map(p => `<option value="${p.position}" data-points="${p.points}">${p.position}${p.position == 1 ? 'er' : 'ème'} - ${p.points} pts</option>`).join('')}
                </select>
                <input type="hidden" name="player2_points" id="player2Points" value="0">
            </div>
        `;
    }

    function calculateIndividualPoints() {
        const player1Select = document.getElementById('player1Position');
        const player2Select = document.getElementById('player2Position');

        const player1Points = player1Select.selectedIndex > 0 ?
            parseInt(player1Select.options[player1Select.selectedIndex].dataset.points) : 0;
        const player2Points = player2Select.selectedIndex > 0 ?
            parseInt(player2Select.options[player2Select.selectedIndex].dataset.points) : 0;

        document.getElementById('player1Points').value = player1Points;
        document.getElementById('player2Points').value = player2Points;

        const total = player1Points + player2Points;
        document.getElementById('totalPoints').textContent = total;
    }

    function updatePointsPreview() {
        const gameSelect = document.getElementById('gameSelect');
        const selectedGame = gameSelect.options[gameSelect.selectedIndex];
        const mode = selectedGame.dataset.mode;

        if (mode === 'team') {
            const positionSelect = document.getElementById('positionSelect');
            const selectedPosition = positionSelect.options[positionSelect.selectedIndex];
            const points = selectedPosition.dataset.points || 0;
            document.getElementById('teamPoints').textContent = points;
        }
    }

    // Validation du formulaire avant soumission
    document.getElementById('pointsForm').addEventListener('submit', function(e) {
        const scoringMode = document.getElementById('scoringMode').value;

        if (scoringMode === 'preset') {
            // Mode barème : vérifier les champs requis
            const gameId = document.getElementById('gameSelect').value;
            const teamId = document.getElementById('teamSelect').value;
            const position = document.getElementById('positionSelect').value;

            if (!gameId || !teamId || !position) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs obligatoires (Jeu, Équipe, Position).');
                return false;
            }
        } else {
            // Mode manuel : vérifier les champs requis
            const teamId = document.getElementById('manualTeamId').value;
            const points = document.getElementById('manualPoints').value;

            if (!teamId || !points || points == '0') {
                e.preventDefault();
                alert('Veuillez sélectionner une équipe et entrer un nombre de points (différent de 0).');
                return false;
            }
        }
    });
    </script>

    <!-- Classement actuel -->
    <div class="card">
        <h2>🏆 Classement actuel</h2>
        <?php if (count($teams) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Équipe</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Trier par points décroissants
                    usort($teams, function($a, $b) {
                        return $b['points'] - $a['points'];
                    });
                    
                    $position = 1;
                    foreach ($teams as $team): 
                    ?>
                        <tr>
                            <td>
                                <?php if ($position == 1): ?>
                                    🥇
                                <?php elseif ($position == 2): ?>
                                    🥈
                                <?php elseif ($position == 3): ?>
                                    🥉
                                <?php else: ?>
                                    <?= $position ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($team['name']) ?></td>
                            <td><strong><?= $team['points'] ?></strong> pts</td>
                        </tr>
                    <?php 
                        $position++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">Aucune équipe créée</p>
        <?php endif; ?>
    </div>

    <!-- Historique -->
    <div class="card">
        <h2>📜 Historique des points (50 dernières entrées)</h2>
        <?php if (count($history) > 0): ?>
            <table class="table">
<thead>
                    <tr>
                        <th>Date</th>
                        <th>Équipe</th>
                        <th>Jeu</th>
                        <th>Points</th>
                        <th>Détails</th>
                        <th>Raison</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></td>
                            <td><?= htmlspecialchars($entry['team_name']) ?></td>
                            <td><?= $entry['game_name'] ? htmlspecialchars($entry['game_name']) : '-' ?></td>
                            <td>
                                <span class="<?= $entry['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <strong><?= $entry['points'] > 0 ? '+' : '' ?><?= $entry['points'] ?></strong>
                                </span>
                            </td>
                            <td>
                                <?php if ($entry['player1_points'] || $entry['player2_points']): ?>
                                    <small style="color: var(--gray);">
                                        <?= htmlspecialchars($entry['player1_name']) ?>: <?= $entry['player1_points'] ?> pts<br>
                                        <?= htmlspecialchars($entry['player2_name']) ?>: <?= $entry['player2_points'] ?> pts
                                    </small>
                                <?php elseif ($entry['position']): ?>
                                    <small style="color: var(--gray);">
                                        <?php
                                        $medals = ['🥇', '🥈', '🥉'];
                                        echo isset($medals[$entry['position']-1]) ? $medals[$entry['position']-1] . ' ' : '';
                                        ?>
                                        <?= $entry['position'] ?><?= $entry['position'] == 1 ? 'er' : 'ème' ?>
                                    </small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= $entry['reason'] ? htmlspecialchars($entry['reason']) : '-' ?></td>
                            <td>
                                <a href="?delete=<?= $entry['id'] ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Supprimer cette entrée et ajuster les points ?')">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">Aucun historique</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

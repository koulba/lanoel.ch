<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Ajouter une équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = trim($_POST['name']);
    $player1 = $_POST['player1_id'] ?: null;
    $player2 = $_POST['player2_id'] ?: null;
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO teams (name, player1_id, player2_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $player1, $player2]);
        $success = "Équipe créée avec succès !";
    }
}

// Modifier une équipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_team'])) {
    $id = $_POST['team_id'];
    $name = trim($_POST['name']);
    $player1 = $_POST['player1_id'] ?: null;
    $player2 = $_POST['player2_id'] ?: null;
    
    $stmt = $pdo->prepare("UPDATE teams SET name = ?, player1_id = ?, player2_id = ? WHERE id = ?");
    $stmt->execute([$name, $player1, $player2, $id]);
    $success = "Équipe modifiée avec succès !";
}

// Supprimer une équipe
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Équipe supprimée avec succès !";
}

// Récupérer toutes les équipes
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

// Récupérer tous les joueurs (non admin)
$stmt = $pdo->query("SELECT id, username FROM users WHERE is_admin = 0 ORDER BY username");
$players = $stmt->fetchAll();

$pageTitle = "Gestion des équipes";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">🏆 Gestion des équipes</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="auth-box" style="max-width: 600px;">
        <h3>Créer une équipe</h3>
        <form method="POST">
            <div class="form-group">
                <label>Nom de l'équipe</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Joueur 1</label>
                <select name="player1_id">
                    <option value="">-- Choisir un joueur --</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Joueur 2</label>
                <select name="player2_id">
                    <option value="">-- Choisir un joueur --</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="add_team" class="btn btn-primary">Créer l'équipe</button>
        </form>
    </div>
    
    <h3 style="margin-top: 50px; font-size: 1.5rem;">Liste des équipes</h3>
    
    <?php if (empty($teams)): ?>
        <div class="alert alert-info">Aucune équipe n'a encore été créée.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Nom de l'équipe</th>
                    <th>Joueur 1</th>
                    <th>Joueur 2</th>
                    <th>Points</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $index => $team): ?>
                    <tr>
                        <td>
                            <strong style="font-size: 1.2rem;">
                                <?php if ($index === 0): ?>
                                    🥇
                                <?php elseif ($index === 1): ?>
                                    🥈
                                <?php elseif ($index === 2): ?>
                                    🥉
                                <?php else: ?>
                                    #<?= $index + 1 ?>
                                <?php endif; ?>
                            </strong>
                        </td>
                        <td><strong><?= htmlspecialchars($team['name']) ?></strong></td>
                        <td><?= $team['player1_name'] ? htmlspecialchars($team['player1_name']) : '-' ?></td>
                        <td><?= $team['player2_name'] ? htmlspecialchars($team['player2_name']) : '-' ?></td>
                        <td><strong><?= $team['points'] ?> pts</strong></td>
                        <td class="admin-actions">
                            <button onclick="editTeam(<?= $team['id'] ?>, '<?= htmlspecialchars($team['name'], ENT_QUOTES) ?>', <?= $team['player1_id'] ?: 'null' ?>, <?= $team['player2_id'] ?: 'null' ?>)" class="btn btn-small btn-secondary">Modifier</button>
                            <a href="?delete=<?= $team['id'] ?>" onclick="return confirm('Êtes-vous sûr ?')" class="btn btn-small btn-danger">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Modal de modification -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Modifier l'équipe</h3>
        <form method="POST">
            <input type="hidden" name="team_id" id="edit_team_id">
            
            <div class="form-group">
                <label>Nom de l'équipe</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label>Joueur 1</label>
                <select name="player1_id" id="edit_player1">
                    <option value="">-- Choisir un joueur --</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Joueur 2</label>
                <select name="player2_id" id="edit_player2">
                    <option value="">-- Choisir un joueur --</option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?= $player['id'] ?>"><?= htmlspecialchars($player['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="edit_team" class="btn btn-primary">Modifier</button>
        </form>
    </div>
</div>

<script>
function editTeam(id, name, player1Id, player2Id) {
    document.getElementById('edit_team_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_player1').value = player1Id || '';
    document.getElementById('edit_player2').value = player2Id || '';
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>

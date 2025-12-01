<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Ajouter un jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $name = trim($_POST['name']);
    $image = null;
    
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType;
        $targetFile = $targetDir . $newFileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($imageFileType, $allowedTypes) && $_FILES["image"]["size"] < 5000000) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $image = $newFileName;
            }
        }
    }
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO games (name, image) VALUES (?, ?)");
        $stmt->execute([$name, $image]);
        $success = "Jeu ajouté avec succès !";
    }
}

// Modifier un jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_game'])) {
    $id = $_POST['game_id'];
    $name = trim($_POST['name']);
    $image = $_POST['current_image'];
    
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid() . '.' . $imageFileType;
        $targetFile = $targetDir . $newFileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($imageFileType, $allowedTypes) && $_FILES["image"]["size"] < 5000000) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                // Supprimer l'ancienne image
                if ($image && file_exists("../uploads/" . $image)) {
                    unlink("../uploads/" . $image);
                }
                $image = $newFileName;
            }
        }
    }
    
    $stmt = $pdo->prepare("UPDATE games SET name = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $image, $id]);
    $success = "Jeu modifié avec succès !";
}

// Supprimer un jeu
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT image FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch();
    
    if ($game && $game['image'] && file_exists("../uploads/" . $game['image'])) {
        unlink("../uploads/" . $game['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Jeu supprimé avec succès !";
}

// Récupérer tous les jeux
$stmt = $pdo->query("
    SELECT g.*, COUNT(v.id) as vote_count
    FROM games g
    LEFT JOIN votes v ON g.id = v.game_id
    GROUP BY g.id
    ORDER BY vote_count DESC
");
$games = $stmt->fetchAll();

$pageTitle = "Gestion des jeux";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">🎮 Gestion des jeux</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="auth-box" style="max-width: 600px;">
    <h3>Ajouter un jeu</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Nom du jeu</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label for="game_image">Image du jeu</label>
            <div class="file-input-wrapper">
                <label for="game_image" class="file-input-label">
                    📷 Choisir une image
                </label>
                <input type="file" name="image" id="game_image" accept="image/*" onchange="showGameFileName(this)">
            </div>
            <div class="file-name" id="gameFileName"></div>
            <small style="display: block; margin-top: 5px; color: var(--gray);">
                Formats acceptés : JPG, PNG, GIF, WEBP (max 5Mo)
            </small>
        </div>

        <button type="submit" name="add_game" class="btn btn-primary">Ajouter</button>
    </form>
</div>

<!-- ... reste du code ... -->

<!-- Modal de modification -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Modifier le jeu</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="game_id" id="edit_game_id">
            <input type="hidden" name="current_image" id="current_image">

            <div class="form-group">
                <label>Nom du jeu</label>
                <input type="text" name="name" id="edit_name" required>
            </div>

            <div class="form-group">
                <label for="edit_game_image">Nouvelle image (laisser vide pour conserver l'actuelle)</label>
                <div class="file-input-wrapper">
                    <label for="edit_game_image" class="file-input-label">
                        📷 Choisir une image
                    </label>
                    <input type="file" name="image" id="edit_game_image" accept="image/*" onchange="showEditFileName(this)">
                </div>
                <div class="file-name" id="editFileName"></div>
            </div>

            <button type="submit" name="edit_game" class="btn btn-primary">Modifier</button>
        </form>
    </div>
</div>

<script>
function showGameFileName(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('gameFileName').textContent = fileName ? `📄 ${fileName}` : '';
}

function showEditFileName(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('editFileName').textContent = fileName ? `📄 ${fileName}` : '';
}

function editGame(id, name, image) {
    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('current_image').value = image;
    document.getElementById('editFileName').textContent = '';
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
    
    <h3 style="margin-top: 50px; font-size: 1.5rem;">Liste des jeux</h3>
    
    <?php if (empty($games)): ?>
        <div class="alert alert-info">Aucun jeu n'a encore été ajouté.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Votes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td>
                            <?php if ($game['image']): ?>
                                <img src="../uploads/<?= htmlspecialchars($game['image']) ?>" alt="" class="admin-game-thumbnail">
                            <?php else: ?>
                                <div class="admin-game-thumbnail-placeholder">🎮</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($game['name']) ?></strong></td>
                        <td><?= $game['vote_count'] ?> vote<?= $game['vote_count'] > 1 ? 's' : '' ?></td>
                        <td class="admin-actions">
                            <button onclick="editGame(<?= $game['id'] ?>, '<?= htmlspecialchars($game['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($game['image']) ?>')" class="btn btn-small btn-secondary">Modifier</button>
                            <a href="?delete=<?= $game['id'] ?>" onclick="return confirm('Êtes-vous sûr ?')" class="btn btn-small btn-danger">Supprimer</a>
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
        <h3>Modifier le jeu</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="game_id" id="edit_game_id">
            <input type="hidden" name="current_image" id="current_image">
            
            <div class="form-group">
                <label>Nom du jeu</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            
            <div class="form-group">
                <label>Nouvelle image (laisser vide pour conserver l'actuelle)</label>
                <input type="file" name="image" accept="image/*">
            </div>
            
            <button type="submit" name="edit_game" class="btn btn-primary">Modifier</button>
        </form>
    </div>
</div>

<script>
function editGame(id, name, image) {
    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('current_image').value = image;
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

<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Gérer l'upload d'image
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['avatar']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $error = "Format non autorisé. Utilisez JPG, PNG ou GIF.";
    } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
        $error = "L'image ne doit pas dépasser 2 MB.";
    } else {
        $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../uploads/avatars/' . $new_filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
            // Supprimer l'ancien avatar
            if ($user['avatar'] && file_exists('../uploads/avatars/' . $user['avatar'])) {
                unlink('../uploads/avatars/' . $user['avatar']);
            }

            // Mettre à jour la base de données
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$new_filename, $user_id]);

            $user['avatar'] = $new_filename;
            $message = "Photo de profil mise à jour !";
        } else {
            $error = "Erreur lors de l'upload.";
        }
    }
}

// ⭐ CORRECTION : Définir l'URL correcte de l'avatar
if ($user['avatar']) {
    // Chemin absolu depuis la racine du site
    $avatar_url = '/tournoi-gaming/uploads/avatars/' . htmlspecialchars($user['avatar']);
} else {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&size=150&background=1d1d1f&color=ffffff';
}

// ⭐ Définir la variable pour le header
$isProfile = true;

include '../includes/header.php';
?>
<link rel="stylesheet" href="../css/style.css">
<div class="profile-container">
    <div class="profile-header">
        <h1>Mon Profil</h1>
        <p class="profile-subtitle">Gérez votre photo de profil</p>
    </div>

    <div class="avatar-section">
        <img src="<?= $avatar_url ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
        <div class="username-display">👤 <?= htmlspecialchars($user['username']) ?></div>
    </div>

    <?php if ($message): ?>
        <div class="message success">✅ <?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">❌ <?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="file-input-wrapper">
            <label for="avatar" class="file-input-label">
                📷 Choisir une photo
            </label>
            <input type="file" name="avatar" id="avatar" accept="image/*" required onchange="previewAvatar(this)">
        </div>

        <div class="file-name" id="fileName"></div>
        <div class="file-info">Formats acceptés : JPG, PNG, GIF (max 2 MB)</div>

        <button type="submit" class="btn-upload">Mettre à jour la photo</button>
    </form>



    <div class="profile-share-section">
    <p class="profile-share-text">🔗 Partage ton profil public :</p>
    <div class="profile-share-buttons">
        <a href="view.php?id=<?= $_SESSION['user_id'] ?>"
           class="btn btn-secondary"
           target="_blank">
            👁️ Voir mon profil public
        </a>

    </div>
<a href="../index.php" class="back-link">← Retour à l'accueil</a>
</div>

<script>
function copyProfileLink() {
    var link = document.getElementById('profileLink');
    link.select();
    link.setSelectionRange(0, 99999); // Pour mobile
    
    navigator.clipboard.writeText(link.value).then(function() {
        // Notification de succès
        var btn = event.target;
        var originalText = btn.innerHTML;
        btn.innerHTML = '✅ Copié !';
        btn.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 2000);
    });
}
</script>


</div>

<script>
function previewAvatar(input) {
    const fileName = input.files[0]?.name || '';
    document.getElementById('fileName').textContent = fileName ? `📄 ${fileName}` : '';
    
    // Prévisualisation de l'image
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>

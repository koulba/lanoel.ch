<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Récupérer l'ID de l'utilisateur à afficher
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profile_id === 0) {
    redirect('../index.php');
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.*, t.name as team_name, t.points as team_points
    FROM users u
    LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
    WHERE u.id = ?
");
$stmt->execute([$profile_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('../index.php');
}

// Récupérer les votes de l'utilisateur
$stmt = $pdo->prepare("
    SELECT g.name, g.image, v.created_at
    FROM votes v
    JOIN games g ON v.game_id = g.id
    WHERE v.user_id = ?
    ORDER BY v.created_at DESC
");
$stmt->execute([$profile_id]);
$votes = $stmt->fetchAll();

// Vérifier si c'est le profil de l'utilisateur connecté
$is_own_profile = ($profile_id === $_SESSION['user_id']);

$pageTitle = "Profil de " . htmlspecialchars($user['username']);
include '../includes/header.php';
?>

<div class="container" style="max-width: 900px; margin-top: 40px;">

    <!-- Header du profil -->
    <div style="background-color: var(--dark-gray); border-radius: 18px; padding: 50px 40px; text-align: center; color: var(--white); margin-bottom: 30px;">

        <!-- Avatar -->
        <div style="margin-bottom: 25px;">
            <?php 
            $avatar_url = !empty($user['avatar']) 
                ? '../uploads/avatars/' . htmlspecialchars($user['avatar'])
                : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&size=200&background=1d1d1f&color=fff&bold=true';
            ?>
            <img src="<?= $avatar_url ?>" 
                 alt="Avatar de <?= htmlspecialchars($user['username']) ?>" 
                 style="width: 140px; height: 140px; border-radius: 50%; border: 3px solid var(--white); object-fit: cover;">
        </div>

        <!-- Nom d'utilisateur -->
        <h1 style="font-size: 2.2rem; margin: 15px 0 10px 0; font-weight: 600; letter-spacing: -1px;">
            <?= htmlspecialchars($user['username']) ?>
            <?php if ($user['is_admin']): ?>
                <span style="background-color: var(--white); color: var(--black); padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px; font-weight: 600;">Admin</span>
            <?php endif; ?>
        </h1>

        <!-- Équipe -->
        <?php if ($user['team_name']): ?>
            <div style="font-size: 1.1rem; color: var(--light-gray); margin-top: 15px;">
                Équipe : <strong style="color: var(--white);"><?= htmlspecialchars($user['team_name']) ?></strong> 
                <span style="color: var(--gray);">(<?= $user['team_points'] ?> points)</span>
            </div>
        <?php else: ?>
            <div style="font-size: 1rem; color: var(--gray); margin-top: 15px;">
                Pas encore d'équipe
            </div>
        <?php endif; ?>

        <!-- Bouton d'édition si c'est son propre profil -->
        <?php if ($is_own_profile): ?>
            <a href="index.php" class="btn btn-primary" style="margin-top: 25px; display: inline-block; background-color: var(--white); color: var(--black);">
                Modifier mon profil
            </a>
        <?php endif; ?>
    </div>

    <!-- Statistiques -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">

        <!-- Total de votes -->
        <div style="background-color: var(--white); border: 1px solid var(--light-gray); padding: 30px; border-radius: 18px; text-align: center;">
            <div style="font-size: 3rem; font-weight: 700; color: var(--black); margin-bottom: 5px; letter-spacing: -2px;">
                <?= count($votes) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--gray); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500;">
                Vote<?= count($votes) > 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Date d'inscription -->
        <div style="background-color: var(--white); border: 1px solid var(--light-gray); padding: 30px; border-radius: 18px; text-align: center;">
            <div style="font-size: 1.3rem; font-weight: 600; color: var(--black); margin-bottom: 5px;">
                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--gray); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500;">
                Inscrit le
            </div>
        </div>

        <!-- Badge équipe -->
        <div style="background-color: var(--white); border: 1px solid var(--light-gray); padding: 30px; border-radius: 18px; text-align: center;">
            <div style="font-size: 1.3rem; font-weight: 600; color: var(--black); margin-bottom: 5px;">
                <?= $user['team_name'] ? 'En équipe' : 'Solo' ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--gray); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 500;">
                Statut
            </div>
        </div>
    </div>

    <!-- Votes de l'utilisateur -->
    <div style="background-color: var(--white); border: 1px solid var(--light-gray); padding: 40px; border-radius: 18px;">
        <h2 style="margin-bottom: 30px; font-size: 1.5rem; font-weight: 600; letter-spacing: -0.5px;">
            Jeux votés
        </h2>

        <?php if (empty($votes)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--gray);">
                <p style="font-size: 1.1rem;">Aucun vote pour le moment</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px;">
                <?php foreach ($votes as $vote): ?>
                    <div style="background-color: var(--white); border: 1px solid var(--light-gray); border-radius: 15px; overflow: hidden; transition: var(--transition); cursor: pointer;" 
                         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 30px rgba(0,0,0,0.1)'; this.style.borderColor='var(--black)';"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'; this.style.borderColor='var(--light-gray)';">

                        <?php if ($vote['image']): ?>
                            <img src="../uploads/<?= htmlspecialchars($vote['image']) ?>" 
                                 alt="<?= htmlspecialchars($vote['name']) ?>" 
                                 style="width: 100%; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 120px; background-color: var(--light-gray); display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                🎮
                            </div>
                        <?php endif; ?>

                        <div style="padding: 15px; text-align: center;">
                            <div style="font-weight: 600; margin-bottom: 5px; font-size: 0.95rem; color: var(--dark-gray);">
                                <?= htmlspecialchars($vote['name']) ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--gray);">
                                <?= date('d/m/Y', strtotime($vote['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bouton retour -->
    <div style="text-align: center; margin: 40px 0 60px 0;">
        <a href="../index.php" class="btn btn-secondary">
            ← Retour à l'accueil
        </a>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

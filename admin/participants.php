<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$success = '';
$error = '';
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'present', 'absent'])) {
    $filter = 'all';
}

// Basculer le statut présent / absent d'un participant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_absent'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $isAbsent = (int)($_POST['is_absent'] ?? 0) === 1 ? 1 : 0;

    if ($userId > 0) {
        $stmt = $pdo->prepare("UPDATE users SET is_absent = ? WHERE id = ? AND is_admin = 0");
        $stmt->execute([$isAbsent, $userId]);

        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $name = $stmt->fetchColumn();

        $_SESSION['success_message'] = $isAbsent
            ? htmlspecialchars($name) . " est marqué(e) absent(e)."
            : htmlspecialchars($name) . " est marqué(e) présent(e).";
    }
    redirect('participants.php?filter=' . urlencode($filter));
}

// Générer une "équipe" solo pour chaque participant présent qui n'en a pas encore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_solo'])) {
    $stmt = $pdo->query("
        SELECT u.id, u.username
        FROM users u
        LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
        WHERE u.is_admin = 0 AND u.is_absent = 0 AND t.id IS NULL
        ORDER BY u.username
    ");
    $toCreate = $stmt->fetchAll();

    $insert = $pdo->prepare("INSERT INTO teams (name, player1_id, player2_id) VALUES (?, ?, NULL)");
    $created = 0;
    foreach ($toCreate as $player) {
        $insert->execute([$player['username'], $player['id']]);
        $created++;
    }

    $_SESSION['success_message'] = $created > 0
        ? "$created joueur(s) solo créé(s). Ils apparaissent maintenant dans le classement et la page des points."
        : "Rien à faire : tous les participants présents ont déjà leur entrée dans le classement.";
    redirect('participants.php?filter=' . urlencode($filter));
}

// Supprimer les entrées solo des absents qui n'ont pas encore de points
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cleanup_absent_solo'])) {
    $stmt = $pdo->query("
        SELECT t.id
        FROM teams t
        JOIN users u ON t.player1_id = u.id
        WHERE t.player2_id IS NULL AND u.is_absent = 1 AND t.points = 0
    ");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $removed = 0;
    if (!empty($ids)) {
        $delete = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        foreach ($ids as $id) {
            $delete->execute([$id]);
            $removed++;
        }
    }

    $_SESSION['success_message'] = $removed > 0
        ? "$removed entrée(s) solo d'absent(s) retirée(s) du classement."
        : "Aucune entrée solo d'absent à retirer.";
    redirect('participants.php?filter=' . urlencode($filter));
}

// Récupérer tous les participants avec leur éventuelle équipe
$where = "u.is_admin = 0";
if ($filter === 'present') $where .= " AND u.is_absent = 0";
if ($filter === 'absent')  $where .= " AND u.is_absent = 1";

$stmt = $pdo->query("
    SELECT u.id, u.username, u.avatar, u.is_absent, u.created_at,
           t.id as team_id, t.name as team_name, t.points as team_points, t.player2_id,
           (SELECT COUNT(*) FROM votes WHERE user_id = u.id) as vote_count
    FROM users u
    LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
    WHERE $where
    ORDER BY u.is_absent ASC, u.username ASC
");
$participants = $stmt->fetchAll();

// Compteurs globaux (indépendants du filtre)
$counts = $pdo->query("
    SELECT
        SUM(is_absent = 0) as present,
        SUM(is_absent = 1) as absent
    FROM users WHERE is_admin = 0
")->fetch();
$countPresent = (int)$counts['present'];
$countAbsent  = (int)$counts['absent'];

$missingSolo = (int)$pdo->query("
    SELECT COUNT(*)
    FROM users u
    LEFT JOIN teams t ON (t.player1_id = u.id OR t.player2_id = u.id)
    WHERE u.is_admin = 0 AND u.is_absent = 0 AND t.id IS NULL
")->fetchColumn();

$absentSoloToClean = (int)$pdo->query("
    SELECT COUNT(*)
    FROM teams t
    JOIN users u ON t.player1_id = u.id
    WHERE t.player2_id IS NULL AND u.is_absent = 1 AND t.points = 0
")->fetchColumn();

$pageTitle = "Gestion des participants";
$isAdmin = true;
include '../includes/header.php';
?>

<div class="container">
    <h2 class="section-title">👥 Gestion des participants</h2>
    <p class="section-subtitle">Marquez les lutins qui ne seront pas là le jour J. Leur compte et leurs votes sont conservés.</p>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="teams-grid">
        <div class="team-card">
            <div class="admin-stat-icon">✅</div>
            <div class="team-name">Présents</div>
            <div class="team-points"><?= $countPresent ?></div>
        </div>
        <div class="team-card">
            <div class="admin-stat-icon">🚫</div>
            <div class="team-name">Absents</div>
            <div class="team-points"><?= $countAbsent ?></div>
        </div>
        <div class="team-card">
            <div class="admin-stat-icon">🎯</div>
            <div class="team-name">Présents sans classement</div>
            <div class="team-points"><?= $missingSolo ?></div>
        </div>
    </div>

    <!-- Mode cavalier seul -->
    <div class="auth-box" style="max-width: 700px; margin-top: 40px;">
        <h3>🏇 Mode cavalier seul</h3>
        <p style="color: var(--muted); margin-bottom: 18px;">
            Cette année il n'y a pas d'équipes. Ce bouton crée une entrée de classement individuelle
            (une « équipe » d'une seule personne, portant le pseudo du joueur) pour chaque participant présent
            qui n'en a pas encore. Les absents sont ignorés. Vous pouvez le relancer sans risque : il ne crée jamais de doublon.
        </p>
        <div class="admin-actions">
            <form method="POST">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <button type="submit" name="generate_solo" class="btn btn-primary" <?= $missingSolo === 0 ? 'disabled' : '' ?>>
                    Générer les joueurs solo (<?= $missingSolo ?>)
                </button>
            </form>
            <?php if ($absentSoloToClean > 0): ?>
                <form method="POST" onsubmit="return confirm('Retirer du classement les <?= $absentSoloToClean ?> absent(s) qui n\'ont pas encore de points ?')">
                    <button type="submit" name="cleanup_absent_solo" class="btn btn-secondary">
                        Retirer les absents du classement (<?= $absentSoloToClean ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtres -->
    <div class="participants-filter">
        <span style="color: var(--muted); font-size: .85rem;">Afficher :</span>
        <a href="?filter=all" class="btn btn-small btn-secondary <?= $filter === 'all' ? 'active' : '' ?>">Tous (<?= $countPresent + $countAbsent ?>)</a>
        <a href="?filter=present" class="btn btn-small btn-secondary <?= $filter === 'present' ? 'active' : '' ?>">Présents (<?= $countPresent ?>)</a>
        <a href="?filter=absent" class="btn btn-small btn-secondary <?= $filter === 'absent' ? 'active' : '' ?>">Absents (<?= $countAbsent ?>)</a>
    </div>

    <?php if (empty($participants)): ?>
        <div class="alert alert-info" style="margin-top: 24px;">Aucun participant dans cette catégorie.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Participant</th>
                    <th>Statut</th>
                    <th>Classement</th>
                    <th>Votes</th>
                    <th>Inscrit le</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                    <?php
                    $avatar_url = !empty($p['avatar'])
                        ? '../uploads/avatars/' . htmlspecialchars($p['avatar'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($p['username']) . '&size=72&background=ff2e2e&color=1a0000&bold=true';
                    $isSolo = $p['team_id'] && $p['player2_id'] === null;
                    ?>
                    <tr class="participants-admin-row <?= $p['is_absent'] ? 'is-absent' : '' ?>">
                        <td>
                            <img src="<?= $avatar_url ?>" alt="" class="avatar-mini">
                            <a href="../profile/view.php?id=<?= $p['id'] ?>" style="color: inherit;"><strong><?= htmlspecialchars($p['username']) ?></strong></a>
                        </td>
                        <td>
                            <?php if ($p['is_absent']): ?>
                                <span class="status-pill absent">Absent</span>
                            <?php else: ?>
                                <span class="status-pill present">Présent</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['team_id']): ?>
                                <?= $isSolo ? 'Solo' : 'Équipe <strong>' . htmlspecialchars($p['team_name']) . '</strong>' ?>
                                · <?= (int)$p['team_points'] ?> pts
                                <?php if ($p['is_absent'] && (int)$p['team_points'] > 0): ?>
                                    <br><span style="color: var(--warning); font-size: .8rem;">⚠️ Absent mais a déjà des points</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$p['vote_count'] ?></td>
                        <td><?= $p['created_at'] ? date('d.m.Y', strtotime($p['created_at'])) : '—' ?></td>
                        <td class="admin-actions">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="is_absent" value="<?= $p['is_absent'] ? 0 : 1 ?>">
                                <?php if ($p['is_absent']): ?>
                                    <button type="submit" name="toggle_absent" class="btn btn-small btn-primary">Marquer présent</button>
                                <?php else: ?>
                                    <button type="submit" name="toggle_absent" class="btn btn-small btn-danger">Marquer absent</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

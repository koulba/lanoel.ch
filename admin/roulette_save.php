<?php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit();
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// Initialiser la session pour la roulette si elle n'existe pas
if (!isset($_SESSION['roulette_drawn'])) {
    $_SESSION['roulette_drawn'] = [];
}

// Récupérer les IDs des participants tirés
$participant1_id = isset($_POST['participant1_id']) ? intval($_POST['participant1_id']) : 0;
$participant2_id = isset($_POST['participant2_id']) ? intval($_POST['participant2_id']) : 0;

// Vérifier que les IDs sont valides
if ($participant1_id <= 0 || $participant2_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'IDs invalides']);
    exit();
}

// Vérifier que les participants n'ont pas déjà été tirés
if (in_array($participant1_id, $_SESSION['roulette_drawn']) ||
    in_array($participant2_id, $_SESSION['roulette_drawn'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Un ou plusieurs participants ont déjà été tirés']);
    exit();
}

// Vérifier que les participants existent dans la base de données
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id IN (?, ?) AND is_admin = 0");
    $stmt->execute([$participant1_id, $participant2_id]);
    $result = $stmt->fetch();

    if ($result['count'] != 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Participants introuvables']);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données']);
    exit();
}

// Ajouter les participants à la liste des tirés
$_SESSION['roulette_drawn'][] = $participant1_id;
$_SESSION['roulette_drawn'][] = $participant2_id;

// Retourner une réponse de succès
echo json_encode([
    'success' => true,
    'message' => 'Équipe enregistrée avec succès',
    'drawn_count' => count($_SESSION['roulette_drawn'])
]);
?>

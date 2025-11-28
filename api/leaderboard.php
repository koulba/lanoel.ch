<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
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
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour l'overlay
    $leaderboard = [];
    foreach ($teams as $index => $team) {
        $leaderboard[] = [
            'rank' => $index + 1,
            'name' => $team['name'],
            'points' => (int)$team['points'],
            'player1' => $team['player1_name'],
            'player2' => $team['player2_name']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $leaderboard,
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur'
    ]);
}

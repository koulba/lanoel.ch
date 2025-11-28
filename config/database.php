<?php
session_start();

// Configuration de la base de données
$host = '***MASKED***';
$dbname = '***MASKED***';
$username = '***MASKED***';
$password = '***MASKED***;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Fonction pour rediriger
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fonction pour vérifier si la période de vote est terminée
function isVotingClosed() {
    $deadline = new DateTime('2025-11-27 23:59:59');
    $now = new DateTime();
    return $now > $deadline;
}
?>


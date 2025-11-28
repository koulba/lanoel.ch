<?php
session_start();

// Charger les variables d'environnement depuis le fichier .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignorer les commentaires
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
        $_ENV[trim($key)] = trim($value);
    }
}

// Configuration de la base de données
// Vérifier d'abord les variables d'environnement, sinon utiliser $_ENV
$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? null);
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
$password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? null);

// Si les variables ne sont pas définies, afficher un message d'erreur détaillé
if (!$host || !$dbname || !$username || !$password) {
    die("Erreur : Les variables d'environnement de la base de données ne sont pas définies. Vérifiez que le fichier .env existe à : " . realpath(__DIR__ . '/..'));
}

try {
    // Forcer la connexion TCP/IP avec le port 3306 pour éviter l'erreur "No such file or directory"
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
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


-- Script de mise à jour du système de scoring
-- À exécuter dans phpMyAdmin ou via ligne de commande MySQL

-- 1. Ajouter une colonne pour indiquer si le jeu est en mode équipe ou individuel
ALTER TABLE games ADD COLUMN scoring_mode ENUM('team', 'individual') DEFAULT 'team' AFTER image;

-- 2. Créer une table pour stocker les barèmes de points
CREATE TABLE IF NOT EXISTS scoring_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Créer une table pour les détails des barèmes (positions et points)
CREATE TABLE IF NOT EXISTS scoring_preset_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preset_id INT NOT NULL,
    position INT NOT NULL,
    points INT NOT NULL,
    FOREIGN KEY (preset_id) REFERENCES scoring_presets(id) ON DELETE CASCADE
);

-- 4. Insérer le barème par défaut pour les jeux d'équipe
INSERT INTO scoring_presets (id, name, description) VALUES
(1, 'Barème Équipe Standard', 'Barème pour les jeux en équipe avec 6 positions');

-- 5. Insérer les points pour chaque position
INSERT INTO scoring_preset_details (preset_id, position, points) VALUES
(1, 1, 16),
(1, 2, 13),
(1, 3, 10),
(1, 4, 7),
(1, 5, 5),
(1, 6, 3);

-- 6. Mettre à jour les jeux d'équipe existants
UPDATE games SET scoring_mode = 'team'
WHERE name IN ('BAPBAP', 'Mario Kart', 'Mario kart', 'GeoGuessr', 'Biped 2', 'Codenames', 'Mage Arena', 'Gentlemen''s Dispute');

-- 7. Mettre à jour les jeux individuels existants
UPDATE games SET scoring_mode = 'individual'
WHERE name IN ('Fall Guys', 'Trackmania', 'Skribbl.io');

-- 8. Ajouter une colonne dans points_history pour stocker les points individuels
ALTER TABLE points_history
ADD COLUMN player1_points INT DEFAULT 0 AFTER points,
ADD COLUMN player2_points INT DEFAULT 0 AFTER player1_points,
ADD COLUMN position INT DEFAULT NULL AFTER reason;

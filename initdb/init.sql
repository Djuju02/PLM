CREATE DATABASE IF NOT EXISTS plm;
USE plm;

-- TABLES DE BASE
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (role_name) VALUES ('admin'), ('manager'), ('user'), ('Equipe1'), ('Equipe2');

-- Utilisateurs
INSERT IGNORE INTO users (username, password_hash, role) 
VALUES ('admin', SHA2('admin', 256), 'admin'),
       ('User1', SHA2('mdp',256), 'Equipe1'),
       ('User2', SHA2('mdp',256), 'Equipe2');

-- TABLE PARFUMS (Produits)
-- Ajout d'un champ version pour suivre les itérations du produit
-- Ajout d'un champ lifecycle_stage pour l'étape du cycle de vie (ex: Développement, Production, Obsolète)
-- Ajout d'un champ team pour rattacher le produit à une équipe
CREATE TABLE IF NOT EXISTS parfums (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  team VARCHAR(50) DEFAULT 'Equipe1',
  version INT DEFAULT 1,
  lifecycle_stage VARCHAR(50) DEFAULT 'Développement'
);

-- TABLE INGREDIENTS (BOM : Bill of Materials)
-- Ajout de quantity pour la quantité requise de l’ingrédient par unité de parfum
-- unit_price, tva déjà mentionnés
CREATE TABLE IF NOT EXISTS ingredients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  reference VARCHAR(100),
  quantity DECIMAL(10,2) DEFAULT 1.00,
  unit_price DECIMAL(10,2) DEFAULT 5.00,
  tva DECIMAL(4,2) DEFAULT 20.00,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE
);

-- TABLE POUR L’HISTORIQUE DES CHANGEMENTS SUR LES INGREDIENTS
-- On stocke quel ingrédient a été modifié, par quel utilisateur, quand, et quelles valeurs ont changé
CREATE TABLE IF NOT EXISTS ingredient_changes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ingredient_id INT NOT NULL,
  user_id INT NOT NULL,
  field_changed VARCHAR(50) NOT NULL,
  old_value VARCHAR(255),
  new_value VARCHAR(255),
  changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- TABLE POUR LES COMMENTAIRES (HISTORIQUE DES MESSAGES)
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- TABLE POUR SUIVRE LA PRODUCTION (LOTS DE PRODUCTION)
-- Permet d’enregistrer des "runs" de production, avec quantité, date, qui a fait la saisie
CREATE TABLE IF NOT EXISTS production_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  produced_quantity DECIMAL(10,2) NOT NULL,
  produced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT NOT NULL,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- INSERER DES PARFUMS DE BASE
-- Parfum Lavande -> Equipe1, Parfum Rose -> Equipe2
INSERT IGNORE INTO parfums (name, description, price, team, version, lifecycle_stage)
VALUES ('Parfum Lavande', 'Un parfum frais et apaisant avec des notes de lavande.', 25.00, 'Equipe1', 1, 'Production'),
       ('Parfum Rose', 'Un parfum doux et romantique aux notes de rose.', 30.00, 'Equipe2', 1, 'Production');

-- INGREDIENTS DE BASE AVEC PRIX ET TVA
INSERT IGNORE INTO ingredients (parfum_id, name, reference, quantity, unit_price, tva)
VALUES 
(1, 'Essence de Lavande', 'LAV-001', 1.00, 5.00, 20.00),
(1, 'Huile de Bergamote', 'BER-002', 0.50, 3.50, 20.00),
(1, 'Essence de Menthe', 'MEN-003', 0.80, 4.00, 20.00),
(1, 'Huile de Citron', 'CIT-004', 0.30, 2.50, 20.00),
(1, 'Extrait de Vanille', 'VAN-005', 0.20, 6.00, 20.00),

(2, 'Extrait de Rose', 'ROS-001', 1.00, 5.50, 20.00),
(2, 'Huile de Jasmin', 'JAS-002', 0.70, 5.20, 20.00),
(2, 'Essence de Santal', 'SAN-003', 0.60, 4.80, 20.00),
(2, 'Huile d\'Ylang-Ylang', 'YLA-004', 0.50, 3.90, 20.00),
(2, 'Extrait de Muguet', 'MUG-005', 0.25, 6.50, 20.00);

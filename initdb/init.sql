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
-- Ajout de version, lifecycle_stage, team, et reference
CREATE TABLE IF NOT EXISTS parfums (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  team VARCHAR(50) DEFAULT 'Equipe1',
  version INT DEFAULT 1,
  lifecycle_stage VARCHAR(50) DEFAULT 'Développement',
  reference VARCHAR(100) UNIQUE
);

-- TABLE INGREDIENTS (BOM)
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

-- TABLE POUR L'HISTORIQUE DES MODIFICATIONS SUR LES INGREDIENTS
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

-- TABLE POUR LES COMMENTAIRES
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- TABLE POUR SUIVRE LA PRODUCTION
CREATE TABLE IF NOT EXISTS production_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  produced_quantity DECIMAL(10,2) NOT NULL,
  produced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT NOT NULL,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ingredients_global (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  reference VARCHAR(100) NOT NULL UNIQUE,
  default_unit_price DECIMAL(10,2) DEFAULT 5.00,
  default_tva DECIMAL(4,2) DEFAULT 20.00
);


-- INSERER DES PARFUMS DE BASE
INSERT IGNORE INTO parfums (name, description, price, team, version, lifecycle_stage, reference)
VALUES ('Parfum Lavande', 'Un parfum frais et apaisant avec des notes de lavande.', 25.00, 'Equipe1', 1, 'Production', 'PAR-LAV-001'),
       ('Parfum Rose', 'Un parfum doux et romantique aux notes de rose.', 30.00, 'Equipe2', 1, 'Production', 'PAR-ROS-001');

-- INGREDIENTS DE BASE
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

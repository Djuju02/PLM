CREATE DATABASE IF NOT EXISTS plm;
USE plm;

--
-- TABLES DE BASE
--
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(255) NOT NULL,
  last_login DATETIME DEFAULT NULL  -- Pour gérer la notion de "nouveaux" commentaires
);

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (role_name) VALUES 
('admin'),
('manager'),
('user'),
('Equipe1'),
('Equipe2');

-- AJOUT SANS 'IF NOT EXISTS', compatible MySQL 5.7
ALTER TABLE roles 
  ADD color_code VARCHAR(7) DEFAULT '#3498db';

--
-- Utilisateurs
--
INSERT IGNORE INTO users (username, password_hash, role, last_login)
VALUES 
('admin', SHA2('admin', 256), 'admin', '2024-12-10 08:00:00'),
('User1', SHA2('mdp',256), 'Equipe1', '2024-12-18 15:00:00'),
('User2', SHA2('mdp',256), 'Equipe2', '2024-12-18 16:30:00'),
('User3', SHA2('mdp',256), 'manager,Equipe1', '2024-12-19 09:00:00'),
('User4', SHA2('mdp',256), 'manager,Equipe2', '2024-12-19 10:00:00');

--
-- TABLE PARFUMS
--
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
ALTER TABLE parfums ADD image_filename VARCHAR(255) DEFAULT NULL;
CREATE TABLE IF NOT EXISTS parfum_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  user_id INT NOT NULL,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

--
-- TABLE INGREDIENTS (BOM)
--
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

--
-- TABLE POUR L'HISTORIQUE DES MODIFICATIONS SUR LES INGREDIENTS
--
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

--
-- TABLE POUR LES COMMENTAIRES
--
CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

--
-- TABLE POUR SUIVRE LA PRODUCTION
--
CREATE TABLE IF NOT EXISTS production_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parfum_id INT NOT NULL,
  produced_quantity DECIMAL(10,2) NOT NULL,
  produced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT NOT NULL,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

--
-- TABLE INGREDIENTS GLOBAL
-- (catalogue de sous-produits)
--
CREATE TABLE IF NOT EXISTS ingredients_global (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  reference VARCHAR(100) NOT NULL UNIQUE,
  default_unit_price DECIMAL(10,2) DEFAULT 5.00,
  default_tva DECIMAL(4,2) DEFAULT 20.00
);

CREATE TABLE IF NOT EXISTS dashboards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100),
  description TEXT,
  settings TEXT
);

CREATE TABLE IF NOT EXISTS user_parfum_read (
  user_id   INT NOT NULL,
  parfum_id INT NOT NULL,
  last_read_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, parfum_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (parfum_id) REFERENCES parfums(id) ON DELETE CASCADE
);


--
-- INSERER DES PARFUMS DE BASE
--
INSERT IGNORE INTO parfums (name, description, price, team, version, lifecycle_stage, reference)
VALUES
('Parfum Lavande', 'Un parfum frais et apaisant avec des notes de lavande.', 25.00, 'Equipe1', 2, 'Production', 'PAR-LAV-001'),
('Parfum Rose', 'Un parfum doux et romantique aux notes de rose.', 30.00, 'Equipe2', 1, 'Production', 'PAR-ROS-001'),
('Parfum Menthe', 'Une senteur fraîche et dynamique.', 0.00, 'Equipe1', 1, 'Pré-prod', 'PAR-MEN-001'),
('Parfum Vanille', 'Un parfum sucré et chaleureux.', 0.00, 'Equipe1', 1, 'R&D', 'PAR-VAN-001'),
('Parfum Agrumes', 'Mélange vif de citron, orange et bergamote.', 15.00, 'Equipe2', 2, 'Pré-prod', 'PAR-AGR-002');

-- team VARCHAR(255) pour stocker plusieurs équipes séparées par des virgules
ALTER TABLE parfums MODIFY team VARCHAR(255) DEFAULT 'Equipe1';

--
-- INGREDIENTS DE BASE POUR LES PARFUMS CI-DESSUS
--
INSERT IGNORE INTO ingredients (parfum_id, name, reference, quantity, unit_price, tva)
VALUES 
-- Parfum Lavande (id=1)
(1, 'Essence de Lavande', 'LAV-001', 1.00, 5.00, 20.00),
(1, 'Huile de Bergamote', 'BER-002', 0.50, 3.50, 20.00),

-- Parfum Rose (id=2)
(2, 'Extrait de Rose', 'ROS-001', 1.00, 5.50, 20.00),
(2, 'Huile de Jasmin', 'JAS-002', 0.70, 5.20, 20.00),

-- Parfum Menthe (id=3)
(3, 'Essence de Menthe', 'MEN-003', 1.00, 4.00, 20.00),

-- Parfum Vanille (id=4)
(4, 'Extrait de Vanille', 'VAN-005', 1.20, 6.00, 20.00),

-- Parfum Agrumes (id=5)
(5, 'Huile de Citron', 'CIT-004', 0.80, 2.50, 20.00),
(5, 'Essence d''Orange', 'ORA-010', 0.60, 3.00, 20.00);

--
-- INGREDIENTS GLOBAL (catalogue)
--
INSERT IGNORE INTO ingredients_global (name, reference, default_unit_price, default_tva)
VALUES
('Essence de Lavande', 'LAV-001', 5.00, 20.00),
('Huile de Bergamote', 'BER-002', 3.50, 20.00),
('Essence de Menthe', 'MEN-003', 4.00, 20.00),
('Huile de Citron', 'CIT-004', 2.50, 20.00),
('Extrait de Vanille', 'VAN-005', 6.00, 20.00),
('Extrait de Rose', 'ROS-001', 5.50, 20.00),
('Huile de Jasmin', 'JAS-002', 5.20, 20.00),
('Essence de Santal', 'SAN-003', 4.80, 20.00),
('Essence d''Orange', 'ORA-010', 3.00, 20.00),
('Huile d''Ylang-Ylang', 'YLA-004', 3.90, 20.00),
('Extrait de Muguet', 'MUG-005', 6.50, 20.00);

--
-- AJOUTER QUELQUES COMMENTAIRES DE DÉMO
--
INSERT IGNORE INTO comments (parfum_id, user_id, message, created_at)
VALUES
(1, 2, 'J’adore ce parfum !', '2024-12-18 16:00:00'),
(1, 3, 'On pourrait augmenter la concentration de lavande ?', '2024-12-19 09:30:00'),
(2, 4, 'La rose est trop forte, réduire l’extrait ?', '2024-12-19 11:00:00'),
(3, 2, 'La note mentholée est parfaite pour l’été.', '2024-12-20 08:00:00'),
(5, 3, 'Pourquoi pas ajouter du pamplemousse ?', '2024-12-20 09:15:00'),
(5, 1, 'Idée validée, merci !', '2024-12-20 10:00:00');

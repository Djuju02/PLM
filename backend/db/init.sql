-- 🟢 1. Création du rôle utilisateur (avec gestion des doublons)
DO $$ 
BEGIN 
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'parfum_user') THEN 
    CREATE ROLE parfum_user WITH LOGIN PASSWORD 'password'; 
  END IF; 
END 
$$;

-- 🟢 2. Création de la base de données (⚠️ CREATE DATABASE ne peut pas être dans un DO)
-- Vérification d'existence
SELECT 'CREATE DATABASE parfum_db' 
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'parfum_db')\gexec

-- 🟢 3. Connexion à la base de données
\c parfum_db parfum_user

-- 🟢 4. Attribution des privilèges à l'utilisateur 
GRANT ALL PRIVILEGES ON DATABASE parfum_db TO parfum_user;

-- 🟢 5. Création de la table des utilisateurs (avec gestion des doublons)
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);

-- 🟢 6. Création de la table des parfums (avec gestion des doublons)
CREATE TABLE IF NOT EXISTS parfums (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  price DECIMAL(10, 2) NOT NULL
);

-- 🟢 7. Accorder les permissions sur les tables aux utilisateurs
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE users TO parfum_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE parfums TO parfum_user;

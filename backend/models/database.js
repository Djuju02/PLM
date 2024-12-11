const { Pool } = require('pg');

// Crée une instance de pool pour PostgreSQL
const pool = new Pool({
  user: 'user',          // Utilisateur PostgreSQL
  host: 'db',            // Nom du service Docker pour PostgreSQL
  database: 'parfum_db', // Nom de la base de données
  password: 'password',  // Mot de passe PostgreSQL
  port: 5432,            // Port par défaut
});

pool.connect()
  .then(() => console.log('Connected to PostgreSQL'))
  .catch((err) => console.error('Connection error', err));

module.exports = pool;

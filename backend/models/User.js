const pool = require('./database');

// Créer un nouvel utilisateur
exports.createUser = async (username, password) => {
  const query = 'INSERT INTO users (username, password) VALUES ($1, $2) RETURNING *';
  const values = [username, password];
  const result = await pool.query(query, values);
  return result.rows[0];
};

// Trouver un utilisateur par nom d'utilisateur
exports.findUserByUsername = async (username) => {
  const query = 'SELECT * FROM users WHERE username = $1';
  const values = [username];
  const result = await pool.query(query, values);
  return result.rows[0];
};

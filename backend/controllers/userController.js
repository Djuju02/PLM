const User = require('../models/User');
const bcrypt = require('bcrypt');

// Inscription
exports.register = async (req, res) => {
  const { username, password } = req.body;
  try {
    const hashedPassword = await bcrypt.hash(password, 10);
    const user = await User.createUser(username, hashedPassword);
    res.status(201).send(user);
  } catch (err) {
    res.status(500).send({ message: 'Erreur lors de l\'inscription', error: err });
  }
};

// Connexion
exports.login = async (req, res) => {
  const { username, password } = req.body;
  try {
    const user = await User.findUserByUsername(username);
    if (!user) {
      return res.status(404).send({ message: 'Utilisateur non trouvé' });
    }
    const isValid = await bcrypt.compare(password, user.password);
    if (!isValid) {
      return res.status(401).send({ message: 'Mot de passe incorrect' });
    }
    res.send({ message: 'Connexion réussie' });
  } catch (err) {
    res.status(500).send({ message: 'Erreur lors de la connexion', error: err });
  }
};

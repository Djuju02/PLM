const express = require('express');
const app = express();
const parfumRoutes = require('./routes/parfumRoutes');
const userRoutes = require('./routes/userRoutes');
const authMiddleware = require('./middlewares/authMiddleware');

app.use(express.json());

// Route de base
app.get('/api', (req, res) => {
  res.send({ message: 'API is running' });
});

// Routes
app.use('/api/parfums', parfumRoutes);
app.use('/api/users', userRoutes);

app.listen(5000, () => {
  console.log('Server running on port 5000');
});

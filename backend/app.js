const express = require('express');
const app = express();
const parfumRoutes = require('./routes/parfumRoutes');
const userRoutes = require('./routes/userRoutes');
const authMiddleware = require('./middlewares/authMiddleware');

app.use(express.json());

// Routes
app.use('/api/parfums', parfumRoutes);
app.use('/api/users', userRoutes);

app.listen(5000, () => {
  console.log('Server running on port 5000');
});

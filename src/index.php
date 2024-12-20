<?php
session_start();
if (isset($_SESSION['username'])) {
  header("Location: home.php");
  exit();
}
$error = isset($_GET['error']) && $_GET['error'] === 'invalid';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion - PLM</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      background: #ecf0f1;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      height: 100vh;
    }

    .login-section {
      width: 30%;
      background: #ecf0f1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .login-section h2 {
      margin-bottom: 20px;
      color: #2c3e50;
    }

    .login-section input {
      width: 80%;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 5px;
      border: 1px solid #bdc3c7;
    }

    .login-section button {
      width: 80%;
      padding: 10px;
      background: #3498db;
      color: #fff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 16px;
    }

    .login-section button:hover {
      background: #2980b9;
    }

    .error-message {
      color: #e74c3c;
      margin-bottom: 10px;
    }

    .image-section {
      width: 70%;
      background: url('images/parfum.jpg') no-repeat center center;
      background-size: cover;
    }
  </style>
</head>
<body>
  <div class="login-section">
    <h2>Connexion à PLM</h2>
    <?php if ($error): ?>
      <p class="error-message">Nom d'utilisateur ou mot de passe incorrect.</p>
    <?php endif; ?>
    <form action="login.php" method="post">
      <input type="text" name="username" placeholder="Nom d'utilisateur" required>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <button type="submit">Se connecter</button>
    </form>
  </div>
  <div class="image-section"></div>
</body>
</html>

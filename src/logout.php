<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Déconnexion</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    body {
      background: #ecf0f1;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: Arial, sans-serif;
    }
    .message-box {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    .message-box h2 {
      margin-bottom: 20px;
      color: #2c3e50;
    }
    .countdown {
      color: #7f8c8d;
      margin-top: 10px;
    }
  </style>
  <script>
    let seconds = 3;
    const interval = setInterval(() => {
      seconds--;
      document.getElementById('countdown').textContent = seconds;
      if (seconds <= 0) {
        clearInterval(interval);
        window.location.href = 'index.php';
      }
    }, 1000);
  </script>
</head>
<body>
  <div class="message-box">
    <h2>Merci et au revoir !</h2>
    <p>Vous allez être redirigé vers la page de connexion dans <span id="countdown">3</span> secondes.</p>
  </div>
</body>
</html>

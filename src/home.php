<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil - PLM</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .sidebar {
      width: 250px;
      background-color: #2c3e50;
      color: #ecf0f1;
      padding: 20px;
    }

    .sidebar h2 {
      margin-bottom: 20px;
      font-size: 22px;
    }

    .sidebar h3 {
      margin-top: 20px;
      margin-bottom: 10px;
      font-size: 18px;
      color: #ecf0f1;
      border-bottom: 1px solid #34495e;
      padding-bottom: 5px;
    }

    .sidebar nav a {
      display: block;
      color: #ecf0f1;
      text-decoration: none;
      margin: 10px 0;
      font-size: 16px;
      padding: 10px;
      border-radius: 5px;
      transition: background 0.3s;
    }

    .sidebar nav a:hover {
      background-color: #34495e;
      text-decoration: none;
    }

    .main-content {
      flex: 1;
      padding: 20px;
      background-color: #ecf0f1;
    }

    header h1 {
      font-size: 24px;
      margin-bottom: 20px;
    }

    body {
      display: flex;
      margin: 0;
      font-family: Arial, sans-serif;
      background: #ecf0f1;
      color: #2c3e50;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Bonjour <?php echo $username; ?></h2>
    
    <h3>Gestion des Ressources</h3>
    <nav>
      <a href="list_parfums.php">Liste des Parfums</a>
      <a href="list_users.php">Liste des Utilisateurs</a>
    </nav>
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <h3>Gestion Admin</h3>
      <nav>
        <a href="manage_users.php">Utilisateurs</a>
        <a href="manage_parfums.php">Parfums</a>
        <a href="manage_roles.php">Rôles</a>
        <a href="manage_ingredients_global.php">Sous-Produits (BOM)</a>
      </nav>
    <?php endif; ?>

    <nav>
      <a href="logout.php">Se déconnecter</a>
    </nav>
  </div>

  <div class="main-content">
    <header>
      <h1 id="parfum-title">Bienvenue dans l'outil PLM</h1>
    </header>

    <section id="cost-simulation">
      <h2>Simulation des Coûts</h2>
      <p>Réservé à l’admin : Calculez les coûts selon les ingrédients.</p>
      <!-- Contenu de la simulation des coûts -->
    </section>

    <section id="compliance-management">
      <h2>Gestion des Conformités</h2>
      <p>Réservé à l’admin : Vérifiez la conformité des ingrédients.</p>
      <!-- Contenu de la gestion de conformité -->
    </section>

    <!-- Onglet BOM ajouté dans la sidebar admin (manage_ingredients.php) -->
    
  </div>
</body>
</html>

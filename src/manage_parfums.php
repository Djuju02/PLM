<?php
session_start();
if (!isset($_SESSION['username']) || strpos($_SESSION['role'], 'admin') === false) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Liste des parfums
$res = $mysqli->query("SELECT id, name, version, lifecycle_stage, team, reference FROM parfums ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gérer Parfums</title>
<link rel="stylesheet" href="styles.css">
<style>
  .container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  .header-actions {
    margin-bottom: 20px;
    display:flex;
    justify-content: space-between;
    align-items:center;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
    <a href="create_parfum.php" class="btn">Créer un Nouveau Parfum</a>
  </div>
  
  <h1>Gérer Parfums</h1>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Version</th>
        <th>Cycle de vie</th>
        <th>Équipe</th>
        <th>Référence</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($pf = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($pf['name']); ?></td>
        <td><?php echo htmlspecialchars($pf['version']); ?></td>
        <td><?php echo htmlspecialchars($pf['lifecycle_stage']); ?></td>
        <td><?php echo htmlspecialchars($pf['team']); ?></td>
        <td><?php echo htmlspecialchars($pf['reference']); ?></td>
        <td class="actions">
          <a href="edit_parfum.php?id=<?php echo $pf['id']; ?>" class="btn-edit">Modifier</a>
          <a href="delete_parfum.php?id=<?php echo $pf['id']; ?>" class="btn-delete" onclick="return confirm('Supprimer ce parfum ?')">Supprimer</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

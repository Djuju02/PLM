<?php
session_start();
if (!isset($_SESSION['username']) || strpos($_SESSION['role'], 'admin') === false) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Liste des parfums
$res = $mysqli->query("SELECT id, name, version, lifecycle_stage, team FROM parfums ORDER BY name ASC");

// Création d'un parfum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_parfum'])) {
  $p_name = $_POST['name'];
  $p_desc = $_POST['description'];
  $p_price = $_POST['price'];
  $p_team = $_POST['team'];
  $stmt_p = $mysqli->prepare("INSERT INTO parfums (name, description, price, team, version, lifecycle_stage) VALUES (?,?,?,?,1,'Développement')");
  $stmt_p->bind_param("ssds", $p_name, $p_desc, $p_price, $p_team);
  $stmt_p->execute();
  header("Location: manage_parfums.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gérer Parfums</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Gérer Parfums</h1>
  <div class="header-actions">
    <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Version</th>
        <th>Lifecycle</th>
        <th>Équipe</th>
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
        <td class="actions">
          <a href="edit_parfum.php?id=<?php echo $pf['id']; ?>" class="btn-edit">Modifier</a>
          <a href="delete_parfum.php?id=<?php echo $pf['id']; ?>" class="btn-delete" onclick="return confirm('Supprimer ce parfum ?')">Supprimer</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Créer un nouveau parfum</h2>
  <form method="post" style="max-width:400px;">
    <input type="hidden" name="create_parfum" value="1">
    <div class="form-group">
      <label>Nom du parfum</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"></textarea>
    </div>
    <div class="form-group">
      <label>Prix</label>
      <input type="number" step="0.01" name="price" required>
    </div>
    <div class="form-group">
      <label>Équipe</label>
      <select name="team">
        <option value="Equipe1">Equipe1</option>
        <option value="Equipe2">Equipe2</option>
      </select>
    </div>
    <button type="submit" class="btn">Créer</button>
  </form>
</div>
</body>
</html>

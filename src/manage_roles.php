<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");

// Création de rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
  $new_role_name = $_POST['new_role_name'];
  $stmt = $mysqli->prepare("INSERT INTO roles (role_name) VALUES (?)");
  $stmt->bind_param("s", $new_role_name);
  $stmt->execute();
}

$result_roles = $mysqli->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gérer les Rôles</title>
<link rel="stylesheet" href="styles.css">
<style>
  .header-actions {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
</style>
</head>
<body>
<div class="container">
  <h1>Gérer les Rôles</h1>
  <!-- On place le bouton retour à l'accueil à gauche et la création à droite -->
  <div class="header-actions">
    <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
    <!-- Pas de création massive, mais un formulaire en bas pour créer -->
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Rôle</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $result_roles->fetch_assoc()): ?>
        <tr>
          <td><?php echo $r['id']; ?></td>
          <td><?php echo htmlspecialchars($r['role_name']); ?></td>
          <td class="actions">
            <?php if ($r['role_name'] !== 'admin'): ?>
              <a href="edit_role.php?id=<?php echo $r['id']; ?>">Modifier</a>
              <a href="delete_role.php?id=<?php echo $r['id']; ?>" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
            <?php else: ?>
              <!-- Le rôle admin n'est ni modifiable ni supprimable -->
              <span style="color: #7f8c8d;">Impossible de modifier ou supprimer admin</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Créer un nouveau rôle</h2>
  <form method="post" style="max-width:300px;">
    <input type="hidden" name="create_role" value="1">
    <div class="form-group">
      <label>Nom du rôle</label>
      <input type="text" name="new_role_name" required>
    </div>
    <button type="submit" class="btn">Créer</button>
  </form>
</div>
</body>
</html>

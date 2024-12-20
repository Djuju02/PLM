<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");

// Création d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
  $new_username = $_POST['new_username'];
  $new_password = $_POST['new_password'];
  $selected_roles = $_POST['roles'] ?? [];

  // Concaténer les rôles choisis en une chaîne séparée par des virgules
  $roles_str = implode(',', $selected_roles);

  $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, SHA2(?, 256), ?)");
  $stmt->bind_param("sss", $new_username, $new_password, $roles_str);
  $stmt->execute();
}

$result_users = $mysqli->query("SELECT id, username, role FROM users ORDER BY id ASC");
$result_roles = $mysqli->query("SELECT role_name FROM roles ORDER BY role_name ASC");
$roles = $result_roles->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gérer les Utilisateurs</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .header-actions {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .actions {
      white-space: nowrap;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>Gérer les Utilisateurs</h1>
  <div class="header-actions">
    <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
    <!-- Pas de création de rôle ici, donc pas de bouton supplémentaire en haut -->
  </div>

  <h2>Liste des utilisateurs</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Nom d'utilisateur</th>
        <th>Rôles</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($user = $result_users->fetch_assoc()): ?>
      <tr>
        <td><?php echo $user['id']; ?></td>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td class="actions">
          <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-edit">Modifier</a>
          <?php if ($user['username'] !== 'admin'): ?>
            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr ?')">Supprimer</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Créer un nouvel utilisateur</h2>
  <form method="post" style="max-width:300px;">
    <input type="hidden" name="create_user" value="1">
    <div class="form-group">
      <label>Nom d'utilisateur</label>
      <input type="text" name="new_username" required>
    </div>
    <div class="form-group">
      <label>Mot de passe</label>
      <input type="password" name="new_password" required>
    </div>
    <div class="form-group">
      <label>Rôles</label>
      <?php foreach ($roles as $r): ?>
        <div>
          <label>
            <input type="checkbox" name="roles[]" value="<?php echo htmlspecialchars($r['role_name']); ?>">
            <?php echo htmlspecialchars($r['role_name']); ?>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn">Créer</button>
  </form>
</div>
</body>
</html>

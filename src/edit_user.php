<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_users.php");
  exit();
}

// Récupérer utilisateur
$stmt = $mysqli->prepare("SELECT username, password_hash, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_users.php");
  exit();
}
$stmt->bind_result($username, $password_hash, $roles_str);
$stmt->fetch();

// Récupérer tous les rôles disponibles
$roles_result = $mysqli->query("SELECT role_name FROM roles ORDER BY role_name ASC");
$all_roles = $roles_result->fetch_all(MYSQLI_ASSOC);

// Rôles actuels de l'utilisateur (séparés par virgules)
$user_roles = explode(',', $roles_str);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_username = $_POST['username'] ?? $username;
  $new_password = $_POST['password'] ?? '';
  $selected_roles = $_POST['roles'] ?? [];
  $new_roles_str = implode(',', $selected_roles);

  if (!empty($new_password)) {
    $stmt_up = $mysqli->prepare("UPDATE users SET username=?, password_hash=SHA2(?,256), role=? WHERE id=?");
    $stmt_up->bind_param("sssi", $new_username, $new_password, $new_roles_str, $id);
  } else {
    // Si pas de nouveau mot de passe, on garde l'ancien hash
    $stmt_up = $mysqli->prepare("UPDATE users SET username=?, role=? WHERE id=?");
    $stmt_up->bind_param("ssi", $new_username, $new_roles_str, $id);
  }
  $stmt_up->execute();

  header("Location: list_users.php");
  exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier Utilisateur</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Modifier l'utilisateur <?php echo htmlspecialchars($username); ?></h1>
  <form method="post" style="max-width:300px;">
    <div class="form-group">
      <label>Nom d'utilisateur</label>
      <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div class="form-group">
      <label>Nouveau Mot de passe (laisser vide pour ne pas changer)</label>
      <input type="password" name="password">
    </div>
    <div class="form-group">
      <label>Rôles (plusieurs choix possibles)</label>
      <?php foreach ($all_roles as $r): ?>
        <?php $role_name = $r['role_name']; ?>
        <div>
          <label>
            <input type="checkbox" name="roles[]" value="<?php echo htmlspecialchars($role_name); ?>"
              <?php if (in_array($role_name, $user_roles)) echo 'checked'; ?>>
            <?php echo htmlspecialchars($role_name); ?>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn">Modifier</button>
    <a href="list_users.php" class="btn btn-secondary">Annuler</a>
  </form>
</div>
</body>
</html>

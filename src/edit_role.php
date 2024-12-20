<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");

$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: manage_roles.php");
  exit();
}

// Récupérer le rôle
$stmt = $mysqli->prepare("SELECT role_name FROM roles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: manage_roles.php");
  exit();
}
$stmt->bind_result($role_name);
$stmt->fetch();

if ($role_name === 'admin') {
  // On ne modifie pas le rôle admin
  header("Location: manage_roles.php");
  exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_role_name = $_POST['role_name'] ?? $role_name;

  $stmt_up = $mysqli->prepare("UPDATE roles SET role_name=? WHERE id=?");
  $stmt_up->bind_param("si", $new_role_name, $id);
  $stmt_up->execute();

  header("Location: manage_roles.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Rôle</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Modifier le Rôle <?php echo htmlspecialchars($role_name); ?></h1>
  <form method="post" style="max-width:300px;">
    <div class="form-group">
      <label>Nouveau Nom</label>
      <input type="text" name="role_name" value="<?php echo htmlspecialchars($role_name); ?>" required>
    </div>
    <button type="submit" class="btn">Modifier</button>
    <a href="manage_roles.php" class="btn btn-secondary">Annuler</a>
  </form>
</div>
</body>
</html>

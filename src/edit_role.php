<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// ID du rôle
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: manage_roles.php");
  exit();
}

// Sélection
$stmt = $mysqli->prepare("SELECT role_name, color_code FROM roles WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows===0) {
  header("Location: manage_roles.php");
  exit();
}
$stmt->bind_result($role_name, $color_code);
$stmt->fetch();

// Pas de modif du rôle admin
if ($role_name==='admin') {
  header("Location: manage_roles.php");
  exit();
}

// Form
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $new_role_name = $_POST['role_name']  ?? $role_name;
  $new_color     = $_POST['color_code'] ?? $color_code;

  $up = $mysqli->prepare("
    UPDATE roles
    SET role_name=?, color_code=?
    WHERE id=?
  ");
  $up->bind_param("ssi", $new_role_name, $new_color, $id);
  $up->execute();

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
<style>
  .container {
    width:90%;
    max-width:1200px;
    margin:40px auto;
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
  }
  .header-actions {
    margin-bottom:20px;
    display:flex;
    gap:10px;
  }
  .btn-home {
    background-color: #2c3e50;
    color: #ecf0f1;
  }
  .btn-cancel {
    background-color: #7f8c8d;
    color: #fff;
  }
  .form-group {
    margin-bottom:15px;
  }
  .form-group label {
    display:block;
    margin-bottom:5px;
    font-weight:bold;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php" class="btn btn-home">Accueil</a>
    <a href="manage_roles.php" class="btn btn-cancel">Annuler</a>
  </div>

  <h1>Modifier le Rôle : <?php echo htmlspecialchars($role_name); ?></h1>
  <form method="post">
    <div class="form-group">
      <label>Nouveau Nom</label>
      <input type="text" name="role_name" value="<?php echo htmlspecialchars($role_name); ?>" required>
    </div>
    <div class="form-group">
      <label>Couleur</label>
      <input type="color" name="color_code" value="<?php echo htmlspecialchars($color_code); ?>">
      <small>Choisir une couleur pour ce rôle.</small>
    </div>
    <button type="submit" class="btn">Enregistrer</button>
  </form>
</div>
</body>
</html>

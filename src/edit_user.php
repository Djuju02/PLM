<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_users.php");
  exit();
}

// Récupérer l'utilisateur
$stmt = $mysqli->prepare("SELECT username, role FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows===0) {
  header("Location: list_users.php");
  exit();
}
$stmt->bind_result($username, $roles_csv);
$stmt->fetch();

if ($username==='admin') {
  // Ne pas modifier admin
  header("Location: list_users.php");
  exit();
}

// Traitement
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $new_username = $_POST['username'] ?? $username;
  // multi-select
  $selected_roles = $_POST['roles'] ?? []; // ex: ["manager","Equipe1"]
  $new_roles_csv  = implode(',', $selected_roles);

  $up = $mysqli->prepare("UPDATE users SET username=?, role=? WHERE id=?");
  $up->bind_param("ssi", $new_username, $new_roles_csv, $id);
  $up->execute();

  header("Location: list_users.php");
  exit();
}

// Découper CSV => array
$current_roles = explode(',', $roles_csv); // ex: "manager,Equipe1"
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Utilisateur</title>
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
    <a href="list_users.php" class="btn btn-cancel">Annuler</a>
  </div>

  <h1>Modifier l'Utilisateur : <?php echo htmlspecialchars($username); ?></h1>
  <form method="post">
    <div class="form-group">
      <label>Nom d'utilisateur</label>
      <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div class="form-group">
      <label>Rôles</label>
      <select name="roles[]" multiple size="4">
        <option value="admin"   <?php if(in_array('admin',   $current_roles)) echo 'selected'; ?>>admin</option>
        <option value="manager" <?php if(in_array('manager', $current_roles)) echo 'selected'; ?>>manager</option>
        <option value="Equipe1" <?php if(in_array('Equipe1', $current_roles)) echo 'selected'; ?>>Equipe1</option>
        <option value="Equipe2" <?php if(in_array('Equipe2', $current_roles)) echo 'selected'; ?>>Equipe2</option>
      </select>
      <small>Maintenir Ctrl (ou Cmd) pour sélectionner plusieurs</small>
    </div>
    <button type="submit" class="btn">Enregistrer</button>
  </form>
</div>
</body>
</html>

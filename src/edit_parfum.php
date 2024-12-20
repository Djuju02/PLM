<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_parfums.php");
  exit();
}

// Récupérer le parfum
$stmt = $mysqli->prepare("SELECT name, description, price FROM parfums WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_parfums.php");
  exit();
}
$stmt->bind_result($name, $description, $price);
$stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_name = $_POST['name'] ?? $name;
  $new_description = $_POST['description'] ?? $description;
  $new_price = $_POST['price'] ?? $price;

  $stmt_up = $mysqli->prepare("UPDATE parfums SET name=?, description=?, price=? WHERE id=?");
  $stmt_up->bind_param("ssdi", $new_name, $new_description, $new_price, $id);
  $stmt_up->execute();

  header("Location: list_parfums.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Parfum</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Modifier le Parfum <?php echo htmlspecialchars($name); ?></h1>
  <form method="post" style="max-width:400px;">
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <div class="form-group">
      <label>Prix</label>
      <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
    </div>
    <button type="submit" class="btn">Modifier</button>
    <a href="list_parfums.php" class="btn btn-secondary">Annuler</a>
  </form>
</div>
</body>
</html>

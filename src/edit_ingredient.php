<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");
$ingredient_id = $_GET['id'] ?? '';
if (empty($ingredient_id)) {
  header("Location: list_parfums.php");
  exit();
}

// Rôles
$user_roles = explode(',', $_SESSION['role']);
$is_admin = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

if (!$is_admin && !$is_manager) {
  // Pas d'accès
  header("Location: list_parfums.php");
  exit();
}

// Récupérer l'ingrédient
$stmt = $mysqli->prepare("SELECT parfum_id, name, reference, quantity, unit_price, tva FROM ingredients WHERE id=?");
$stmt->bind_param("i", $ingredient_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_parfums.php");
  exit();
}
$stmt->bind_result($parfum_id, $name, $reference, $quantity, $unit_price, $tva);
$stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_quantity = $_POST['quantity'];
  $new_unit_price = $_POST['unit_price'];
  $new_tva = $_POST['tva'];

  // Comparer anciennes et nouvelles valeurs pour enregistrer dans ingredient_changes
  $user_id = $_SESSION['user_id']; 

  // ex: changement sur quantity
  if ($new_quantity != $quantity) {
    $stmt_chg = $mysqli->prepare("INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value) VALUES (?,?,?,?,?)");
    $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field = 'quantity', $old = (string)$quantity, $new = (string)$new_quantity);
    $stmt_chg->execute();
  }

  // idem pour unit_price
  if ($new_unit_price != $unit_price) {
    $stmt_chg = $mysqli->prepare("INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value) VALUES (?,?,?,?,?)");
    $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field = 'unit_price', $old = (string)$unit_price, $new = (string)$new_unit_price);
    $stmt_chg->execute();
  }

  // idem pour tva
  if ($new_tva != $tva) {
    $stmt_chg = $mysqli->prepare("INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value) VALUES (?,?,?,?,?)");
    $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field = 'tva', $old = (string)$tva, $new = (string)$new_tva);
    $stmt_chg->execute();
  }

  // Mettre à jour l'ingrédient
  $stmt_up = $mysqli->prepare("UPDATE ingredients SET quantity=?, unit_price=?, tva=? WHERE id=?");
  $stmt_up->bind_param("dddi", $new_quantity, $new_unit_price, $new_tva, $ingredient_id);
  $stmt_up->execute();

  header("Location: parfum_detail.php?id=$parfum_id");
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Ingrédient</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Modifier Ingrédient: <?php echo htmlspecialchars($name); ?></h1>
  <p><strong>Référence :</strong> <?php echo htmlspecialchars($reference); ?></p>

  <form method="post" style="max-width:300px;">
    <div class="form-group">
      <label>Quantité</label>
      <input type="number" step="0.01" name="quantity" value="<?php echo $quantity; ?>" required>
    </div>
    <div class="form-group">
      <label>Prix Unitaire (€)</label>
      <input type="number" step="0.01" name="unit_price" value="<?php echo $unit_price; ?>" required>
    </div>
    <div class="form-group">
      <label>TVA (%)</label>
      <input type="number" step="0.01" name="tva" value="<?php echo $tva; ?>" required>
    </div>
    <button type="submit" class="btn">Enregistrer</button>
    <a href="parfum_detail.php?id=<?php echo $parfum_id; ?>" class="btn btn-secondary">Annuler</a>
  </form>
</div>
</body>
</html>

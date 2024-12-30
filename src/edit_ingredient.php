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

$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin   = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

if (!$is_admin && !$is_manager) {
  header("Location: list_parfums.php");
  exit();
}

// Récupérer l'ingrédient
$stmt = $mysqli->prepare("
  SELECT parfum_id, name, reference, quantity, unit_price, tva
  FROM ingredients
  WHERE id = ?
");
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
  $new_name       = $_POST['name']       ?? $name;
  $new_ref        = $_POST['reference']  ?? $reference;
  $new_quantity   = $_POST['quantity']   ?? $quantity;
  $new_unit_price = $_POST['unit_price'] ?? $unit_price;
  $new_tva        = $_POST['tva']        ?? $tva;
  $user_id        = $_SESSION['user_id'] ?? null;

  // Historique
  if ($user_id) {
    // Nom
    if ($new_name != $name) {
      $stmt_chg = $mysqli->prepare("
        INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (?,?,?,?,?)
      ");
      $field='name'; 
      $old=$name; 
      $new=$new_name;
      $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field, $old, $new);
      $stmt_chg->execute();
    }
    // Référence
    if ($new_ref != $reference) {
      $stmt_chg = $mysqli->prepare("
        INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (?,?,?,?,?)
      ");
      $field='reference';
      $old=$reference;
      $new=$new_ref;
      $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field, $old, $new);
      $stmt_chg->execute();
    }
    // Quantité
    if ($new_quantity != $quantity) {
      $stmt_chg = $mysqli->prepare("
        INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (?,?,?,?,?)
      ");
      $field='quantity'; 
      $old=(string)$quantity; 
      $new=(string)$new_quantity;
      $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field, $old, $new);
      $stmt_chg->execute();
    }
    // Prix unitaire
    if ($new_unit_price != $unit_price) {
      $stmt_chg = $mysqli->prepare("
        INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (?,?,?,?,?)
      ");
      $field='unit_price'; 
      $old=(string)$unit_price; 
      $new=(string)$new_unit_price;
      $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field, $old, $new);
      $stmt_chg->execute();
    }
    // TVA
    if ($new_tva != $tva) {
      $stmt_chg = $mysqli->prepare("
        INSERT INTO ingredient_changes (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (?,?,?,?,?)
      ");
      $field='tva';
      $old=(string)$tva;
      $new=(string)$new_tva;
      $stmt_chg->bind_param("iisss", $ingredient_id, $user_id, $field, $old, $new);
      $stmt_chg->execute();
    }
  }

  // Mise à jour
  $stmt_up = $mysqli->prepare("
    UPDATE ingredients
    SET name=?, reference=?, quantity=?, unit_price=?, tva=?
    WHERE id=?
  ");
  $stmt_up->bind_param("ssdddi", $new_name, $new_ref, $new_quantity, $new_unit_price, $new_tva, $ingredient_id);
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
<style>
  .container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  .header-actions {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
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
    font-weight:bold;
    margin-bottom:5px;
    display:block;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php" class="btn btn-home">Accueil</a>
    <a href="parfum_detail.php?id=<?php echo $parfum_id; ?>" class="btn btn-cancel">Annuler</a>
  </div>

  <h1>Modifier l'Ingrédient : <?php echo htmlspecialchars($name); ?></h1>
  <form method="post">
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
    </div>
    <div class="form-group">
      <label>Référence</label>
      <input type="text" name="reference" value="<?php echo htmlspecialchars($reference); ?>">
    </div>
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
  </form>
</div>
</body>
</html>

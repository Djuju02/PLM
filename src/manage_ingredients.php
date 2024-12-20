<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");
$parfum_id = $_GET['parfum_id'] ?? '';
if (empty($parfum_id)) {
  header("Location: list_parfums.php");
  exit();
}

$user_roles = explode(',', $_SESSION['role']);
$is_admin = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

if (!$is_admin && !$is_manager) {
  header("Location: parfum_detail.php?id=$parfum_id");
  exit();
}

// Ajout d'un ingrédient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
  $i_name = $_POST['name'];
  $i_ref = $_POST['reference'];
  $i_qty = $_POST['quantity'];
  $i_price = $_POST['unit_price'];
  $i_tva = $_POST['tva'];
  $stmt_i = $mysqli->prepare("INSERT INTO ingredients (parfum_id, name, reference, quantity, unit_price, tva) VALUES (?,?,?,?,?,?)");
  $stmt_i->bind_param("issddd", $parfum_id, $i_name, $i_ref, $i_qty, $i_price, $i_tva);
  $stmt_i->execute();
  header("Location: manage_ingredients.php?parfum_id=$parfum_id");
  exit();
}

// Liste des ingrédients du parfum
$ing_res = $mysqli->query("SELECT id, name, reference, quantity, unit_price, tva FROM ingredients WHERE parfum_id=$parfum_id");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gérer les Sous-Produits</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Gérer les Sous-Produits du Parfum <?php echo $parfum_id; ?></h1>
  <p><a href="parfum_detail.php?id=<?php echo $parfum_id; ?>" class="btn btn-secondary">Retour au Parfum</a></p>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Quantité</th>
        <th>Prix Unitaire</th>
        <th>TVA (%)</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($ing = $ing_res->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($ing['name']); ?></td>
        <td><?php echo htmlspecialchars($ing['reference']); ?></td>
        <td><?php echo htmlspecialchars($ing['quantity']); ?></td>
        <td><?php echo htmlspecialchars($ing['unit_price']); ?></td>
        <td><?php echo htmlspecialchars($ing['tva']); ?></td>
        <td class="actions">
          <a href="edit_ingredient.php?id=<?php echo $ing['id']; ?>" class="btn-edit">Modifier</a>
          <a href="delete_ingredient.php?id=<?php echo $ing['id']; ?>" class="btn-delete" onclick="return confirm('Supprimer cet ingrédient ?')">Supprimer</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Ajouter un nouvel ingrédient</h2>
  <form method="post" style="max-width:300px;">
    <input type="hidden" name="add_ingredient" value="1">
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Référence</label>
      <input type="text" name="reference" required>
    </div>
    <div class="form-group">
      <label>Quantité</label>
      <input type="number" step="0.01" name="quantity" required value="1.00">
    </div>
    <div class="form-group">
      <label>Prix Unitaire</label>
      <input type="number" step="0.01" name="unit_price" required value="5.00">
    </div>
    <div class="form-group">
      <label>TVA (%)</label>
      <input type="number" step="0.01" name="tva" required value="20.00">
    </div>
    <button type="submit" class="btn">Ajouter</button>
  </form>
</div>
</body>
</html>

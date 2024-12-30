<?php
session_start();
if (!isset($_SESSION['username']) || strpos($_SESSION['role'], 'admin') === false) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion : " . $mysqli->connect_error);
}

// Ajout d’un ingrédient global
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_global_ingredient'])) {
  $g_name  = $_POST['name']       ?? '';
  $g_ref   = $_POST['reference']  ?? '';
  $g_price = $_POST['default_unit_price'] ?? '5.00';
  $g_tva   = $_POST['default_tva']       ?? '20.00';

  $stmt_g = $mysqli->prepare("
    INSERT INTO ingredients_global (name, reference, default_unit_price, default_tva)
    VALUES (?,?,?,?)
  ");
  $stmt_g->bind_param("ssdd", $g_name, $g_ref, $g_price, $g_tva);
  $stmt_g->execute();
  header("Location: manage_ingredients_global.php");
  exit();
}

// Liste des ingrédients globaux
$res_global = $mysqli->query("
  SELECT id, name, reference, default_unit_price, default_tva
  FROM ingredients_global
  ORDER BY name ASC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gérer les Sous-Produits (BOM)</title>
<link rel="stylesheet" href="styles.css">
<style>
  .container {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  .header-actions {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: #fff;
  }
  table th, table td {
    border: 1px solid #bdc3c7;
    padding: 8px;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
  </div>

  <h1>Gérer le Catalogue Global de Sous-Produits</h1>
  <p>Voici la liste globale des ingrédients disponibles. Ce sont les prix et TVA par défaut.</p>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Prix Unitaire par Défaut</th>
        <th>TVA (%)</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($g = $res_global->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($g['name']); ?></td>
        <td><?php echo htmlspecialchars($g['reference']); ?></td>
        <td><?php echo number_format($g['default_unit_price'], 2, ',', ' '); ?> €</td>
        <td><?php echo number_format($g['default_tva'], 2, ',', ' '); ?> %</td>
        <td class="actions">
          <a href="edit_ingredient_global.php?id=<?php echo $g['id']; ?>" class="btn-edit">
            Modifier
          </a>
          <a href="delete_ingredient_global.php?id=<?php echo $g['id']; ?>" class="btn-delete"
             onclick="return confirm('Supprimer cet ingrédient ?');">
            Supprimer
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Ajouter un Sous-Produit Global</h2>
  <form method="post" style="max-width:300px;">
    <input type="hidden" name="add_global_ingredient" value="1">
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Référence</label>
      <input type="text" name="reference" required>
    </div>
    <div class="form-group">
      <label>Prix Unitaire par Défaut (€)</label>
      <input type="number" step="0.01" name="default_unit_price"
             value="5.00" required>
    </div>
    <div class="form-group">
      <label>TVA (%)</label>
      <input type="number" step="0.01" name="default_tva"
             value="20.00" required>
    </div>
    <button type="submit" class="btn">Ajouter</button>
  </form>
</div>
</body>
</html>

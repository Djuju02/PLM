<?php
session_start();
if (!isset($_SESSION['username']) || strpos($_SESSION['role'], 'admin') === false) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Récupérer la liste des ingrédients globaux
$global_res = $mysqli->query("SELECT id, name, reference, default_unit_price, default_tva FROM ingredients_global ORDER BY name ASC");

// Création d’un parfum
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_parfum'])) {
  $p_name = $_POST['name'];
  $p_desc = $_POST['description'];
  $p_team = $_POST['team'];

  // Générer référence automatiquement
  $count_res = $mysqli->query("SELECT COUNT(*) as c FROM parfums");
  $count = $count_res->fetch_assoc()['c'];
  $ref = "PAR-".str_pad($count+1, 3, '0', STR_PAD_LEFT);

  // Créer le parfum en R&D, version=1, pas de prix direct
  $stmt_p = $mysqli->prepare("INSERT INTO parfums (name, description, team, version, lifecycle_stage, reference) VALUES (?,?,?,1,'R&D',?)");
  $stmt_p->bind_param("ssss", $p_name, $p_desc, $p_team, $ref);
  $stmt_p->execute();
  
  $new_parfum_id = $mysqli->insert_id;
  
  // Ajouter les ingrédients sélectionnés
  if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
    foreach ($_POST['ingredients'] as $gid) {
      $qty = $_POST['qty_'.$gid] ?? 1.00;
      // Récupérer infos par défaut de l'ingrédient global
      $gstmt = $mysqli->prepare("SELECT default_unit_price, default_tva, reference FROM ingredients_global WHERE id=?");
      $gstmt->bind_param("i", $gid);
      $gstmt->execute();
      $gstmt->store_result();
      if ($gstmt->num_rows > 0) {
        $gstmt->bind_result($dup, $dtva, $gref);
        $gstmt->fetch();
        // Insérer dans ingredients liés à ce parfum
        $istmt = $mysqli->prepare("INSERT INTO ingredients (parfum_id, name, reference, quantity, unit_price, tva) 
                                   SELECT ?, name, reference, ?, ?, ? FROM ingredients_global WHERE id=?");
        $istmt->bind_param("idddi", $new_parfum_id, $qty, $dup, $dtva, $gid);
        $istmt->execute();
      }
    }
  }

  header("Location: manage_parfums.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer un Parfum</title>
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
    display:flex;
    justify-content: space-between;
    align-items: center;
  }
  .form-group {
    margin-bottom:15px;
  }
  .form-group label {
    display:block;
    margin-bottom:5px;
    font-weight:bold;
  }
  table {
    border-collapse:collapse;
    width:100%;
    margin-top:20px;
  }
  table th, table td {
    border:1px solid #bdc3c7;
    padding:8px;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="manage_parfums.php" class="btn btn-secondary">Retour à l'accueil</a>
  </div>
  
  <h1>Créer un Nouveau Parfum</h1>
  <form method="post">
    <input type="hidden" name="create_parfum" value="1">
    <div class="form-group">
      <label>Nom du parfum</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"></textarea>
    </div>
    <div class="form-group">
      <label>Équipe</label>
      <select name="team">
        <option value="Equipe1">Equipe1</option>
        <option value="Equipe2">Equipe2</option>
      </select>
    </div>
    
    <h2>Ajouter des Sous-Produits (Ingrédients) Existants</h2>
    <p>Choisissez parmi les ingrédients déjà existants. Vous pourrez en créer de nouveaux via la gestion globale des ingrédients.</p>
    <table>
      <thead>
        <tr>
          <th>Ajouter</th>
          <th>Nom</th>
          <th>Référence</th>
          <th>Prix Unitaire par Défaut</th>
          <th>TVA</th>
          <th>Quantité</th>
        </tr>
      </thead>
      <tbody>
        <?php while($g = $global_res->fetch_assoc()): ?>
        <tr>
          <td><input type="checkbox" name="ingredients[]" value="<?php echo $g['id']; ?>"></td>
          <td><?php echo htmlspecialchars($g['name']); ?></td>
          <td><?php echo htmlspecialchars($g['reference']); ?></td>
          <td><?php echo htmlspecialchars($g['default_unit_price']); ?> €</td>
          <td><?php echo htmlspecialchars($g['default_tva']); ?> %</td>
          <td><input type="number" step="0.01" name="qty_<?php echo $g['id']; ?>" value="1.00" style="width:80px;"></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <button type="submit" class="btn">Créer</button>
  </form>
</div>
</body>
</html>

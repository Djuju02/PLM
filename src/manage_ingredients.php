<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion : " . $mysqli->connect_error);
}

$parfum_id = $_GET['parfum_id'] ?? '';
if (empty($parfum_id)) {
  header("Location: list_parfums.php");
  exit();
}

// Rôles
$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin   = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

// Accès refusé si ni admin ni manager
if (!$is_admin && !$is_manager) {
  header("Location: parfum_detail.php?id=$parfum_id");
  exit();
}

// AJOUT d’ingrédients choisis depuis ingredients_global
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_selected'])) {
  if (!empty($_POST['global_ids']) && is_array($_POST['global_ids'])) {
    foreach($_POST['global_ids'] as $gid) {
      // On récupère la quantité saisie
      $qty = $_POST["qty_$gid"] ?? '1.00';

      // Trouver l’ingrédient global
      $stmtF = $mysqli->prepare("
        SELECT name, reference, default_unit_price, default_tva
        FROM ingredients_global
        WHERE id=?
      ");
      $stmtF->bind_param("i", $gid);
      $stmtF->execute();
      $resF = $stmtF->get_result();
      if ($rowF=$resF->fetch_assoc()) {
        $gName = $rowF['name'];
        $gRef  = $rowF['reference'];
        $gPrice= $rowF['default_unit_price'];
        $gTva  = $rowF['default_tva'];

        // Vérifier si un ingrédient (name, reference) existe déjà pour ce parfum
        $stmtChk = $mysqli->prepare("
          SELECT id, quantity
          FROM ingredients
          WHERE parfum_id=? AND name=? AND reference=?
          LIMIT 1
        ");
        $stmtChk->bind_param("iss", $parfum_id, $gName, $gRef);
        $stmtChk->execute();
        $rChk = $stmtChk->get_result();

        if ($rowChk=$rChk->fetch_assoc()) {
          // => Il existe déjà => on cumule la quantité
          $newQty = (float)$rowChk['quantity'] + (float)$qty;
          $existingId = (int)$rowChk['id'];

          $stmtUp = $mysqli->prepare("
            UPDATE ingredients
            SET quantity=?
            WHERE id=?
          ");
          $stmtUp->bind_param("di", $newQty, $existingId);
          $stmtUp->execute();
        } else {
          // => Pas trouvé => on insère
          $stmtIns = $mysqli->prepare("
            INSERT INTO ingredients
            (parfum_id, name, reference, quantity, unit_price, tva)
            VALUES (?,?,?,?,?,?)
          ");
          $stmtIns->bind_param("issddd",
            $parfum_id, $gName, $gRef, $qty, $gPrice, $gTva
          );
          $stmtIns->execute();
        }
      }
    }
  }
  header("Location: manage_ingredients.php?parfum_id=$parfum_id");
  exit();
}

// Liste des ingrédients actuellement dans le parfum
$resIng = $mysqli->query("
  SELECT id, name, reference, quantity, unit_price, tva
  FROM ingredients
  WHERE parfum_id=$parfum_id
  ORDER BY name ASC
");

// Recherche dans ingredients_global
$search = $_GET['search'] ?? '';
$conds  = [];
if (!empty($search)) {
  $esc = $mysqli->real_escape_string($search);
  $conds[] = "(name LIKE '%$esc%' OR reference LIKE '%$esc%')";
}
$qGlobal = "SELECT id, name, reference, default_unit_price, default_tva
            FROM ingredients_global";
if (!empty($conds)) {
  $qGlobal .= " WHERE " . implode(' AND ', $conds);
}
$qGlobal .= " ORDER BY name ASC";
$resGlobal = $mysqli->query($qGlobal);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gérer les Sous-Produits du Parfum <?php echo htmlspecialchars($parfum_id); ?></title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .container {
      width: 90%;
      max-width:1200px;
      margin:40px auto;
      background:#fff;
      padding:30px;
      border-radius:8px;
      box-shadow:0 0 10px rgba(0,0,0,0.1);
    }
    table {
      width:100%; border-collapse:collapse; margin-top:20px; background:#fff;
    }
    table th, table td {
      border:1px solid #bdc3c7; padding:8px;
    }
    .quantity-input {
      width: 60px; text-align:right;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>Gérer les Sous-Produits (Ingrédients) pour le Parfum #<?php echo htmlspecialchars($parfum_id); ?></h1>
  <p>
    <a href="parfum_detail.php?id=<?php echo $parfum_id; ?>" class="btn btn-secondary">
      ← Retour au Parfum
    </a>
  </p>

  <!-- Liste actuelle des ingrédients de CE parfum -->
  <h2>Ingrédients Actuels</h2>
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
      <?php while($ing=$resIng->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($ing['name']); ?></td>
        <td><?php echo htmlspecialchars($ing['reference']); ?></td>
        <td><?php echo htmlspecialchars($ing['quantity']); ?></td>
        <td><?php echo htmlspecialchars($ing['unit_price']); ?></td>
        <td><?php echo htmlspecialchars($ing['tva']); ?></td>
        <td>
          <a href="edit_ingredient.php?id=<?php echo $ing['id']; ?>" class="btn-edit">Modifier</a>
          <a href="delete_ingredient.php?id=<?php echo $ing['id']; ?>" class="btn-delete"
             onclick="return confirm('Supprimer cet ingrédient ?');">
            Supprimer
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <hr>

  <!-- Recherche + ajout depuis ingredients_global -->
  <h2>Ajouter un Sous-Produit depuis le Catalogue</h2>
  <form method="get" style="margin-bottom:20px;">
    <!-- on conserve le parfum_id dans l’URL -->
    <input type="hidden" name="parfum_id" value="<?php echo htmlspecialchars($parfum_id); ?>">
    <div style="margin-bottom:10px;">
      <input type="text" name="search"
             placeholder="Rechercher un ingrédient global..."
             value="<?php echo htmlspecialchars($search); ?>"
             style="padding:8px; border:1px solid #bdc3c7; border-radius:5px; width:200px;">
      <button type="submit" class="btn">Rechercher</button>
    </div>
  </form>

  <form method="post">
    <input type="hidden" name="add_selected" value="1">
    <table>
      <thead>
        <tr>
          <th>Ajouter</th>
          <th>Nom</th>
          <th>Référence</th>
          <th>Quantité</th>
          <th>Prix Unitaire (défaut)</th>
          <th>TVA (défaut)</th>
        </tr>
      </thead>
      <tbody>
        <?php while($g=$resGlobal->fetch_assoc()):
          $gid   = $g['id'];
          $gName = $g['name'];
          $gRef  = $g['reference'];
          $gPrice= $g['default_unit_price'];
          $gTva  = $g['default_tva'];
        ?>
        <tr>
          <td>
            <input type="checkbox" name="global_ids[]" value="<?php echo $gid; ?>">
          </td>
          <td><?php echo htmlspecialchars($gName); ?></td>
          <td><?php echo htmlspecialchars($gRef); ?></td>
          <td>
            <input type="number" step="0.01" name="qty_<?php echo $gid; ?>"
                   value="1.00" class="quantity-input">
          </td>
          <!-- On n’autorise pas la modification du prix / tva ici -->
          <td><?php echo number_format($gPrice, 2, ',', ' '); ?> €</td>
          <td><?php echo number_format($gTva, 2, ',', ' '); ?> %</td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    <button type="submit" class="btn btn-success" style="margin-top:10px;">
      Ajouter les Ingrédients Sélectionnés
    </button>
  </form>

</div>
</body>
</html>

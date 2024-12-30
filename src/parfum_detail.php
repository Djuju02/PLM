<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion : " . $mysqli->connect_error);
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_parfums.php");
  exit();
}

$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin   = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);
$user_id    = $_SESSION['user_id'] ?? null;

/*--------------------------------------------------------------------------
   1) Ajout d'un commentaire
--------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
  if (!$user_id) {
    header("Location: parfum_detail.php?id=$id&error=no_user_id");
    exit();
  }
  $message = $_POST['message'] ?? '';
  if (!empty($message)) {
    $stmt_c = $mysqli->prepare("
      INSERT INTO comments (parfum_id, user_id, message)
      VALUES (?,?,?)
    ");
    $stmt_c->bind_param("iis", $id, $user_id, $message);
    $stmt_c->execute();
  }
  header("Location: parfum_detail.php?id=$id");
  exit();
}

/*--------------------------------------------------------------------------
   2) Mettre à jour la date last_read_at (table user_parfum_read)
--------------------------------------------------------------------------*/
if ($user_id) {
  $stmtUpdateRead = $mysqli->prepare("
    INSERT INTO user_parfum_read (user_id, parfum_id, last_read_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE last_read_at=NOW()
  ");
  $stmtUpdateRead->bind_param("ii", $user_id, $id);
  $stmtUpdateRead->execute();
}

/*--------------------------------------------------------------------------
   Préparer/réussir la création des dossiers images/ et fichiers/
--------------------------------------------------------------------------*/
$imagesDir = __DIR__ . '/images';
if (!is_dir($imagesDir)) {
  mkdir($imagesDir, 0777, true);
}

$filesDir = __DIR__ . '/fichiers';
if (!is_dir($filesDir)) {
  mkdir($filesDir, 0777, true);
}

/*--------------------------------------------------------------------------
   3) Gérer l'upload de l'IMAGE PRINCIPALE dans /images
--------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_main_image'])) {
  if (!empty($_FILES['main_image']['name'])) {
    $fileName = 'parfum_main_'.$id.'_'.time().'_'.basename($_FILES['main_image']['name']);
    $destPath = $imagesDir . '/' . $fileName;

    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $destPath)) {
      // Mettre à jour la table parfums : image_filename
      $stmtImg = $mysqli->prepare("
        UPDATE parfums
        SET image_filename=?
        WHERE id=?
      ");
      $stmtImg->bind_param("si", $fileName, $id);
      $stmtImg->execute();

      // Historique (ingredient_changes) => on met ingredient_id=0
      $stmtHist = $mysqli->prepare("
        INSERT INTO ingredient_changes 
        (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (0, ?, 'main_image_upload', '', ?)
      ");
      $stmtHist->bind_param("is", $user_id, $fileName);
      $stmtHist->execute();
    }
  }
  header("Location: parfum_detail.php?id=$id");
  exit();
}

/*--------------------------------------------------------------------------
   4) Gérer l'upload de FICHIERS LIÉS (type doc, PDF, 3D...) dans /fichiers
--------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_attachment'])) {
  if (!empty($_FILES['attachment']['name'])) {
    $attachName = 'parfum_file_'.$id.'_'.time().'_'.basename($_FILES['attachment']['name']);
    $destPath = $filesDir . '/' . $attachName;

    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
      // Insérer dans parfum_files
      $stmtAF = $mysqli->prepare("
        INSERT INTO parfum_files (parfum_id, file_name, user_id)
        VALUES (?,?,?)
      ");
      $stmtAF->bind_param("isi", $id, $attachName, $user_id);
      $stmtAF->execute();

      // Historique => file_uploaded
      $stmtHist2 = $mysqli->prepare("
        INSERT INTO ingredient_changes 
        (ingredient_id, user_id, field_changed, old_value, new_value)
        VALUES (0, ?, 'file_uploaded', '', ?)
      ");
      $stmtHist2->bind_param("is", $user_id, $attachName);
      $stmtHist2->execute();
    }
  }
  header("Location: parfum_detail.php?id=$id");
  exit();
}

/*--------------------------------------------------------------------------
   5) Récupérer le parfum (dont image_filename)
--------------------------------------------------------------------------*/
$stmt = $mysqli->prepare("
  SELECT name, description, price, team,
         version, lifecycle_stage, reference,
         image_filename
  FROM parfums
  WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_parfums.php");
  exit();
}
$stmt->bind_result($name, $description, $price, $team,
                   $version, $lifecycle_stage, $reference,
                   $image_filename);
$stmt->fetch();

/*--------------------------------------------------------------------------
   6) Barre de progression : R&D => 33%, Pré-prod => 66%, Production => 100%
--------------------------------------------------------------------------*/
$progress = 0;
$color    = '#3498db';
switch ($lifecycle_stage) {
  case 'R&D':       $progress=33;  $color='#3498db';  break;
  case 'Pré-prod':  $progress=66;  $color='#e67e22';  break;
  case 'Production':$progress=100; $color='#2ecc71';  break;
}

/*--------------------------------------------------------------------------
   7) Liste des Ingrédients (BOM)
--------------------------------------------------------------------------*/
$ing_result = $mysqli->query("
  SELECT id, name, reference, quantity, unit_price, tva
  FROM ingredients
  WHERE parfum_id=$id
  ORDER BY name ASC
");

/*--------------------------------------------------------------------------
   8) Commentaires
--------------------------------------------------------------------------*/
$comments_result = $mysqli->query("
  SELECT c.message, c.created_at, u.username
  FROM comments c
  JOIN users u ON c.user_id=u.id
  WHERE c.parfum_id=$id
  ORDER BY c.created_at DESC
");

/*--------------------------------------------------------------------------
   9) Historique : table ingredient_changes
--------------------------------------------------------------------------*/
$history_result = $mysqli->query("
  SELECT p.name AS produit, ic.field_changed, ic.old_value,
         ic.new_value, ic.changed_at, us.username
  FROM ingredient_changes ic
  JOIN ingredients ing ON ic.ingredient_id=ing.id OR ic.ingredient_id=0
  JOIN parfums p       ON ing.parfum_id=p.id OR p.id=$id
  JOIN users us        ON ic.user_id=us.id
  WHERE p.id=$id
  ORDER BY ic.changed_at DESC
");

/*--------------------------------------------------------------------------
   10) Changement de lifecycle
--------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_stage'])) {
  if ($is_admin || $is_manager) {
    $new_stage = $_POST['change_stage'];
    $stmt_up = $mysqli->prepare("
      UPDATE parfums
      SET lifecycle_stage=?
      WHERE id=?
    ");
    $stmt_up->bind_param("si", $new_stage, $id);
    $stmt_up->execute();
    header("Location: parfum_detail.php?id=$id");
    exit();
  }
}

/*--------------------------------------------------------------------------
   11) Fichiers liés => table parfum_files
--------------------------------------------------------------------------*/
$filesRes = $mysqli->query("
  SELECT id, file_name, uploaded_at, user_id
  FROM parfum_files
  WHERE parfum_id=$id
  ORDER BY uploaded_at DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détails du Parfum - <?php echo htmlspecialchars($name); ?></title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .info-block { margin-bottom: 20px; }
    .info-block p { margin: 5px 0; }
    .history-table td, .history-table th { padding: 8px; }
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .comments-section { margin-bottom: 20px; }
    .comment {
      margin: 10px 0;
      padding: 10px;
      background-color: #ecf0f1;
      border-left: 3px solid #3498db;
    }
    #commentForm textarea { min-height: 80px; }
    .progress-bar {
      background: #bdc3c7; 
      border-radius:5px; 
      height:20px; 
      width:100%; 
      margin:10px 0;
      position: relative;
    }
    .progress-bar-inner {
      width: <?php echo $progress; ?>%; 
      background: <?php echo $color; ?>; 
      height:20px; 
      border-radius:5px;
    }
    .header-actions {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    /* Image de prévisualisation */
    .parfum-image {
      max-width: 300px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      display: block;
    }
    .files-section {
      margin-top: 30px;
    }
    .files-section table th,
    .files-section table td {
      border: 1px solid #bdc3c7;
      padding: 8px;
    }
  </style>
</head>
<body>
<div class="container">

  <!-- Bouton retour -->
  <div class="header-actions">
    <a href="list_parfums.php" class="btn btn-secondary">Retour à la liste</a>
  </div>

  <h1><?php echo htmlspecialchars($name); ?></h1>

  <!-- Image de prévisualisation si un filename est renseigné -->
  <?php if (!empty($image_filename)): ?>
    <img src="images/<?php echo htmlspecialchars($image_filename); ?>"
         alt="Image du Parfum"
         class="parfum-image">
  <?php endif; ?>

  <!-- Formulaire pour uploader / remplacer l'image principale -->
  <?php if ($is_admin || $is_manager): ?>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;">
      <label><strong>Changer l'Image Principale :</strong></label>
      <input type="file" name="main_image" accept="image/*">
      <button type="submit" name="upload_main_image" class="btn">Uploader</button>
    </form>
  <?php endif; ?>

  <!-- Infos principales -->
  <div class="info-block">
    <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($description)); ?></p>
    <p><strong>Référence :</strong> <?php echo htmlspecialchars($reference); ?></p>
    <p><strong>Équipe(s) :</strong> <?php echo htmlspecialchars($team); ?></p>
    <p><strong>Version :</strong> <?php echo htmlspecialchars($version); ?></p>
    <p><strong>Étape du cycle de vie :</strong> <?php echo htmlspecialchars($lifecycle_stage); ?></p>

    <!-- Barre de progression -->
    <div class="progress-bar">
      <div class="progress-bar-inner"></div>
    </div>
    
    <!-- Bouton changer l'étape -->
    <?php if ($is_admin || $is_manager): ?>
      <form method="post">
        <?php if ($lifecycle_stage === 'R&D'): ?>
          <button name="change_stage" value="Pré-prod" class="btn">Passer en Pré-prod</button>
        <?php elseif ($lifecycle_stage === 'Pré-prod'): ?>
          <button name="change_stage" value="Production" class="btn">Passer en Prod</button>
        <?php elseif ($lifecycle_stage === 'Production'): ?>
          <button name="change_stage" value="Pré-prod" class="btn">Rétrograder en Pré-prod</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>

    <p><strong>Prix de base :</strong> 
       <?php echo number_format($price, 2, ',', ' '); ?> €</p>
  </div>

  <!-- BOM : liste des ingrédients, sans colonne "Actions" -->
  <h2>Ingrédients (BOM)</h2>
  <p>
    <a href="manage_ingredients.php?parfum_id=<?php echo $id; ?>" class="btn">
      Gérer les Sous-Produits
    </a>
  </p>
  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Quantité</th>
        <th>Prix Unitaire (€)</th>
        <th>TVA (%)</th>
        <th>Total TTC (€)</th>
      </tr>
    </thead>
    <tbody>
      <?php while($ing = $ing_result->fetch_assoc()):
        $qty  = $ing['quantity'];
        $unit = $ing['unit_price'];
        $tva  = $ing['tva'];
        $total_ht  = $qty * $unit;
        $total_ttc = $total_ht * (1 + $tva / 100);
      ?>
      <tr>
        <td><?php echo htmlspecialchars($ing['name']); ?></td>
        <td><?php echo htmlspecialchars($ing['reference']); ?></td>
        <td><?php echo htmlspecialchars($qty); ?></td>
        <td><?php echo number_format($unit, 2, ',', ' '); ?></td>
        <td><?php echo htmlspecialchars($tva); ?></td>
        <td><?php echo number_format($total_ttc, 2, ',', ' '); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Historique des Modifications (ingredients & files) -->
  <h2>Historique des Modifications</h2>
  <table class="history-table">
    <thead>
      <tr>
        <th>Produit</th>
        <th>Champ modifié</th>
        <th>Ancienne valeur</th>
        <th>Nouvelle valeur</th>
        <th>Date</th>
        <th>Modifié par</th>
      </tr>
    </thead>
    <tbody>
      <?php while($h = $history_result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($h['produit']); ?></td>
        <td><?php echo htmlspecialchars($h['field_changed']); ?></td>
        <td><?php echo htmlspecialchars($h['old_value']); ?></td>
        <td><?php echo htmlspecialchars($h['new_value']); ?></td>
        <td><?php echo $h['changed_at']; ?></td>
        <td><?php echo htmlspecialchars($h['username']); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Commentaires -->
  <h2>Commentaires</h2>
  <div class="comments-section" style="max-height:300px; overflow:auto;">
    <?php while($c = $comments_result->fetch_assoc()): ?>
      <div class="comment">
        <p>
          <strong><?php echo htmlspecialchars($c['username']); ?> :</strong>
          <?php echo nl2br(htmlspecialchars($c['message'])); ?>
        </p>
        <small><?php echo $c['created_at']; ?></small>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- Form d'ajout de commentaire -->
  <h3>Ajouter un commentaire</h3>
  <form method="post" id="commentForm">
    <textarea name="message" required placeholder="Votre commentaire"></textarea>
    <button type="submit" name="new_comment" class="btn">Commenter</button>
  </form>

  <!-- Section FICHIERS JOINTS -->
  <div class="files-section">
    <h2>Fichiers Liés</h2>
    <table>
      <thead>
        <tr>
          <th>Nom du Fichier</th>
          <th>Date d'Upload</th>
          <th>Télécharger</th>
        </tr>
      </thead>
      <tbody>
      <?php while($f = $filesRes->fetch_assoc()):
        $fName   = $f['file_name'];
        $fDate   = $f['uploaded_at'];
        // On construit l'URL dans le dossier "fichiers"
        $fileUrl = 'fichiers/' . rawurlencode($fName);
      ?>
        <tr>
          <td><?php echo htmlspecialchars($fName); ?></td>
          <td><?php echo $fDate; ?></td>
          <td>
            <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn-secondary">
              Voir / Télécharger
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>

    <?php if ($is_admin || $is_manager): ?>
    <!-- Form pour ajouter un fichier -->
    <form method="post" enctype="multipart/form-data" style="margin-top:20px;">
      <label><strong>Ajouter un Fichier :</strong></label><br>
      <input type="file" name="attachment" required>
      <button type="submit" name="upload_attachment" class="btn">Uploader</button>
    </form>
    <?php endif; ?>
  </div>

</div>
</body>
</html>

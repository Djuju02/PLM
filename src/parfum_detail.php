<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_parfums.php");
  exit();
}

// Rôles
$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

// Avant de fetcher les commentaires, on traite l'ajout s'il y a un POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_comment'])) {
  if (!isset($_SESSION['user_id'])) {
    // Si l'user_id n'est pas défini, on ne peut pas insérer
    // Redirection ou message d'erreur
    header("Location: parfum_detail.php?id=$id&error=no_user_id");
    exit();
  }
  
  $user_id = $_SESSION['user_id'];
  $message = $_POST['message'] ?? '';
  if (!empty($message)) {
    $stmt_c = $mysqli->prepare("INSERT INTO comments (parfum_id, user_id, message) VALUES (?,?,?)");
    $stmt_c->bind_param("iis", $id, $user_id, $message);
    $stmt_c->execute();
  }
  // On redirige pour éviter resoumission du formulaire
  header("Location: parfum_detail.php?id=$id");
  exit();
}

// Récupérer le parfum
$stmt = $mysqli->prepare("SELECT name, description, price, team, version, lifecycle_stage FROM parfums WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_parfums.php");
  exit();
}
$stmt->bind_result($name, $description, $price, $team, $version, $lifecycle_stage);
$stmt->fetch();

// Ingrédients
$ing_result = $mysqli->query("SELECT id, name, reference, quantity, unit_price, tva FROM ingredients WHERE parfum_id=$id");

// Après insertion, on refait la requête commentaires
$comments_result = $mysqli->query("SELECT c.message, c.created_at, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.parfum_id=$id ORDER BY c.created_at DESC");

// Historique
$history_result = $mysqli->query("
  SELECT ic.field_changed, ic.old_value, ic.new_value, ic.changed_at, us.username
  FROM ingredient_changes ic
  JOIN ingredients ing ON ic.ingredient_id=ing.id
  JOIN users us ON ic.user_id=us.id
  WHERE ing.parfum_id=$id
  ORDER BY ic.changed_at DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détails du Parfum - <?php echo htmlspecialchars($name); ?></title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .info-block {
      margin-bottom: 20px;
    }
    .info-block p {
      margin: 5px 0;
    }
    .history-table td, .history-table th {
      padding: 8px;
    }
    /* Ajout de marges supplémentaires */
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .comments-section {
      margin-bottom: 20px;
    }
    .comment {
      margin: 10px 0;
      padding: 10px;
      background-color: #ecf0f1;
      border-left: 3px solid #3498db;
    }
    #commentForm textarea {
      min-height: 80px;
    }
  </style>
</head>
<body>
<div class="container">
  <h1><?php echo htmlspecialchars($name); ?></h1>
  <div class="info-block">
    <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($description)); ?></p>
    <p><strong>Équipe :</strong> <?php echo htmlspecialchars($team); ?></p>
    <p><strong>Version :</strong> <?php echo htmlspecialchars($version); ?></p>
    <p><strong>Étape du cycle de vie :</strong> <?php echo htmlspecialchars($lifecycle_stage); ?></p>
    <p><strong>Prix de base :</strong> <?php echo number_format($price, 2, ',', ' '); ?> €</p>
  </div>

  <h2>Ingrédients (BOM)</h2>
  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Quantité</th>
        <th>Prix Unitaire (€)</th>
        <th>TVA (%)</th>
        <th>Total TTC (€)</th>
        <?php if ($is_admin || $is_manager): ?>
          <th>Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php while($ing = $ing_result->fetch_assoc()):
        $unit = $ing['unit_price'];
        $tva = $ing['tva'];
        $qty = $ing['quantity'];
        $total_ht = $unit * $qty;
        $total_ttc = $total_ht * (1 + $tva/100);
      ?>
      <tr>
        <td><?php echo htmlspecialchars($ing['name']); ?></td>
        <td><?php echo htmlspecialchars($ing['reference']); ?></td>
        <td><?php echo $qty; ?></td>
        <td><?php echo number_format($unit, 2, ',', ' '); ?></td>
        <td><?php echo $tva; ?></td>
        <td><?php echo number_format($total_ttc, 2, ',', ' '); ?></td>
        <?php if ($is_admin || $is_manager): ?>
          <td><a href="edit_ingredient.php?id=<?php echo $ing['id']; ?>" class="btn-edit">Modifier</a></td>
        <?php endif; ?>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Historique des Modifications sur les Ingrédients</h2>
  <table class="history-table">
    <thead>
      <tr>
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
        <td><?php echo htmlspecialchars($h['field_changed']); ?></td>
        <td><?php echo htmlspecialchars($h['old_value']); ?></td>
        <td><?php echo htmlspecialchars($h['new_value']); ?></td>
        <td><?php echo $h['changed_at']; ?></td>
        <td><?php echo htmlspecialchars($h['username']); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Commentaires</h2>
  <div class="comments-section" style="max-height:300px; overflow:auto;">
    <?php while($c = $comments_result->fetch_assoc()): ?>
      <div class="comment">
        <p><strong><?php echo htmlspecialchars($c['username']); ?>:</strong> <?php echo nl2br(htmlspecialchars($c['message'])); ?></p>
        <small><?php echo $c['created_at']; ?></small>
      </div>
    <?php endwhile; ?>
  </div>

  <h3>Ajouter un commentaire</h3>
  <form method="post" id="commentForm">
    <textarea name="message" required placeholder="Votre commentaire"></textarea>
    <button type="submit" name="new_comment" class="btn">Commenter</button>
  </form>

  <p><a href="list_parfums.php" class="btn btn-secondary">Retour à la liste</a></p>
</div>
</body>
</html>

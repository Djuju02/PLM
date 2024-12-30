<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

// Connexion
$mysqli = new mysqli("db", "root", "root", "plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion : " . $mysqli->connect_error);
}

// Rôles
$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_manager = in_array('manager', $user_roles);
$is_admin   = in_array('admin', $user_roles);

// Équipe
$team = null;
foreach ($user_roles as $r) {
  if (stripos($r, 'Equipe') !== false) {
    $team = $r;
    break;
  }
}

$user_id = $_SESSION['user_id'] ?? null;

// Filtres
$search        = $_GET['search']        ?? '';
$filter_unread = isset($_GET['filter_unread']);
$order_by      = $_GET['order_by']      ?? 'ASC';
$order_by      = ($order_by==='DESC') ? 'DESC' : 'ASC';

// Base query
$query = "
  SELECT p.id, p.name, p.description, p.price, 
         p.reference, p.lifecycle_stage, p.team
  FROM parfums p
";

$conditions = [];

// Filtrer par équipe si pas admin
if (!$is_admin && $team) {
  $conditions[] = "p.team LIKE '%{$mysqli->real_escape_string($team)}%'";
}

// Recherche par nom/référence/team
if (!empty($search)) {
  $esc = $mysqli->real_escape_string($search);
  $conditions[] = "(
    p.name LIKE '%$esc%' 
    OR p.reference LIKE '%$esc%' 
    OR p.team LIKE '%$esc%'
  )";
}

// **On NE fait plus** la jointure sur `comments` pour le filter_unread, 
// car on va gérer ça PAR parfum en bas dans la boucle.

if (!empty($conditions)) {
  $query .= " WHERE " . implode(" AND ", $conditions);
}

// Tri
$query .= " ORDER BY p.name $order_by";

$result = $mysqli->query($query);
if (!$result) {
  die("Erreur requête: " . $mysqli->error);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des Parfums</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    @media (max-width: 600px) {
      .actions a { display: block; margin-bottom: 5px; }
    }
    .state-badge {
      display: inline-block; font-weight: bold; padding: 5px 10px;
      color: #fff; border-radius: 5px; text-align:center;
    }
    .state-rd       { background: #3498db; }
    .state-preprod  { background: #e67e22; }
    .state-prod     { background: #2ecc71; }
    .team-button {
      display:inline-block; margin:2px; padding:5px 8px;
      background-color:#7f8c8d; color:#fff; border:none;
      border-radius:4px; cursor:default; font-weight:bold;
    }
  </style>
</head>
<body>
<div class="container">
  <p><a href="home.php" class="btn btn-secondary">Retour à l'accueil</a></p>
  <h1>Liste des Parfums</h1>

  <!-- Formulaire de filtres -->
  <form method="get" style="margin-bottom:20px;">
    <div style="margin-bottom:10px;">
      <input type="text" name="search"
             placeholder="Rechercher par nom, référence, équipe..."
             value="<?php echo htmlspecialchars($search); ?>"
             style="padding:8px; border-radius:5px; border:1px solid #bdc3c7; width:200px;">
      <button type="submit" class="btn">Rechercher</button>
    </div>
    <div style="margin-bottom:10px;">
      <label>
        <input type="checkbox" name="filter_unread" <?php if ($filter_unread) echo 'checked'; ?>>
        Nouveaux commentaires non lus
      </label>
    </div>
    <div style="margin-bottom:10px;">
      <label>Trier par nom :</label>
      <select name="order_by">
        <option value="ASC"  <?php if($order_by==='ASC') echo 'selected';?>>A → Z</option>
        <option value="DESC" <?php if($order_by==='DESC') echo 'selected';?>>Z → A</option>
      </select>
    </div>
    <button type="submit" class="btn">Appliquer les filtres</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Description</th>
        <th>Prix (€)</th>
        <th>Équipes</th>
        <th>État</th>
        <?php if ($is_admin || $is_manager): ?>
          <th>Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php while($p = $result->fetch_assoc()):
        // Badge état
        $stageClass = '';
        switch($p['lifecycle_stage']){
          case 'R&D':         $stageClass='state-rd';      break;
          case 'Pré-prod':    $stageClass='state-preprod'; break;
          case 'Production':  $stageClass='state-prod';    break;
        }

        // Déterminer s'il y a de nouveaux commentaires 
        // (après user_parfum_read.last_read_at)
        $has_new = false;
        if ($user_id) {
          // 1) Récupérer last_read_at dans user_parfum_read
          $stmtLR = $mysqli->prepare("
            SELECT last_read_at
            FROM user_parfum_read
            WHERE user_id=? AND parfum_id=?
          ");
          $pid = (int)$p['id'];
          $stmtLR->bind_param("ii", $user_id, $pid);
          $stmtLR->execute();
          $resLR = $stmtLR->get_result();
          $last_read_at = null;
          if($rowLR = $resLR->fetch_assoc()){
            $last_read_at = $rowLR['last_read_at']; 
          }

          // 2) Filtrer si user a coché “Nouveaux commentaires non lus” ?
          //    On affiche TOUT, mais on mettra la mention (Nouveau) si 
          //    y a >0 commentaires plus récents.
          //    Par contre, si l'utilisateur veut FILTER, on pourrait 
          //    exclure le parfum s'il n'a PAS de new comments.

          // 3) Combien de comments plus récents ?
          if ($last_read_at) {
            $stmtC = $mysqli->prepare("
              SELECT COUNT(*) AS cnt
              FROM comments
              WHERE parfum_id=?
                AND created_at > ?
            ");
            $stmtC->bind_param("is", $pid, $last_read_at);
          } else {
            // Jamais lu => tous les com. sont “nouveaux”
            $stmtC = $mysqli->prepare("
              SELECT COUNT(*) AS cnt
              FROM comments
              WHERE parfum_id=?
            ");
            $stmtC->bind_param("i", $pid);
          }
          $stmtC->execute();
          $rc = $stmtC->get_result();
          $countNew = 0;
          if($rowC=$rc->fetch_assoc()){
            $countNew = $rowC['cnt'] ?? 0;
          }
          $has_new = ($countNew>0);

          // 4) Si l’utilisateur a coché “filter_unread” 
          //    et qu’il N’Y A PAS de new => On skip 
          if($filter_unread && !$has_new){
            continue; // On ignore ce parfum, il n’a pas de new comment
          }
        }

        // Équipes multiples
        $teams_array = explode(',', $p['team']);
      ?>
      <tr>
        <td>
          <a href="parfum_detail.php?id=<?php echo $p['id']; ?>">
            <?php echo htmlspecialchars($p['name']); ?>
            <?php if($has_new): ?>
              <span style="color:red; font-weight:bold;">(Nouveau)</span>
            <?php endif; ?>
          </a>
        </td>
        <td><?php echo htmlspecialchars($p['reference']); ?></td>
        <td><?php echo htmlspecialchars($p['description']); ?></td>
        <td><?php echo htmlspecialchars($p['price']); ?></td>
        <td>
          <?php foreach($teams_array as $tm): ?>
            <button class="team-button"><?php echo htmlspecialchars($tm);?></button>
          <?php endforeach; ?>
        </td>
        <td style="text-align:center;">
          <span class="state-badge <?php echo $stageClass; ?>">
            <?php echo htmlspecialchars($p['lifecycle_stage']); ?>
          </span>
        </td>

        <?php if($is_admin||$is_manager): ?>
          <td class="actions">
            <a href="edit_parfum.php?id=<?php echo $p['id'];?>" class="btn-edit">Modifier</a>
            <a href="delete_parfum.php?id=<?php echo $p['id'];?>" class="btn-delete"
               onclick="return confirm('Supprimer ce parfum ?');">
               Supprimer
            </a>
          </td>
        <?php endif;?>
      </tr>
      <?php endwhile;?>
    </tbody>
  </table>
</div>
</body>
</html>

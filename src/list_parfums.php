<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Déterminer le rôle
$user_roles = explode(',', $_SESSION['role']); 
$is_manager = in_array('manager', $user_roles);
$is_admin = in_array('admin', $user_roles);

$team = null;
foreach ($user_roles as $r) {
  if (strpos($r, 'Equipe') !== false) {
    $team = $r;
    break;
  }
}

// On peut ajouter une recherche
$search = $_GET['search'] ?? '';
$query = "SELECT id, name, description, price, reference FROM parfums";

// Filtrer par équipe si pas admin
$conditions = [];
if (!$is_admin && $team) {
  $conditions[] = "team='$team'";
}

// Si recherche non vide, filtrer sur name, reference, team
if (!empty($search)) {
  $search_esc = $mysqli->real_escape_string($search);
  $conditions[] = "(name LIKE '%$search_esc%' OR reference LIKE '%$search_esc%' OR team LIKE '%$search_esc%')";
}

if (!empty($conditions)) {
  $query .= " WHERE " . implode(" AND ", $conditions);
}

$result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des Parfums</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Liste des Parfums</h1>

  <!-- Barre de recherche -->
  <form method="get" style="margin-bottom:20px;">
    <input type="text" name="search" placeholder="Rechercher par nom, référence, équipe..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; border-radius:5px; border:1px solid #bdc3c7; width:200px;">
    <button type="submit" class="btn">Rechercher</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Nom</th>
        <th>Référence</th>
        <th>Description</th>
        <th>Prix (€)</th>
        <?php if ($is_admin || $is_manager): ?>
          <th>Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php while ($p = $result->fetch_assoc()): ?>
      <tr>
        <td><a href="parfum_detail.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></a></td>
        <td><?php echo htmlspecialchars($p['reference']); ?></td>
        <td><?php echo htmlspecialchars($p['description']); ?></td>
        <td><?php echo htmlspecialchars($p['price']); ?></td>
        <?php if ($is_admin || $is_manager): ?>
          <td class="actions">
            <a href="edit_parfum.php?id=<?php echo $p['id']; ?>" class="btn-edit">Modifier</a>
            <a href="delete_parfum.php?id=<?php echo $p['id']; ?>" class="btn-delete" onclick="return confirm('Supprimer ce parfum ?')">Supprimer</a>
          </td>
        <?php endif; ?>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

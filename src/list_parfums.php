<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Déterminer l’équipe et si manager
$user_roles = explode(',', $_SESSION['role']); // ex: "manager,Equipe1"
$is_manager = in_array('manager', $user_roles);
$team = null;
// Trouver l'équipe dans les rôles (Equipe1 ou Equipe2)
foreach ($user_roles as $r) {
  if (strpos($r, 'Equipe') !== false) {
    $team = $r;
    break;
  }
}

// Si pas d'équipe trouvée, on peut par défaut ne rien afficher ou afficher tout si admin
$is_admin = in_array('admin', $user_roles);

$query = "SELECT id, name, description, price FROM parfums";
if (!$is_admin && $team) {
  $query .= " WHERE team='$team'";
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
  <table>
    <thead>
      <tr>
        <th>Nom</th>
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

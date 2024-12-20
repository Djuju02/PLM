<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

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

$query = "SELECT id, username, role FROM users";
if (!$is_admin && $team) {
  $query .= " WHERE role LIKE '%$team%'";
}

$result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des Utilisateurs</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <h1>Liste des Utilisateurs</h1>
  <table>
    <thead>
      <tr>
        <th>Nom d'utilisateur</th>
        <th>Rôles</th>
        <?php if ($is_admin || $is_manager): ?>
          <th>Actions</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php while($u = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td><?php echo htmlspecialchars($u['role']); ?></td>
        <?php 
        // Conditions pour actions
        // Si admin, peut modifier tout le monde sauf admin lui-même
        // Si manager, peut modifier ceux de sa team
        if ($is_admin && $u['username'] !== 'admin') {
          echo '<td class="actions">
                  <a href="edit_user.php?id='.$u['id'].'" class="btn-edit">Modifier</a>
                  <a href="delete_user.php?id='.$u['id'].'" class="btn-delete" onclick="return confirm(\'Supprimer cet utilisateur ?\')">Supprimer</a>
                </td>';
        } elseif ($is_manager && $team && strpos($u['role'], $team) !== false && $u['username'] !== 'admin') {
          echo '<td class="actions">
                  <a href="edit_user.php?id='.$u['id'].'" class="btn-edit">Modifier</a>
                  <a href="delete_user.php?id='.$u['id'].'" class="btn-delete" onclick="return confirm(\'Supprimer cet utilisateur ?\')">Supprimer</a>
                </td>';
        } elseif (($is_manager || $is_admin) && $u['username'] === 'admin') {
          // Aucun droit sur admin
          // Pas de cellule action
        } ?>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

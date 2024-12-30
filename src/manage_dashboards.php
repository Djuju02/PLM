<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

$user_roles = explode(',', $_SESSION['role'] ?? '');
if (!in_array('admin', $user_roles)) {
  header("Location: dashboard.php");
  exit();
}

$mysqli = new mysqli("db","root","root","plm");

// Création d’un dashboard
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create_dashboard'])) {
  $title = $_POST['title'] ?? '';
  $desc  = $_POST['description'] ?? '';
  // settings = '{}'
  $stmt = $mysqli->prepare("
    INSERT INTO dashboards (title, description, settings)
    VALUES (?,?, '{}')
  ");
  $stmt->bind_param("ss", $title, $desc);
  $stmt->execute();
  header("Location: manage_dashboards.php");
  exit();
}

// Suppression
if (isset($_GET['delete_id'])) {
  $delete_id = (int)$_GET['delete_id'];
  $mysqli->query("DELETE FROM dashboards WHERE id=$delete_id");
  header("Location: manage_dashboards.php");
  exit();
}

// Liste
$res_dash = $mysqli->query("
  SELECT id, title, description
  FROM dashboards
  ORDER BY id ASC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Dashboards</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .container {
      width: 90%;
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .header-actions {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
    }
    .header-actions a {
      padding: 8px 12px;
      background: #34495e;
      color: #fff;
      text-decoration: none;
      border-radius: 5px;
    }
    table {
      width:100%;
      border-collapse:collapse;
      margin-top:20px;
    }
    table th, table td {
      border:1px solid #bdc3c7;
      padding:8px;
    }
    .form-group {
      margin-bottom:15px;
    }
    .form-group label {
      display:block;
      font-weight:bold;
      margin-bottom:5px;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php">Accueil</a>
    <a href="dashboard.php">Retour Dashboard</a>
  </div>

  <h1>Gestion des Dashboards</h1>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Titre</th>
        <th>Description</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($d = $res_dash->fetch_assoc()): ?>
      <tr>
        <td><?php echo $d['id']; ?></td>
        <td><?php echo htmlspecialchars($d['title']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($d['description'])); ?></td>
        <td>
          <a href="edit_dashboard.php?id=<?php echo $d['id']; ?>">Modifier</a>
          <a href="manage_dashboards.php?delete_id=<?php echo $d['id']; ?>"
             onclick="return confirm('Supprimer ce Dashboard ?')"
             style="margin-left:10px; color:red;">Supprimer</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h2>Créer un nouveau Dashboard</h2>
  <form method="post">
    <input type="hidden" name="create_dashboard" value="1">
    <div class="form-group">
      <label>Titre</label>
      <input type="text" name="title" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"></textarea>
    </div>
    <button type="submit" class="btn">Créer</button>
  </form>
</div>
</body>
</html>

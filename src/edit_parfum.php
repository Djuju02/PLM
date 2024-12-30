<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");

// ID du parfum
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: list_parfums.php");
  exit();
}

// Sélection du parfum
$stmt = $mysqli->prepare("
  SELECT name, description, price, team, reference, lifecycle_stage
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
$stmt->bind_result($name, $description, $price, $teams_csv, $reference, $lifecycle_stage);
$stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_name        = $_POST['name']            ?? $name;
  $new_description = $_POST['description']     ?? $description;
  $new_price       = $_POST['price']           ?? $price;
  $new_reference   = $_POST['reference']       ?? $reference;
  $new_lifecycle   = $_POST['lifecycle_stage'] ?? $lifecycle_stage;

  // Multi-équipes
  // On reçoit un array de type ["Equipe1", "Equipe2", ...]
  $selected_teams = $_POST['teams'] ?? [];
  $teams_csv_new  = implode(',', $selected_teams);

  $stmt_up = $mysqli->prepare("
    UPDATE parfums
    SET name=?, description=?, price=?, team=?, reference=?, lifecycle_stage=?
    WHERE id=?
  ");
  $stmt_up->bind_param("ssdsssi",
    $new_name,
    $new_description,
    $new_price,
    $teams_csv_new,    // champ team = CSV
    $new_reference,
    $new_lifecycle,
    $id
  );
  $stmt_up->execute();

  header("Location: list_parfums.php");
  exit();
}

// Découper la liste CSV pour présélection
$current_teams = explode(',', $teams_csv); // Ex: "Equipe1,Equipe2" => ["Equipe1","Equipe2"]
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Parfum</title>
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
  .btn-home {
    background-color: #2c3e50; 
    color: #ecf0f1;
  }
  .btn-cancel {
    background-color: #7f8c8d;
    color: #fff;
  }
  .form-group {
    margin-bottom: 15px;
  }
  .form-group label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
  }
</style>
</head>
<body>
<div class="container">
  <div class="header-actions">
    <a href="home.php" class="btn btn-home">Accueil</a>
    <a href="list_parfums.php" class="btn btn-cancel">Annuler</a>
  </div>

  <h1>Modifier le Parfum : <?php echo htmlspecialchars($name); ?></h1>
  <form method="post">
    <div class="form-group">
      <label>Nom</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <div class="form-group">
      <label>Prix (en €)</label>
      <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
    </div>
    <div class="form-group">
      <label>Équipes (sélection multiple)</label>
      <select name="teams[]" multiple size="4">
        <option value="Equipe1" <?php if(in_array('Equipe1',$current_teams)) echo 'selected'; ?>>Equipe1</option>
        <option value="Equipe2" <?php if(in_array('Equipe2',$current_teams)) echo 'selected'; ?>>Equipe2</option>
        <option value="Equipe3" <?php if(in_array('Equipe3',$current_teams)) echo 'selected'; ?>>Equipe3</option>
        <option value="Equipe4" <?php if(in_array('Equipe4',$current_teams)) echo 'selected'; ?>>Equipe4</option>
      </select>
      <small>Maintenir Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs.</small>
    </div>
    <div class="form-group">
      <label>Référence</label>
      <input type="text" name="reference" value="<?php echo htmlspecialchars($reference); ?>">
    </div>
    <div class="form-group">
      <label>État (Cycle de vie)</label>
      <select name="lifecycle_stage">
        <option value="R&D"        <?php if($lifecycle_stage==='R&D') echo 'selected'; ?>>R&D</option>
        <option value="Pré-prod"   <?php if($lifecycle_stage==='Pré-prod') echo 'selected'; ?>>Pré-prod</option>
        <option value="Production" <?php if($lifecycle_stage==='Production') echo 'selected'; ?>>Production</option>
      </select>
    </div>
    <button type="submit" class="btn">Enregistrer</button>
  </form>
</div>
</body>
</html>

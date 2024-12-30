<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

// Vérification des rôles
$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin   = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

if (!$is_admin && !$is_manager) {
  header("Location: home.php");
  exit();
}

// Connexion MySQL
$mysqli = new mysqli("db","root","root","plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion : " . $mysqli->connect_error);
}

// ID Dashboard
$dashboard_id = 1;

// Charger le dashboard
$stmt = $mysqli->prepare("SELECT title, description, settings FROM dashboards WHERE id=?");
$stmt->bind_param("i", $dashboard_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  die("Dashboard id=1 introuvable.");
}
$row = $res->fetch_assoc();
$title       = $row['title']       ?? "Dashboard";
$description = $row['description'] ?? "";
$settings    = $row['settings']    ?? "{\"blocks\":[]}";

$dashboard = json_decode($settings, true);
if (!isset($dashboard['blocks'])) {
  $dashboard['blocks'] = [];
}
$blocks = &$dashboard['blocks'];

// 1) Suppression bloc
if (isset($_GET['remove_block'])) {
  $idx = (int)$_GET['remove_block'];
  if (isset($blocks[$idx])) {
    array_splice($blocks, $idx, 1);

    $new_json = json_encode($dashboard);
    $upd = $mysqli->prepare("UPDATE dashboards SET settings=? WHERE id=?");
    $upd->bind_param("si", $new_json, $dashboard_id);
    $upd->execute();
  }
  header("Location: edit_dashboard.php");
  exit();
}

// 2) Enregistrer Titre/Desc
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_dashboard'])) {
  $new_title       = $_POST['title']       ?? $title;
  $new_description = $_POST['description'] ?? $description;

  $dashboard['blocks'] = $blocks;
  $new_json = json_encode($dashboard);

  $upd = $mysqli->prepare("UPDATE dashboards SET title=?, description=?, settings=? WHERE id=?");
  $upd->bind_param("sssi", $new_title, $new_description, $new_json, $dashboard_id);
  $upd->execute();

  header("Location: home.php");
  exit();
}

// 3) Ajouter bloc
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_block'])) {
  $block_title = trim($_POST['block_title'] ?? '');
  if ($block_title==="") {
    die("<p style='color:red;'>Titre vide.</p>");
  }
  foreach($blocks as $b) {
    if (($b['title'] ?? '') === $block_title) {
      die("<p style='color:red;'>Un bloc '$block_title' existe déjà.</p>");
    }
  }

  $block_type  = $_POST['block_type']  ?? 'kpi';
  $block_size  = $_POST['block_size']  ?? 'small';
  $teamsFilter = $_POST['teamsFilter'] ?? [];
  $statusFilter= $_POST['statusFilter']?? [];
  $subFilter   = $_POST['subproduct']  ?? [];
  $chartType   = $_POST['chartType']   ?? 'pie';

  $new_block = [
    'title'        => $block_title,
    'type'         => $block_type,
    'size'         => $block_size,
    'teamsFilter'  => $teamsFilter,
    'statusFilter' => $statusFilter,
    'subFilter'    => $subFilter
  ];
  if ($block_type==='chart') {
    $new_block['chartType'] = $chartType;
  }

  $blocks[] = $new_block;
  $dashboard['blocks'] = $blocks;
  $new_json = json_encode($dashboard);

  $upd = $mysqli->prepare("UPDATE dashboards SET settings=? WHERE id=?");
  $upd->bind_param("si", $new_json, $dashboard_id);
  $upd->execute();

  header("Location: edit_dashboard.php");
  exit();
}

// 4) Édition bloc
$editIndex = null;
if (isset($_GET['edit_block'])) {
  $editIndex = (int)$_GET['edit_block'];
  if (!isset($blocks[$editIndex])) {
    $editIndex = null;
  }
}

// Mise à jour bloc
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_block'])) {
  $editIndex = (int)$_POST['block_index'];
  if (!isset($blocks[$editIndex])) {
    die("<p>Bloc inexistant.</p>");
  }

  $up_title = trim($_POST['block_title'] ?? '');
  if ($up_title==="") {
    die("<p>Titre vide.</p>");
  }
  // Unicité
  foreach($blocks as $i=>$b) {
    if ($i!==$editIndex && ($b['title']??'')===$up_title) {
      die("<p>Un bloc '$up_title' existe déjà.</p>");
    }
  }

  $up_type  = $_POST['block_type']  ?? 'kpi';
  $up_size  = $_POST['block_size']  ?? 'small';
  $up_teams = $_POST['teamsFilter'] ?? [];
  $up_stats = $_POST['statusFilter']?? [];
  $up_subs  = $_POST['subproduct']  ?? [];
  $up_chart = $_POST['chartType']   ?? 'pie';

  $blocks[$editIndex]['title']        = $up_title;
  $blocks[$editIndex]['type']         = $up_type;
  $blocks[$editIndex]['size']         = $up_size;
  $blocks[$editIndex]['teamsFilter']  = $up_teams;
  $blocks[$editIndex]['statusFilter'] = $up_stats;
  $blocks[$editIndex]['subFilter']    = $up_subs;
  if ($up_type==='chart') {
    $blocks[$editIndex]['chartType'] = $up_chart;
  } else {
    unset($blocks[$editIndex]['chartType']);
  }

  $dashboard['blocks'] = $blocks;
  $new_json = json_encode($dashboard);

  $upd = $mysqli->prepare("UPDATE dashboards SET settings=? WHERE id=?");
  $upd->bind_param("si", $new_json, $dashboard_id);
  $upd->execute();

  header("Location: edit_dashboard.php");
  exit();
}

// Charger listes (équipes, statuts, sous-produits)
$allTeams=[];
$qteams=$mysqli->query("SELECT team FROM parfums");
while($tr=$qteams->fetch_assoc()) {
  $split=explode(',',$tr['team']);
  foreach($split as $tm){
    $tm=trim($tm);
    if($tm && !in_array($tm,$allTeams)){
      $allTeams[]=$tm;
    }
  }
}
sort($allTeams);

$allStatus=[];
$qstat=$mysqli->query("SELECT DISTINCT lifecycle_stage FROM parfums");
while($sr=$qstat->fetch_assoc()){
  $ls=$sr['lifecycle_stage'];
  if($ls && !in_array($ls,$allStatus)){
    $allStatus[]=$ls;
  }
}
sort($allStatus);

$allSubs=[];
$qsubs=$mysqli->query("SELECT reference, name FROM ingredients_global ORDER BY reference");
while($rowS=$qsubs->fetch_assoc()){
  $allSubs[]=[
    'reference'=>$rowS['reference'],
    'name'=>$rowS['name']
  ];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Configuration du Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <script>
  // Script pour cocher/décocher toutes les checkboxes d’un container
  function toggleAll(containerId, btn) {
    const container = document.getElementById(containerId);
    if (!container || !btn) return;
    const checks = container.querySelectorAll('input[type="checkbox"]');
    let someUnchecked = false;
    checks.forEach(ch => { if(!ch.checked) someUnchecked = true; });
    if(someUnchecked) {
      checks.forEach(ch => ch.checked = true);
      btn.textContent = "Tout décocher";
    } else {
      checks.forEach(ch => ch.checked = false);
      btn.textContent = "Tout cocher";
    }
  }
  </script>
</head>
<body>

<!-- Container principal pour tout le contenu -->
<div class="container" style="margin-top:40px;">
  <!-- Bouton retour accueil en haut -->
  <div class="header-actions" style="justify-content:flex-end;">
    <a href="home.php" class="btn">← Retour Accueil</a>
  </div>

  <h1 style="margin-top:0;">Configuration du Dashboard</h1>

  <!-- Form : Titre & description -->
  <form method="post" style="margin-bottom:20px;">
    <input type="hidden" name="save_dashboard" value="1">

    <div class="form-group">
      <label>Titre du Dashboard</label>
      <input type="text" name="title"
             value="<?php echo htmlspecialchars($title); ?>"
             required>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="3"
                style="width:100%;"><?php echo htmlspecialchars($description); ?></textarea>
    </div>

    <button type="submit" class="btn">Enregistrer Titre & Description</button>
  </form>

  <hr>

  <h2>Liste des Blocs</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Titre</th>
        <th>Type</th>
        <th>Taille</th>
        <th>Équipes</th>
        <th>Statuts</th>
        <th>Sous-Produits</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($blocks as $idx=>$b):
      $bTitle = $b['title'] ?? '';
      $bType  = $b['type']  ?? 'kpi';
      $bSize  = $b['size']  ?? 'small';
      $bTeams = $b['teamsFilter']  ?? [];
      $bStats = $b['statusFilter'] ?? [];
      $bSubs  = $b['subFilter']    ?? [];

      $teamsStr = implode(', ', $bTeams);
      $statsStr = implode(', ', $bStats);
      $subsStr  = implode(', ', $bSubs);
    ?>
      <tr>
        <td><?php echo $idx; ?></td>
        <td><?php echo htmlspecialchars($bTitle); ?></td>
        <td><?php echo htmlspecialchars($bType); ?></td>
        <td><?php echo htmlspecialchars($bSize); ?></td>
        <td><?php echo htmlspecialchars($teamsStr); ?></td>
        <td><?php echo htmlspecialchars($statsStr); ?></td>
        <td><?php echo htmlspecialchars($subsStr); ?></td>
        <td>
          <a href="edit_dashboard.php?edit_block=<?php echo $idx; ?>" class="btn-edit">Modifier</a>
          <a href="edit_dashboard.php?remove_block=<?php echo $idx; ?>" 
             class="btn-delete"
             onclick="return confirm('Supprimer ce bloc ?');">
            Supprimer
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <hr>

  <?php if ($editIndex===null): ?>
    <!-- Formulaire d’AJOUT -->
    <h3>Ajouter un Nouveau Bloc</h3>
    <form method="post" style="margin-top:10px;">
      <input type="hidden" name="add_block" value="1">

      <div class="form-group">
        <label>Titre (unique)</label>
        <input type="text" name="block_title" required>
      </div>

      <div class="form-group">
        <label>Type</label>
        <select name="block_type">
          <option value="kpi">KPI (chiffre)</option>
          <option value="chart">Graphique (Chart.js)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Taille</label>
        <select name="block_size">
          <option value="small">Petit (1x1)</option>
          <option value="medium">Moyen (2x2)</option>
          <option value="large">Grand (3x3)</option>
        </select>
      </div>

      <!-- BOUTON TOUT COCHER en haut, puis les checkboxes en dessous -->
      <div class="form-group">
        <label>Équipes</label>
        <button type="button" class="btn-warning" 
                onclick="toggleAll('teamsAdd', this)">
          Tout cocher
        </button>
        <div id="teamsAdd" style="margin:5px 0;">
          <?php foreach($allTeams as $tm): ?>
            <label style="margin-right:10px;">
              <input type="checkbox" name="teamsFilter[]" value="<?php echo htmlspecialchars($tm); ?>">
              <?php echo htmlspecialchars($tm); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Statuts</label>
        <button type="button" class="btn-warning" 
                onclick="toggleAll('statusAdd', this)">
          Tout cocher
        </button>
        <div id="statusAdd" style="margin:5px 0;">
          <?php foreach($allStatus as $st): ?>
            <label style="margin-right:10px;">
              <input type="checkbox" name="statusFilter[]" value="<?php echo htmlspecialchars($st); ?>">
              <?php echo htmlspecialchars($st); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Sous-Produits</label>
        <button type="button" class="btn-warning"
                onclick="toggleAll('subsAdd', this)">
          Tout cocher
        </button>
        <div id="subsAdd" style="margin:5px 0;">
          <?php foreach($allSubs as $sb):
            $ref = $sb['reference'];
            $nm  = $sb['name'];
          ?>
            <label style="display:block;">
              <input type="checkbox" name="subproduct[]" value="<?php echo htmlspecialchars($ref); ?>">
              <?php echo htmlspecialchars($ref.' - '.$nm); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Type de Graphique (si type=chart)</label>
        <select name="chartType">
          <option value="pie">Camembert (pie)</option>
          <option value="bar">Barres (bar)</option>
          <option value="doughnut">Donut (doughnut)</option>
        </select>
      </div>

      <button type="submit" class="btn btn-success">
        Ajouter ce bloc
      </button>
    </form>

  <?php else:
    // Formulaire EDIT
    $currentBlock = $blocks[$editIndex];
    $curTitle = $currentBlock['title'] ?? '';
    $curType  = $currentBlock['type']  ?? 'kpi';
    $curSize  = $currentBlock['size']  ?? 'small';
    $curTeams = $currentBlock['teamsFilter']  ?? [];
    $curStats = $currentBlock['statusFilter'] ?? [];
    $curSubs  = $currentBlock['subFilter']    ?? [];
    $curChart = $currentBlock['chartType']    ?? 'pie';
  ?>
    <h3>Modifier le Bloc #<?php echo $editIndex; ?></h3>
    <form method="post" style="margin-top:10px;">
      <input type="hidden" name="update_block" value="1">
      <input type="hidden" name="block_index" value="<?php echo $editIndex; ?>">

      <div class="form-group">
        <label>Titre (unique)</label>
        <input type="text" name="block_title"
               value="<?php echo htmlspecialchars($curTitle); ?>" required>
      </div>

      <div class="form-group">
        <label>Type</label>
        <select name="block_type">
          <option value="kpi"   <?php if($curType==='kpi')   echo 'selected'; ?>>KPI (chiffre)</option>
          <option value="chart" <?php if($curType==='chart') echo 'selected'; ?>>Graphique (Chart.js)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Taille</label>
        <select name="block_size">
          <option value="small"  <?php if($curSize==='small')  echo 'selected'; ?>>Petit (1x1)</option>
          <option value="medium" <?php if($curSize==='medium') echo 'selected'; ?>>Moyen (2x2)</option>
          <option value="large"  <?php if($curSize==='large')  echo 'selected'; ?>>Grand (3x3)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Équipes</label>
        <button type="button" class="btn-warning"
                onclick="toggleAll('teamsEdit', this)">
          Tout cocher
        </button>
        <div id="teamsEdit" style="margin:5px 0;">
          <?php foreach($allTeams as $tm): ?>
            <label style="margin-right:10px;">
              <input type="checkbox" name="teamsFilter[]"
                     value="<?php echo htmlspecialchars($tm); ?>"
                     <?php if(in_array($tm, $curTeams)) echo 'checked'; ?>>
              <?php echo htmlspecialchars($tm); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Statuts</label>
        <button type="button" class="btn-warning"
                onclick="toggleAll('statusEdit', this)">
          Tout cocher
        </button>
        <div id="statusEdit" style="margin:5px 0;">
          <?php foreach($allStatus as $st): ?>
            <label style="margin-right:10px;">
              <input type="checkbox" name="statusFilter[]"
                     value="<?php echo htmlspecialchars($st); ?>"
                     <?php if(in_array($st, $curStats)) echo 'checked'; ?>>
              <?php echo htmlspecialchars($st); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Sous-Produits</label>
        <button type="button" class="btn-warning"
                onclick="toggleAll('subsEdit', this)">
          Tout cocher
        </button>
        <div id="subsEdit" style="margin:5px 0;">
          <?php foreach($allSubs as $sb):
            $ref = $sb['reference'];
            $nm  = $sb['name'];
          ?>
            <label style="display:block;">
              <input type="checkbox" name="subproduct[]"
                     value="<?php echo htmlspecialchars($ref); ?>"
                     <?php if(in_array($ref, $curSubs)) echo 'checked'; ?>>
              <?php echo htmlspecialchars($ref.' - '.$nm); ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Type de Graphique (si type=chart)</label>
        <select name="chartType">
          <option value="pie"      <?php if($curChart==='pie')      echo 'selected'; ?>>Camembert (pie)</option>
          <option value="bar"      <?php if($curChart==='bar')      echo 'selected'; ?>>Barres (bar)</option>
          <option value="doughnut" <?php if($curChart==='doughnut') echo 'selected'; ?>>Donut (doughnut)</option>
        </select>
      </div>

      <button type="submit" class="btn btn-warning">
        Mettre à jour
      </button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: index.php?error=notlogged");
  exit();
}

// Rôles
$username   = htmlspecialchars($_SESSION['username']);
$user_roles = explode(',', $_SESSION['role'] ?? '');
$is_admin   = in_array('admin', $user_roles);
$is_manager = in_array('manager', $user_roles);

$mysqli = new mysqli("db", "root", "root", "plm");
if ($mysqli->connect_error) {
  die("Erreur de connexion MySQL : " . $mysqli->connect_error);
}

// Vérifier le dashboard #1, le créer si absent
$check = $mysqli->query("SELECT id FROM dashboards WHERE id=1");
if (!$check) {
  die("Erreur query : " . $mysqli->error);
}
if ($check->num_rows === 0) {
  // On insère un dashboard “par défaut”
  $mysqli->query("
    INSERT INTO dashboards (id, title, description, settings)
    VALUES (1, 'Dashboard Principal', 'Bienvenue sur le Dashboard principal.', '{\"blocks\":[]}')
  ");
}

// Charger le dashboard #1
$stmt = $mysqli->prepare("SELECT title, description, settings FROM dashboards WHERE id=1");
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  die("Dashboard id=1 introuvable.");
}
$row = $res->fetch_assoc();
$title       = $row['title']       ?? "Dashboard";
$description = $row['description'] ?? "";
$settings    = $row['settings']    ?? "{\"blocks\":[]}";
$dashboard   = json_decode($settings, true);
$blocks      = $dashboard['blocks'] ?? [];

/**
 * Fonctions utilitaires
 * ---------------------
 * - getKpiValue()   : Calcule un nombre (KPI) selon les filtres
 * - getChartData()  : Prépare les données pour un chart (par exemple par statut de parfum)
 */

// Calcule un KPI (simple count) en fonction des filtres
function getKpiValue($mysqli, $block, $globalFilters) {
  // On récupère les filtres propres au bloc
  $teamFilter   = $block['teamFilter']   ?? '';
  $statusFilter = $block['statusFilter'] ?? '';
  $subFilter    = $block['subFilter']    ?? '';

  // Priorité aux filtres globaux
  if (!empty($globalFilters['team'])) {
    $teamFilter = $globalFilters['team'];
  }
  if (!empty($globalFilters['status'])) {
    $statusFilter = $globalFilters['status'];
  }

  // EX : Requête basique sur la table parfums
  $sql = "SELECT COUNT(*) as c FROM parfums WHERE 1";
  if ($teamFilter) {
    $sql .= " AND FIND_IN_SET('$teamFilter', team) > 0";
  }
  if ($statusFilter) {
    $sql .= " AND lifecycle_stage='$statusFilter'";
  }

  // Pour filtrer par sous-produit (subFilter), 
  // il faudrait faire un JOIN sur ingredients si vous voulez "les parfums qui contiennent le sous-produit X".
  // Ex. :
  // if ($subFilter) {
  //   $sql = "SELECT COUNT(DISTINCT p.id) as c
  //           FROM parfums p
  //           JOIN ingredients i ON i.parfum_id = p.id
  //           WHERE i.reference='$subFilter'";
  //   // plus conditions teamFilter / statusFilter...
  // }

  $r = $mysqli->query($sql);
  $d = $r->fetch_assoc();
  return (int)$d['c'];
}

// Prépare les données (labels, values) pour un chart (ex: group by lifecycle_stage)
function getChartData($mysqli, $block, $globalFilters) {
  $teamFilter   = $block['teamFilter']   ?? '';
  $statusFilter = $block['statusFilter'] ?? '';
  $subFilter    = $block['subFilter']    ?? '';

  // Merge global
  if (!empty($globalFilters['team'])) {
    $teamFilter = $globalFilters['team'];
  }
  if (!empty($globalFilters['status'])) {
    $statusFilter = $globalFilters['status'];
  }

  // Par ex, on groupe par lifecycle_stage
  $sql = "SELECT lifecycle_stage, COUNT(*) as c FROM parfums WHERE 1";
  if ($teamFilter) {
    $sql .= " AND FIND_IN_SET('$teamFilter', team) > 0";
  }
  if ($statusFilter) {
    $sql .= " AND lifecycle_stage='$statusFilter'";
  }
  // if($subFilter) => JOIN ingredients ?

  $sql .= " GROUP BY lifecycle_stage";

  $r = $mysqli->query($sql);
  $labels = [];
  $values = [];
  while($row = $r->fetch_assoc()) {
    $labels[] = $row['lifecycle_stage'];
    $values[] = (int)$row['c'];
  }
  return [
    'labels' => $labels,
    'values' => $values
  ];
}

// Filtres globaux (ex: ?filter_team=Equipe2&filter_status=Production)
$globalFilters = [
  'team'   => $_GET['filter_team']   ?? '',
  'status' => $_GET['filter_status'] ?? ''
  // subproduct global => non-implémenté ici, si besoin on ajoute un <select> etc.
];

// Récupérer la liste des teams distinctes
$allTeams = [];
$qteams = $mysqli->query("SELECT team FROM parfums");
while($tr=$qteams->fetch_assoc()){
  $split=explode(',',$tr['team']);
  foreach($split as $t){
    $t=trim($t);
    if($t && !in_array($t,$allTeams)) {
      $allTeams[]=$t;
    }
  }
}
sort($allTeams);

// Liste des statuts
$allStatus=[];
$qstat = $mysqli->query("SELECT DISTINCT lifecycle_stage FROM parfums");
while($sr=$qstat->fetch_assoc()){
  $st=$sr['lifecycle_stage'];
  if($st && !in_array($st,$allStatus)){
    $allStatus[]=$st;
  }
}
sort($allStatus);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($title); ?> - Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <!-- Chart.js (pour les charts) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      display: flex; margin:0; background:#ecf0f1; font-family:Arial,sans-serif; color:#2c3e50;
    }
    .sidebar {
      width:250px; background:#2c3e50; color:#ecf0f1; padding:20px;
    }
    .main-content {
      flex:1; padding:20px; background:#ecf0f1;
    }
    .dashboard-header { margin-bottom:30px; }
    .blocks-container {
      display:grid; grid-template-columns: repeat(4,1fr); grid-gap:20px;
    }
    .block-small {
      grid-column: span 1; grid-row: span 1;
    }
    .block-medium {
      grid-column: span 2; grid-row: span 2;
    }
    .block-large {
      grid-column: span 4; grid-row: span 3;
    }
    .block-container {
      background:#fff; border-radius:8px; padding:20px;
      box-shadow:0 0 5px rgba(0,0,0,0.1);
      display:flex; flex-direction:column; align-items:center; justify-content:center;
    }
    .block-container h3 {
      margin:0; margin-bottom:10px; font-size:18px; text-align:center;
    }
    .kpi-value {
      font-size:32px; font-weight:bold; color:#e67e22;
    }
    canvas {
      max-width:100%; max-height:400px;
    }
    .filters-form {
      margin-bottom:20px; display:flex; gap:10px; align-items:center;
    }
    .filters-form select {
      padding:5px;
    }
    .filters-form button {
      padding:8px 15px; background:#3498db; color:#fff; border:none; border-radius:5px; cursor:pointer;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <h2>Bonjour <?php echo $username; ?></h2>

    <h3>Gestion des Ressources</h3>
    <nav>
      <a href="home.php" style="background:#34495e;">Accueil (Dashboard)</a>
      <a href="list_parfums.php">Liste des Parfums</a>
      <a href="list_users.php">Liste des Utilisateurs</a>
    </nav>

    <?php if ($is_admin): ?>
      <h3>Gestion Admin</h3>
      <nav>
        <a href="manage_users.php">Utilisateurs</a>
        <a href="manage_parfums.php">Parfums</a>
        <a href="manage_roles.php">Rôles</a>
        <a href="manage_ingredients_global.php">Sous-Produits (BOM)</a>
      </nav>
    <?php endif; ?>

    <nav>
      <a href="logout.php">Se déconnecter</a>
    </nav>
  </div>

  <!-- Main Content => Dashboard -->
  <div class="main-content">
    <div class="dashboard-header">
      <h1><?php echo htmlspecialchars($title); ?></h1>
      <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
    </div>

    <!-- Filtres globaux (optionnels) -->
    <form method="get" class="filters-form">
      <label>Filtrer Équipe :</label>
      <select name="filter_team">
        <option value="">(Toutes)</option>
        <?php foreach($allTeams as $t): ?>
          <option value="<?php echo htmlspecialchars($t); ?>"
            <?php if($globalFilters['team']===$t) echo 'selected'; ?>>
            <?php echo htmlspecialchars($t); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Filtrer Statut :</label>
      <select name="filter_status">
        <option value="">(Tous)</option>
        <?php foreach($allStatus as $st): ?>
          <option value="<?php echo htmlspecialchars($st); ?>"
            <?php if($globalFilters['status']===$st) echo 'selected'; ?>>
            <?php echo htmlspecialchars($st); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button type="submit">Appliquer</button>
    </form>

    <!-- Affichage des blocs configurés -->
    <div class="blocks-container">
      <?php foreach($blocks as $idx => $block):
        $bTitle = $block['title'] ?? 'Bloc';
        $bType  = $block['type']  ?? 'kpi';
        $bSize  = $block['size']  ?? 'small';
      ?>
      <div class="block-container <?php echo 'block-'.$bSize; ?>">
        <h3><?php echo htmlspecialchars($bTitle); ?></h3>

        <?php if ($bType==='kpi'): ?>
          <?php $val = getKpiValue($mysqli, $block, $globalFilters); ?>
          <div class="kpi-value"><?php echo $val; ?></div>

        <?php elseif($bType==='chart'): ?>
          <?php
            $canvasId  = "chart_" . $idx;
            $chartType = $block['chartType'] ?? 'pie';  // pie, bar, doughnut...
            $chartData = getChartData($mysqli, $block, $globalFilters);
            $labJson   = json_encode($chartData['labels']);
            $valJson   = json_encode($chartData['values']);
          ?>
          <canvas id="<?php echo $canvasId; ?>"
                  data-type="<?php echo $chartType; ?>"
                  data-labels="<?php echo htmlentities($labJson); ?>"
                  data-values="<?php echo htmlentities($valJson); ?>">
          </canvas>
        <?php else: ?>
          <p>Type inconnu : <?php echo htmlspecialchars($bType); ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($is_admin || $is_manager): ?>
      <div style="margin-top:20px;">
        <a href="edit_dashboard.php" 
           style="background:#3498db; color:#fff; padding:10px 15px; border-radius:5px; text-decoration:none;">
          Éditer le Dashboard
        </a>
      </div>
    <?php endif; ?>
  </div>

  <script>
  // Initialiser Chart.js pour chaque canvas "chart_..."
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('canvas[id^="chart_"]').forEach((can)=>{
      const ctx    = can.getContext('2d');
      const ctype  = can.dataset.type || 'pie';
      const labs   = JSON.parse(can.dataset.labels || '[]');
      const vals   = JSON.parse(can.dataset.values || '[]');

      new Chart(ctx, {
        type: ctype,
        data: {
          labels: labs,
          datasets: [{
            data: vals,
            backgroundColor:[
              '#3498db','#e67e22','#2ecc71','#9b59b6',
              '#f1c40f','#1abc9c','#e74c3c','#2c3e50'
            ]
          }]
        },
        options:{
          responsive:true,
          maintainAspectRatio:true,
          scales:{
            y:{ beginAtZero:true }
          }
        }
      });
    });
  });
  </script>
</body>
</html>

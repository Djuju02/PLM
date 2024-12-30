<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: parfum_detail.php");
  exit();
}

// Vérifier le parfum
$stmt = $mysqli->prepare("SELECT name FROM parfums WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: parfum_detail.php");
  exit();
}
$stmt->fetch();

// Supprimer
$stmt_del = $mysqli->prepare("DELETE FROM parfums WHERE id = ?");
$stmt_del->bind_param("i", $id);
$stmt_del->execute();

header("Location: parfum_detail.php");
exit();

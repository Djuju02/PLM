<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';

if (empty($id)) {
  header("Location: manage_roles.php");
  exit();
}

$stmt = $mysqli->prepare("SELECT role_name FROM roles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: manage_roles.php");
  exit();
}
$stmt->bind_result($role_name);
$stmt->fetch();

if ($role_name === 'admin') {
  // Ne pas supprimer admin
  header("Location: manage_roles.php");
  exit();
}

$stmt_del = $mysqli->prepare("DELETE FROM roles WHERE id = ?");
$stmt_del->bind_param("i", $id);
$stmt_del->execute();

header("Location: manage_roles.php");
exit();

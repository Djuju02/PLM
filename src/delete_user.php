<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
  header("Location: index.php?error=notlogged");
  exit();
}

$mysqli = new mysqli("db", "root", "root", "plm");
$id = $_GET['id'] ?? '';

if (empty($id)) {
  header("Location: list_users.php");
  exit();
}

// Vérifier l'utilisateur
$stmt = $mysqli->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
  header("Location: list_users.php");
  exit();
}
$stmt->bind_result($username);
$stmt->fetch();

if ($username === 'admin') {
  // Ne pas supprimer l'admin
  header("Location: list_users.php");
  exit();
}

// Supprimer l'utilisateur
$stmt_del = $mysqli->prepare("DELETE FROM users WHERE id = ?");
$stmt_del->bind_param("i", $id);
$stmt_del->execute();

header("Location: list_users.php");
exit();

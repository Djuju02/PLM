<?php
session_start();

// Connexion à la BD
$mysqli = new mysqli("db", "root", "root", "plm");

$username = $_POST['username'];
$password = $_POST['password'];

// Vérifier l'utilisateur dans la BD avec l'ID
$stmt = $mysqli->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  $stmt->bind_result($user_id, $password_hash, $role);
  $stmt->fetch();
  
  if (hash("sha256", $password) === $password_hash) {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['user_id'] = $user_id; // On stocke l'ID de l'utilisateur
    
    header("Location: home.php");
    exit();
  } else {
    header("Location: index.php?error=invalid");
    exit();
  }
} else {
  header("Location: index.php?error=invalid");
  exit();
}

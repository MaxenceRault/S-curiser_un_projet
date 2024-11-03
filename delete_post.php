<?php
session_start();
require_once('bdd.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Post invalide.");
}

$post_id = intval($_GET['id']);
$stmt = $connexion->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $post_id]);
$post = $stmt->fetch();

if (!$post) {
    die("Post introuvable.");
}

if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== (int)$post['user_id']) {
    die("Vous n'avez pas la permission de supprimer ce post.");
}

$stmt = $connexion->prepare('DELETE FROM posts WHERE id = :id');
$stmt->execute(['id' => $post_id]);

header('Location: posts.php');
exit();
?>

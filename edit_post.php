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
    die("Vous n'avez pas la permission d'éditer ce post.");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le Post</title>
    <link rel="stylesheet" href="post.css">
</head>
<body>
    <div class="container">
        <h1>Modifier le Post</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post" action="traitement.php">
            <textarea name="content" rows="4" cols="50"><?= htmlspecialchars($post['content']); ?></textarea><br>
            <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="post_id" value="<?= $post_id; ?>">
            <input type="submit" value="Mettre à jour">
        </form>
        <p><a href="posts.php">Retour</a></p>
    </div>
</body>
</html>

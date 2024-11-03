<?php
session_start();
require_once('bdd.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// CSRF token generation
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch posts
$stmt = $connexion->prepare('SELECT posts.*, user.name FROM posts JOIN user ON posts.user_id = user.id ORDER BY created_at DESC');
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Twitter Like</title>
    <link rel="stylesheet" href="post.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenue, <?= htmlspecialchars($_SESSION['name'] ?? ''); ?></h1>
        <nav>
            <a href="profil.php">Mon Profil</a>
            <a href="logout.php">Se déconnecter</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="admin_users.php">Gestion des utilisateurs</a>
            <?php endif; ?>
        </nav>

        <h2>Nouveau Post</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post" action="traitement.php">
            <textarea name="content" rows="4" cols="50" placeholder="Votre message"></textarea><br>
            <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="create">
            <input type="submit" value="Poster">
        </form>

        <h2>Posts Récents</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <strong><?= htmlspecialchars($post['name']); ?></strong>
                    <span class="post-date"><?= $post['created_at']; ?></span>
                </div>
                <div class="post-content">
                    <p><?= htmlspecialchars($post['content']); ?></p>
                </div>
                <div class="post-actions">
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $post['user_id']): ?>
                        <a href="edit_post.php?id=<?= $post['id']; ?>">Modifier</a>
                        <a href="delete_post.php?id=<?= $post['id']; ?>">Supprimer</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (isset($_SESSION['post_success'])): ?>
        <script>
            console.log("Le post est un succès");
        </script>
        <?php unset($_SESSION['post_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['post_success2'])): ?>
        <script>
            console.log("Le post est bien modifié");
        </script>
        <?php unset($_SESSION['post_success2']); ?>
    <?php endif; ?>
</body>
</html>
<?php
session_start();
require_once('bdd.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user details
$stmt = $connexion->prepare('SELECT * FROM user WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur introuvable.");
}

// Fetch user posts
$stmt = $connexion->prepare('SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$posts = $stmt->fetchAll();

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['token'])) {
        die("Invalid CSRF token");
    }

    // Delete user posts
    $stmt = $connexion->prepare('DELETE FROM posts WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $_SESSION['user_id']]);

    // Delete user account
    $stmt = $connexion->prepare('DELETE FROM user WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);

    // Log out user
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil</title>
    <link rel="stylesheet" href="profil.css">
    <link rel="stylesheet" href="posts.css">
</head>
<body>
    <div class="container">
        <h1>Profil de <?= htmlspecialchars($user['name']); ?></h1>
        <p>Email: <?= htmlspecialchars($user['email']); ?></p>
        <nav>
            <a href="posts.php">Retour aux posts</a>
            <a href="logout.php">Se déconnecter</a>
        </nav>
        <h2>Vos Posts</h2>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <div class="post-header">
                    <strong><?= htmlspecialchars($user['name']); ?></strong>
                    <span class="post-date"><?= $post['created_at']; ?></span>
                </div>
                <div class="post-content">
                    <p><?= htmlspecialchars($post['content']); ?></p>
                </div>
                <div class="post-actions">
                    <a href="edit_post.php?id=<?= $post['id']; ?>">Modifier</a>
                    <a href="delete_post.php?id=<?= $post['id']; ?>">Supprimer</a>
                </div>
            </div>
        <?php endforeach; ?>
        <h2>Supprimer le compte</h2>
        <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre compte et tous vos posts ?');">
            <input type="hidden" name="delete_account" value="1">
            <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="submit" value="Supprimer mon compte">
        </form>
    </div>
</body>

</html>
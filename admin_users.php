<?php
session_start();
require_once('bdd.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// CSRF token generation
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Handle role update and user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['token'])) {
        die("Invalid CSRF token");
    }

    if (isset($_POST['user_id']) && isset($_POST['role'])) {
        $user_id = intval($_POST['user_id']);
        $role = $_POST['role'];

        // Update user role
        $stmt = $connexion->prepare('UPDATE user SET role = :role WHERE id = :id');
        $stmt->execute([
            'role' => $role,
            'id' => $user_id
        ]);

        header('Location: admin_users.php');
        exit();
    }

    if (isset($_POST['delete_user_id'])) {
        $delete_user_id = intval($_POST['delete_user_id']);

        // Delete user posts
        $stmt = $connexion->prepare('DELETE FROM posts WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $delete_user_id]);

        // Delete user
        $stmt = $connexion->prepare('DELETE FROM user WHERE id = :id');
        $stmt->execute(['id' => $delete_user_id]);

        header('Location: admin_users.php');
        exit();
    }
}

// Fetch all users
$stmt = $connexion->prepare('SELECT * FROM user ORDER BY id ASC');
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Gestion des utilisateurs</h1>
        <nav>
            <a href="posts.php">Retour aux posts</a>
            <a href="logout.php">Se déconnecter</a>
        </nav>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']); ?></td>
                        <td><?= htmlspecialchars($user['name']); ?></td>
                        <td><?= htmlspecialchars($user['email']); ?></td>
                        <td><?= htmlspecialchars($user['role']); ?></td>
                        <td class="admin-actions">
                            <form method="post" action="">
                                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                <select name="role">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
                                <input type="submit" value="Mettre à jour">
                            </form>
                            <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur et tous ses posts ?');">
                                <input type="hidden" name="delete_user_id" value="<?= $user['id']; ?>">
                                <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
                                <input type="submit" class="delete" value="Supprimer">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php
session_start();
require_once('bdd.php');

// Generate CSRF token
if (!isset($_SESSION["csrf_token"]) || empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['token'])) {
        die("Invalid CSRF token");
    }

    // Determine action (register or login)
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        // Registration
        $name = htmlspecialchars(trim($_POST['name']));
        $email = htmlspecialchars(trim($_POST['email']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Tous les champs sont obligatoires pour l'inscription.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            // Check if email exists
            $stmt = $connexion->prepare('SELECT id FROM user WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $error = "Un compte avec cet email existe déjà.";
            } else {
                // Check if this is the first user
                $stmt = $connexion->prepare('SELECT COUNT(*) FROM user');
                $stmt->execute();
                $user_count = $stmt->fetchColumn();

                // Assign role
                $role = $user_count == 0 ? 'admin' : 'user';

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));

                // Insert user into database
                $stmt = $connexion->prepare('INSERT INTO user (name, email, password, role, slug) VALUES (:name, :email, :password, :role, :slug)');
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'role' => $role,
                    'slug' => $slug
                ]);

                // Log in user
                $_SESSION['user_id'] = $connexion->lastInsertId();
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;

                // Generate new CSRF token
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                // Redirect to posts page
                header('Location: posts.php');
                exit();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
        // Login
        $email = htmlspecialchars(trim($_POST['email']));
        $password = $_POST['password'];

        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = "Tous les champs sont obligatoires pour la connexion.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } else {
            // Fetch user
            $stmt = $connexion->prepare('SELECT * FROM user WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Authentication successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Generate new CSRF token
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                // Redirect to posts page
                header('Location: posts.php');
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }
    }
}

// Display errors from session
if (isset($_SESSION['errors'])) {
    $error = implode('<br>', $_SESSION['errors']);
    unset($_SESSION['errors']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion / Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (!empty($error)) { echo '<p class="error">'.$error.'</p>'; } ?>

        <h2>Se connecter</h2>
        <form action="" method="post">
            <input type="hidden" name="action" value="login">
            <label for="login_email">Email :</label>
            <input type="email" name="email" id="login_email" required><br>
            <label for="login_password">Mot de passe :</label>
            <input type="password" name="password" id="login_password" required><br>
            <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="submit" value="Se connecter">
        </form>

        <h2>S'inscrire</h2>
        <form action="" method="post">
            <input type="hidden" name="action" value="register">
            <label for="register_name">Nom :</label>
            <input type="text" name="name" id="register_name" required><br>
            <label for="register_email">Email :</label>
            <input type="email" name="email" id="register_email" required><br>
            <label for="register_password">Mot de passe :</label>
            <input type="password" name="password" id="register_password" required><br>
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" required><br>
            <input type="hidden" name="token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="submit" value="S'inscrire">
        </form>
    </div>
</body>
</html>
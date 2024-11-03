<?php
session_start();
require_once('bdd.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];

// Validation du jeton CSRF
if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['csrf_token']) {
    die("Jeton CSRF invalide");
}

// Supprimer le jeton CSRF pour qu'il soit régénéré
unset($_SESSION['csrf_token']);

// Validation du contenu
if (isset($_POST['content']) && !empty($_POST['content'])) {
    $content = htmlspecialchars($_POST['content']);
} else {
    $errors[] = "Le contenu est obligatoire";
}

// Déterminer l'action (create ou edit)
$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    // S'il n'y a pas d'erreurs de validation, insérer le post dans la base de données
    if (empty($errors)) {
        try {
            $stmt = $connexion->prepare('INSERT INTO posts (user_id, content, created_at) VALUES (:user_id, :content, NOW())');
            $stmt->execute([
                'user_id' => (int)$_SESSION['user_id'],
                'content' => $content
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['post_success'] = true;
                header('Location: posts.php');
                exit();
            } else {
                $errors[] = "Une erreur est survenue lors de la sauvegarde.";
            }
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
} elseif ($action === 'edit') {
    // Validation de post_id
    if (!isset($_POST['post_id']) || empty($_POST['post_id'])) {
        $errors[] = "Post ID manquant.";
    } else {
        $post_id = intval($_POST['post_id']);
    }

    // S'il n'y a pas d'erreurs de validation, continuer
    if (empty($errors)) {
        // Récupérer le post pour vérifier les permissions
        $stmt = $connexion->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $post_id]);
        $post = $stmt->fetch();

        if (!$post) {
            $errors[] = "Le post n'existe pas.";
        } else {
            // Vérifier les permissions
            if ($_SESSION['role'] !== 'admin' && (int)$_SESSION['user_id'] !== (int)$post['user_id']) {
                $errors[] = "Vous n'avez pas la permission de modifier ce post.";
            } else {
                // S'il n'y a pas d'erreurs, mettre à jour le post
                try {
                    $stmt = $connexion->prepare('UPDATE posts SET content = :content WHERE id = :id');
                    $stmt->execute([
                        'content' => $content,
                        'id' => $post_id
                    ]);

                    if ($stmt->rowCount() > 0) {
                        $_SESSION['post_success2'] = true;
                        header('Location: posts.php');
                        exit();
                    } else {
                        $errors[] = "Une erreur est survenue lors de la mise à jour.";
                    }
                } catch (Exception $e) {
                    die($e->getMessage());
                }
            }
        }
    }
}

// S'il y a des erreurs, les stocker dans la session et rediriger vers le formulaire
$_SESSION['errors'] = $errors;
header('Location: posts.php');
exit();
?>

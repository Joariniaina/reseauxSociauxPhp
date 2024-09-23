<?php
require 'db.php';
session_start();

// Redirection vers la page de connexion si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération de tous les posts et leurs commentaires
$sql = "
    SELECT posts.id AS post_id, posts.content AS post_content, posts.created_at AS post_date, 
           users.username AS post_author 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC";
$posts_result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réseau Social</title>
    <style>
        .post, .comment {
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h2>Bienvenue, <?php echo $_SESSION['username']; ?> !</h2>
    <a href="logout.php">Déconnexion</a>

    <!-- Formulaire de publication d'un post -->
    <h3>Créer un nouveau post</h3>
    <form action="create_post.php" method="POST">
        <textarea name="content" placeholder="Que voulez-vous partager ?" required></textarea><br>
        <button type="submit">Publier</button>
    </form>

    <hr>

    <!-- Affichage des posts -->
    <h3>Publications récentes</h3>
    <?php while ($post = $posts_result->fetch_assoc()) : ?>
        <div class="post">
            <h4><?php echo htmlspecialchars($post['post_author']); ?></h4>
            <p><?php echo htmlspecialchars($post['post_content']); ?></p>
            <small>Publié le <?php echo $post['post_date']; ?></small>

            <!-- Afficher les réactions sur ce post -->
            <?php
            $post_id = $post['post_id'];
            $sql_reactions = "
                SELECT reaction_type, COUNT(*) AS total 
                FROM post_reactions 
                WHERE post_id = ? 
                GROUP BY reaction_type";
            $stmt_reactions = $conn->prepare($sql_reactions);
            $stmt_reactions->bind_param('i', $post_id);
            $stmt_reactions->execute();
            $result_reactions = $stmt_reactions->get_result();
            
            echo "<div>Réactions : ";
            while ($reaction = $result_reactions->fetch_assoc()) {
                echo htmlspecialchars($reaction['reaction_type']) . " (" . $reaction['total'] . ") ";
            }
            echo "</div>";
            ?>

            <!-- Formulaire pour ajouter une réaction -->
            <form action="react_post.php" method="POST">
                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                <select name="reaction_type">
                    <option value="aime">Aime</option>
                    <option value="haie">Haie</option>
                    <option value="joie">Joie</option>
                    <option value="triste">Triste</option>
                </select>
                <button type="submit">Réagir</button>
            </form>
            
            <!-- Afficher les commentaires pour ce post -->
            <?php
            $comments_sql = "
                SELECT comments.id AS comment_id, comments.content AS comment_content, comments.created_at AS comment_date, 
                       users.username AS comment_author 
                FROM comments 
                JOIN users ON comments.user_id = users.id 
                WHERE comments.post_id = ? 
                ORDER BY comments.created_at ASC";
            $comments_stmt = $conn->prepare($comments_sql);
            $comments_stmt->bind_param('i', $post_id);
            $comments_stmt->execute();
            $comments_result = $comments_stmt->get_result();
            ?>
            <h5>Commentaires</h5>
            <?php while ($comment = $comments_result->fetch_assoc()) : ?>
                <div class="comment">
                    <strong><?php echo htmlspecialchars($comment['comment_author']); ?> :</strong>
                    <p><?php echo htmlspecialchars($comment['comment_content']); ?></p>
                    <small><?php echo $comment['comment_date']; ?></small>

                    <!-- Afficher les réactions pour chaque commentaire -->
                    <?php
                    $comment_id = $comment['comment_id'];
                    $sql_comment_reactions = "
                        SELECT reaction_type, COUNT(*) AS total 
                        FROM comment_reactions 
                        WHERE comment_id = ? 
                        GROUP BY reaction_type";
                    $stmt_comment_reactions = $conn->prepare($sql_comment_reactions);
                    $stmt_comment_reactions->bind_param('i', $comment_id);
                    $stmt_comment_reactions->execute();
                    $result_comment_reactions = $stmt_comment_reactions->get_result();

                    echo "<div>Réactions : ";
                    while ($comment_reaction = $result_comment_reactions->fetch_assoc()) {
                        echo htmlspecialchars($comment_reaction['reaction_type']) . " (" . $comment_reaction['total'] . ") ";
                    }
                    echo "</div>";
                    ?>

                    <!-- Formulaire pour ajouter une réaction à un commentaire -->
                    <form action="react_comment.php" method="POST">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                        <select name="reaction_type">
                            <option value="aime">Aime</option>
                            <option value="haie">Haie</option>
                            <option value="joie">Joie</option>
                            <option value="triste">Triste</option>
                        </select>
                        <button type="submit">Réagir</button>
                    </form>
                </div>
            <?php endwhile; ?>
            
            <!-- Formulaire pour ajouter un commentaire à ce post -->
            <form action="comment.php" method="POST">
                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                <textarea name="content" placeholder="Votre commentaire..." required></textarea><br>
                <button type="submit">Commenter</button>
            </form>
        </div>
    <?php endwhile; ?>
</body>
</html>

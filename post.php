<?php
session_start();

// Authenticated page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT posts.*, users.name AS author
     FROM posts
     JOIN users ON users.id = posts.user_id
     WHERE posts.id = ?'
);
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

$errors = [];
$content = '';

// Add a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $errors[] = 'Comment is required.';
    } elseif (strlen($content) > 1000) {
        $errors[] = 'Comment must be at most 1000 characters.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$id, $_SESSION['user_id'], $content]);

            $pdo->commit();

            header("Location: post.php?id=$id");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to add comment. Please try again.';
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT comments.*, users.name AS author
     FROM comments
     JOIN users ON users.id = comments.user_id
     WHERE comments.post_id = ?
     ORDER BY comments.created_at ASC, comments.id ASC'
);
$stmt->execute([$id]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?></title>
</head>
<body>
    <p>Logged in as <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | <a href="index.php">Home</a> | <a href="logout.php">Logout</a></p>

    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div>
        <small>
            By <?= htmlspecialchars($post['author']) ?> on <?= $post['created_at'] ?>
            <?php if ($post['updated_at']): ?><em>(edited <?= $post['updated_at'] ?>)</em><?php endif; ?>
            <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                | <a href="edit_post.php?id=<?= $post['id'] ?>">Edit</a> |
                <form method="post" action="delete_post.php" style="display: inline;" onsubmit="return confirm('Delete this post and all its comments?');">
                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            <?php endif; ?>
        </small>
    </div>
    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

    <hr>

    <h2>Comments (<?= count($comments) ?>)</h2>

    <?php if (!$comments): ?>
        <p>No comments yet.</p>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <div style="margin-bottom: 1em;">
            <strong><?= htmlspecialchars($comment['author']) ?></strong>
            <small>
                on <?= $comment['created_at'] ?>
                <?php if ($comment['updated_at']): ?><em>(edited <?= $comment['updated_at'] ?>)</em><?php endif; ?>
                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                    | <a href="edit_comment.php?id=<?= $comment['id'] ?>">Edit</a> |
                    <form method="post" action="delete_comment.php" style="display: inline;" onsubmit="return confirm('Delete this comment?');">
                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                        <button type="submit">Delete</button>
                    </form>
                <?php endif; ?>
            </small>
            <br>
            <?= nl2br(htmlspecialchars($comment['content'])) ?>
        </div>
    <?php endforeach; ?>

    <h3>Add a Comment</h3>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p>
            <textarea name="content" rows="3" cols="50" required maxlength="1000"><?= htmlspecialchars($content) ?></textarea>
        </p>
        <button type="submit">Comment</button>
    </form>
</body>
</html>

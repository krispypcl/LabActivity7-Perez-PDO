<?php
session_start();

// Authenticated page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$errors = [];
$title = '';
$content = '';

// Add a new post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Title must be at most 255 characters.';
    }

    if ($content === '') {
        $errors[] = 'Content is required.';
    } elseif (strlen($content) > 5000) {
        $errors[] = 'Content must be at most 5000 characters.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $title, $content]);

            $pdo->commit();

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create post. Please try again.';
        }
    }
}

// News feed: all posts, most recent first
$stmt = $pdo->prepare(
    'SELECT posts.*, users.name AS author
     FROM posts
     JOIN users ON users.id = posts.user_id
     ORDER BY posts.created_at DESC, posts.id DESC'
);
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <p>Logged in as <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | <a href="logout.php">Logout</a></p>

    <h1>News Feed</h1>

    <h2>New Post</h2>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p>
            <label>Title<br>
            <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required maxlength="255"></label>
        </p>
        <p>
            <label>Content<br>
            <textarea name="content" rows="5" cols="50" required maxlength="5000"><?= htmlspecialchars($content) ?></textarea></label>
        </p>
        <button type="submit">Post</button>
    </form>

    <hr>

    <?php if (!$posts): ?>
        <p>No posts yet.</p>
    <?php endif; ?>

    <?php foreach ($posts as $post): ?>
        <h3><a href="post.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
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
        <p><a href="post.php?id=<?= $post['id'] ?>">View comments</a></p>
        <hr>
    <?php endforeach; ?>
</body>
</html>

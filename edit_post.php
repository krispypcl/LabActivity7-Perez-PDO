<?php
session_start();

// Authenticated page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$id = (int) ($_GET['id'] ?? 0);

// Only fetch the post if it belongs to the logged-in user
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit;
}

$errors = [];
$title = $post['title'];
$content = $post['content'];

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

            $stmt = $pdo->prepare('UPDATE posts SET title = ?, content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$title, $content, $id, $_SESSION['user_id']]);

            $pdo->commit();

            header("Location: post.php?id=$id");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to update post. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
</head>
<body>
    <p>Logged in as <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | <a href="index.php">Home</a> | <a href="logout.php">Logout</a></p>

    <h1>Edit Post</h1>

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
        <button type="submit">Save Changes</button>
        <a href="post.php?id=<?= $id ?>">Cancel</a>
    </form>
</body>
</html>

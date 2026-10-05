<?php
session_start();

// Authenticated page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

$id = (int) ($_GET['id'] ?? 0);

// Only fetch the comment if it belongs to the logged-in user
$stmt = $pdo->prepare('SELECT * FROM comments WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$comment = $stmt->fetch();

if (!$comment) {
    header('Location: index.php');
    exit;
}

$errors = [];
$content = $comment['content'];

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

            $stmt = $pdo->prepare('UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$content, $id, $_SESSION['user_id']]);

            $pdo->commit();

            header('Location: post.php?id=' . $comment['post_id']);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to update comment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Comment</title>
</head>
<body>
    <p>Logged in as <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> | <a href="index.php">Home</a> | <a href="logout.php">Logout</a></p>

    <h1>Edit Comment</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p>
            <textarea name="content" rows="3" cols="50" required maxlength="1000"><?= htmlspecialchars($content) ?></textarea>
        </p>
        <button type="submit">Save Changes</button>
        <a href="post.php?id=<?= $comment['post_id'] ?>">Cancel</a>
    </form>
</body>
</html>

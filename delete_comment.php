<?php
session_start();

// Authenticated page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// Only accept deletes through the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$postId = 0;

try {
    $pdo->beginTransaction();

    // Only fetch the comment if it belongs to the logged-in user
    $stmt = $pdo->prepare('SELECT post_id FROM comments WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    $comment = $stmt->fetch();

    if ($comment) {
        $postId = $comment['post_id'];

        $stmt = $pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $_SESSION['user_id']]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
}

header($postId ? "Location: post.php?id=$postId" : 'Location: index.php');
exit;

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

try {
    $pdo->beginTransaction();

    // Only deletes the post if it belongs to the logged-in user.
    // Its comments are removed automatically by ON DELETE CASCADE.
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
}

header('Location: index.php');
exit;

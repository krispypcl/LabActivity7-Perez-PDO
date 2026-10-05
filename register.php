<?php
session_start();

// Guest-only page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must be at most 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    } elseif (strlen($email) > 255) {
        $errors[] = 'Email must be at most 255 characters.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $pdo->rollBack();
                $errors[] = 'Email is already registered.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $pdo->commit();

                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post" onsubmit="if (this.password.value !== this.confirm_password.value) { alert('Passwords do not match.'); return false; }">
        <p>
            <label>Name<br>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required maxlength="100"></label>
        </p>
        <p>
            <label>Email<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required maxlength="255"></label>
        </p>
        <p>
            <label>Password<br>
            <input type="password" name="password" required minlength="8"></label>
        </p>
        <p>
            <label>Confirm Password<br>
            <input type="password" name="confirm_password" required minlength="8"></label>
        </p>
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>

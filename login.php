<?php
session_start();

// Guest-only page
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            header('Location: index.php');
            exit;
        }

        $errors[] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if (isset($_GET['registered'])): ?>
        <p style="color: green;">Registration successful. You can now log in.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <p>
            <label>Email<br>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required></label>
        </p>
        <p>
            <label>Password<br>
            <input type="password" name="password" required></label>
        </p>
        <button type="submit">Login</button>
    </form>

    <p>No account yet? <a href="register.php">Register</a></p>
</body>
</html>

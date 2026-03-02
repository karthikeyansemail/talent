<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Already logged in → redirect
if (is_logged_in()) {
    header('Location: ' . BASE . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = db()->prepare('SELECT id, name, email, password FROM admin_users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: ' . BASE . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Nalam Pulse Portal</title>
<link rel="stylesheet" href="<?= BASE ?>/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-logo">
        <span class="logo-icon">NP</span>
        <span class="logo-text">Nalam Pulse</span>
    </div>
    <h2 class="login-title">Portal Sign In</h2>
    <p class="login-sub">Admin access only</p>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE ?>/login.php" class="login-form">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>"
                   autofocus required placeholder="admin@nalampulse.com">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <p style="margin-top:20px;text-align:center;font-size:13px;color:var(--gray-500)">
        <a href="<?= MAIN_URL ?>" style="color:var(--primary)">← Back to nalampulse.com</a>
    </p>
</div>

</body>
</html>

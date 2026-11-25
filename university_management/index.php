<?php
/**
 * index.php - Login page (first page of the application)
 * ----------------------------------------------------------------------
 * Checks the username/email and password against the "users" table.
 * Passwords are stored as hashes and checked with password_verify().
 */
require_once 'db.php';

// Already logged in? Go straight to the dashboard.
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Your session has expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // Prepared statement: the user input is never placed directly in the SQL.
        $stmt = $conn->prepare('SELECT id, full_name, username, password FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Upgrade the hash automatically if PHP's default algorithm has changed.
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $upd->bind_param('si', $newHash, $user['id']);
                $upd->execute();
                $upd->close();
            }

            // New session id after login prevents session-fixation attacks.
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            unset($_SESSION['csrf_token']); // fresh CSRF token for the new session

            set_flash('success', 'Welcome back, ' . $user['full_name'] . '! You have logged in successfully.');
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-wrapper">

    <!-- Left: branding panel -->
    <section class="auth-hero">
        <div class="auth-brand">
            <span class="brand-logo"><?= icon('cap') ?></span>
            <strong><?= e(APP_NAME) ?></strong>
        </div>
        <h1>University Student Information &amp; Academic Management System</h1>
        <p>A simple and secure portal to manage student records, courses and semesters in one place.</p>
        <ul class="auth-features">
            <li><?= icon('users') ?> Complete student record management</li>
            <li><?= icon('xml') ?> Live XML export synchronised with MySQL</li>
            <li><?= icon('lock') ?> Secure login with hashed passwords</li>
        </ul>
    </section>

    <!-- Right: login form -->
    <section class="auth-panel">
        <div class="auth-card">
            <h2>Welcome back</h2>
            <p class="text-muted">Sign in to continue to your dashboard.</p>

            <?php
            if ($flash) {
                render_alert($flash['type'], $flash['message']);
            }
            if ($error !== '') {
                render_alert('error', $error);
            }
            ?>

            <form method="post" action="index.php" data-validate="login" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-icon">
                        <?= icon('user') ?>
                        <input type="text" id="username" name="username" value="<?= e($username) ?>"
                               placeholder="Enter your username" autocomplete="username" autofocus>
                    </div>
                    <small class="field-error"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <?= icon('lock') ?>
                        <input type="password" id="password" name="password" placeholder="Enter your password"
                               autocomplete="current-password">
                        <button type="button" class="toggle-password" aria-label="Show password"><?= icon('eye') ?></button>
                    </div>
                    <small class="field-error"></small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="auth-switch">New user? <a href="register.php">Create an account</a></p>

            <div class="demo-box">
                <strong>Demo credentials</strong>
                <span>Username: <code>admin</code> &nbsp; Password: <code>Admin@123</code></span>
            </div>
        </div>
    </section>
</div>
<script src="assets/js/script.js"></script>
</body>
</html>

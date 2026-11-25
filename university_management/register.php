<?php
/**
 * register.php - New user registration
 * ----------------------------------------------------------------------
 * Validates the form, checks that the username/email are not already
 * taken and stores the password as a secure hash (password_hash()).
 */
require_once 'db.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$data   = ['full_name' => '', 'username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['full_name'] = preg_replace('/\s+/', ' ', trim($_POST['full_name'] ?? ''));
    $data['username']  = trim($_POST['username'] ?? '');
    $data['email']     = strtolower(trim($_POST['email'] ?? ''));
    $password          = $_POST['password'] ?? '';
    $confirm           = $_POST['confirm_password'] ?? '';

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session has expired. Please submit the form again.';
    }

    // ---- Server-side validation ----
    if (!preg_match("/^[A-Za-z][A-Za-z .']{2,99}$/", $data['full_name'])) {
        $errors['full_name'] = 'Full name must be 3-100 characters (letters and spaces only).';
    }
    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $data['username'])) {
        $errors['username'] = 'Username must be 4-30 characters: letters, digits or underscore.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must be at least 8 characters and contain letters and numbers.';
    }
    if ($confirm === '' || $password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // ---- Check for an existing username / email ----
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $data['username'], $data['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row['username'], $data['username']) === 0) {
                $errors['username'] = 'This username is already taken.';
            }
            if (strcasecmp($row['email'], $data['email']) === 0) {
                $errors['email'] = 'An account with this email already exists.';
            }
        }
        $stmt->close();
    }

    // ---- Save the new user ----
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare('INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $data['full_name'], $data['username'], $data['email'], $hash);
            $stmt->execute();
            $stmt->close();

            set_flash('success', 'Registration successful! You can now log in with your new account.');
            redirect('index.php');
        } catch (mysqli_sql_exception $ex) {
            // 1062 = duplicate entry (e.g. two people registering the same username at once)
            $errors['form'] = ($ex->getCode() === 1062)
                ? 'This username or email is already registered.'
                : 'Registration failed. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-wrapper">

    <section class="auth-hero">
        <div class="auth-brand">
            <span class="brand-logo"><?= icon('cap') ?></span>
            <strong><?= e(APP_NAME) ?></strong>
        </div>
        <h1>Create your account</h1>
        <p>Register as a staff user to add, update and manage student academic records.</p>
        <ul class="auth-features">
            <li><?= icon('check') ?> Quick and free registration</li>
            <li><?= icon('lock') ?> Passwords are stored as secure hashes</li>
            <li><?= icon('dashboard') ?> Instant access to the dashboard</li>
        </ul>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <h2>Register</h2>
            <p class="text-muted">Fill in the details below to create an account.</p>

            <?php
            if (isset($errors['form'])) {
                render_alert('error', $errors['form']);
            } elseif (!empty($errors)) {
                render_alert('error', 'Please correct the highlighted fields.');
            }
            ?>

            <form method="post" action="register.php" data-validate="register" novalidate>
                <?= csrf_field() ?>

                <div class="form-group<?= error_class($errors, 'full_name') ?>">
                    <label for="full_name">Full Name</label>
                    <div class="input-icon">
                        <?= icon('user') ?>
                        <input type="text" id="full_name" name="full_name" value="<?= e($data['full_name']) ?>"
                               placeholder="e.g. Anish Kumar" maxlength="100" autocomplete="name">
                    </div>
                    <small class="field-error"><?= field_error($errors, 'full_name') ?></small>
                </div>

                <div class="form-row">
                    <div class="form-group<?= error_class($errors, 'username') ?>">
                        <label for="username">Username</label>
                        <div class="input-icon">
                            <?= icon('user') ?>
                            <input type="text" id="username" name="username" value="<?= e($data['username']) ?>"
                                   placeholder="e.g. anish_k" maxlength="30" autocomplete="username">
                        </div>
                        <small class="field-error"><?= field_error($errors, 'username') ?></small>
                    </div>

                    <div class="form-group<?= error_class($errors, 'email') ?>">
                        <label for="email">Email</label>
                        <div class="input-icon">
                            <?= icon('mail') ?>
                            <input type="email" id="email" name="email" value="<?= e($data['email']) ?>"
                                   placeholder="you@example.com" maxlength="100" autocomplete="email">
                        </div>
                        <small class="field-error"><?= field_error($errors, 'email') ?></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group<?= error_class($errors, 'password') ?>">
                        <label for="password">Password</label>
                        <div class="input-icon">
                            <?= icon('lock') ?>
                            <input type="password" id="password" name="password" placeholder="Min. 8 characters"
                                   autocomplete="new-password">
                            <button type="button" class="toggle-password" aria-label="Show password"><?= icon('eye') ?></button>
                        </div>
                        <small class="field-error"><?= field_error($errors, 'password') ?></small>
                    </div>

                    <div class="form-group<?= error_class($errors, 'confirm_password') ?>">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-icon">
                            <?= icon('lock') ?>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Re-enter password" autocomplete="new-password">
                        </div>
                        <small class="field-error"><?= field_error($errors, 'confirm_password') ?></small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="index.php">Login here</a></p>
        </div>
    </section>
</div>
<script src="assets/js/script.js"></script>
</body>
</html>

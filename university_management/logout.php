<?php
/**
 * logout.php - Log the user out
 * ----------------------------------------------------------------------
 * The logout link carries the CSRF token (logout.php?token=...), so other
 * websites cannot log the user out without permission.
 */
require_once 'db.php';

if (!is_logged_in()) {
    redirect('index.php');
}

if (!verify_csrf($_GET['token'] ?? '')) {
    set_flash('error', 'Invalid logout request. Please use the Logout button.');
    redirect('dashboard.php');
}

// 1. Remove all session data
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// 3. Destroy the session on the server
session_destroy();

// 4. Start a fresh session only to show the "logged out" message on the login page
session_start();
session_regenerate_id(true);
set_flash('success', 'You have been logged out successfully.');
redirect('index.php');

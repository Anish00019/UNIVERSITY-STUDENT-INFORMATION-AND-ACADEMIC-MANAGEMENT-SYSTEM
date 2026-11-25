<?php
/**
 * db.php - Database connection
 * ----------------------------------------------------------------------
 * Every page includes this file first. It:
 *   1. Connects to the MySQL database using MySQLi.
 *   2. Loads the common helper functions (sessions, CSRF, XML sync ...).
 *
 * Change the settings below if your XAMPP MySQL uses a different
 * username, password or port.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');                    // XAMPP default user
define('DB_PASS', '');                        // XAMPP default password is empty
define('DB_NAME', 'university_management');
define('DB_PORT', 3306);

// Make MySQLi throw exceptions on errors, so problems are never silently ignored.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    // Show a friendly message instead of a raw PHP error.
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Database Error</title>'
       . '<link rel="stylesheet" href="assets/css/style.css"></head><body class="auth-body">'
       . '<div class="db-error card"><h2>Database connection failed</h2>'
       . '<p>Please make sure that:</p><ul>'
       . '<li>MySQL is started in the XAMPP Control Panel.</li>'
       . '<li>The database <strong>university_management</strong> has been imported from '
       . '<code>database/university_management.sql</code>.</li>'
       . '<li>The settings in <code>db.php</code> are correct.</li></ul>'
       . '<p class="text-muted">Error: ' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
       . '</div></body></html>';
    exit;
}

// Load shared helper functions (session start, CSRF, validation, XML sync).
require_once __DIR__ . '/includes/functions.php';

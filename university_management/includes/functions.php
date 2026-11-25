<?php
/**
 * includes/functions.php - Common helper functions
 * ----------------------------------------------------------------------
 * Loaded automatically by db.php. Contains:
 *   - Session start (with secure cookie settings)
 *   - Application settings (course list, XML file path)
 *   - Security helpers (output escaping, CSRF tokens, login check)
 *   - Flash messages (success / error messages shown once)
 *   - Student validation helpers
 *   - MySQL -> XML synchronisation
 *   - Small SVG icon helper used by the user interface
 */

/* ---------------------------------------------------------------------
 * 1. Session
 * ------------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // cookie is removed when the browser is closed
        'path'     => '/',
        'httponly' => true,       // JavaScript cannot read the session cookie
        'samesite' => 'Lax',      // extra protection against CSRF
    ]);
    session_start();
}

/* ---------------------------------------------------------------------
 * 2. Application settings
 * ------------------------------------------------------------------- */
date_default_timezone_set('Asia/Kolkata');   // Indian Standard Time for all dates

define('APP_NAME', 'UniSIMS');
define('APP_FULL_NAME', 'University Student Information & Academic Management System');
define('XML_FILE', dirname(__DIR__) . '/students.xml');
define('MAX_SEMESTER', 8);

// Courses offered by the university (used in forms, validation and dashboard).
const COURSES = [
    'BCA',
    'BBA',
    'B.Com',
    'B.Sc Computer Science',
    'BA',
    'MCA',
    'MBA',
    'M.Com',
];

/* ---------------------------------------------------------------------
 * 3. Security helpers
 * ------------------------------------------------------------------- */

/**
 * Escape a value before printing it in HTML (prevents XSS attacks).
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect the browser to another page and stop the script.
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Returns true when a user is logged in.
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Protect a page: visitors who are not logged in are sent to the login page.
 */
function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access that page.');
        redirect('index.php');
    }
}

/**
 * Get (or create) the CSRF token stored in the session.
 * The token is placed in every form and checked when the form is submitted,
 * so other websites cannot submit forms on behalf of the user.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden input field containing the CSRF token (print inside every <form>).
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Check a submitted CSRF token against the one in the session.
 */
function verify_csrf($token)
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

/* ---------------------------------------------------------------------
 * 4. Flash messages (shown once on the next page)
 * ------------------------------------------------------------------- */

/**
 * Save a message to show on the next page. $type = success | error | warning | info
 */
function set_flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Read the flash message and remove it from the session.
 */
function get_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Print an alert box (used for flash messages and form errors).
 */
function render_alert($type, $message)
{
    $iconName = ($type === 'success') ? 'check' : 'alert';
    echo '<div class="alert alert-' . e($type) . '" role="alert">'
       . icon($iconName)
       . '<span>' . e($message) . '</span>'
       . '<button type="button" class="alert-close" aria-label="Close">&times;</button>'
       . '</div>';
}

/**
 * Return the escaped error message for one form field ('' if no error).
 */
function field_error(array $errors, $field)
{
    return isset($errors[$field]) ? e($errors[$field]) : '';
}

/**
 * Return ' has-error' when a field has an error (adds a red border via CSS).
 */
function error_class(array $errors, $field)
{
    return isset($errors[$field]) ? ' has-error' : '';
}

/* ---------------------------------------------------------------------
 * 5. Student helpers
 * ------------------------------------------------------------------- */

/**
 * Read the student form fields from $_POST and tidy them up.
 */
function get_student_input()
{
    return [
        'register_number' => strtoupper(trim($_POST['register_number'] ?? '')),
        'student_name'    => preg_replace('/\s+/', ' ', trim($_POST['student_name'] ?? '')),
        'course'          => trim($_POST['course'] ?? ''),
        'semester'        => trim($_POST['semester'] ?? ''),
        'email'           => strtolower(trim($_POST['email'] ?? '')),
        'contact_number'  => trim($_POST['contact_number'] ?? ''),
    ];
}

/**
 * Validate student data on the server side.
 * Returns an array of error messages (field name => message).
 * An empty array means the data is valid.
 */
function validate_student(array $data)
{
    $errors = [];

    if ($data['register_number'] === '') {
        $errors['register_number'] = 'Register number is required.';
    } elseif (!preg_match('/^[A-Z0-9]{5,20}$/', $data['register_number'])) {
        $errors['register_number'] = 'Use 5-20 letters and digits only (e.g. BCA23001).';
    }

    if ($data['student_name'] === '') {
        $errors['student_name'] = 'Student name is required.';
    } elseif (!preg_match("/^[A-Za-z][A-Za-z .']{2,99}$/", $data['student_name'])) {
        $errors['student_name'] = 'Name must be 3-100 characters (letters, spaces, dots only).';
    }

    if (!in_array($data['course'], COURSES, true)) {
        $errors['course'] = 'Please select a valid course.';
    }

    if (!ctype_digit($data['semester']) || (int) $data['semester'] < 1 || (int) $data['semester'] > MAX_SEMESTER) {
        $errors['semester'] = 'Please select a semester between 1 and ' . MAX_SEMESTER . '.';
    }

    if ($data['email'] === '') {
        $errors['email'] = 'Email ID is required.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($data['contact_number'] === '') {
        $errors['contact_number'] = 'Contact number is required.';
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $data['contact_number'])) {
        $errors['contact_number'] = 'Enter a valid 10-digit mobile number starting with 6-9.';
    }

    return $errors;
}

/**
 * Check whether a register number already exists.
 * $excludeId lets the edit page ignore the student that is being edited.
 */
function register_number_exists($conn, $registerNumber, $excludeId = 0)
{
    $stmt = $conn->prepare('SELECT id FROM students WHERE register_number = ? AND id <> ?');
    $stmt->bind_param('si', $registerNumber, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Fetch one student by id. Returns an associative array or null.
 */
function find_student($conn, $id)
{
    $stmt = $conn->prepare('SELECT id, register_number, student_name, course, semester, email, contact_number,
                                   created_at, updated_at
                            FROM students WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $student ?: null;
}

/* ---------------------------------------------------------------------
 * 6. XML synchronisation
 * ------------------------------------------------------------------- */

/**
 * Rebuild students.xml from the MySQL "students" table.
 * Called every time a student is added, updated or deleted, so the XML
 * file always contains the same records as the database.
 * Returns true on success, false if the file could not be written.
 */
function sync_students_xml($conn)
{
    $result = $conn->query('SELECT id, register_number, student_name, course, semester, email
                            FROM students ORDER BY register_number ASC');

    // DOMDocument builds a well-formed XML document and escapes special characters.
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->appendChild($dom->createComment(' Student records - automatically synchronised with the MySQL database '));

    $root = $dom->createElement('students');
    $root->setAttribute('generated', date('Y-m-d H:i:s'));
    $root->setAttribute('total', (string) $result->num_rows);
    $dom->appendChild($root);

    while ($row = $result->fetch_assoc()) {
        $student = $dom->createElement('student');
        $student->setAttribute('id', (string) $row['id']);

        foreach (['register_number', 'student_name', 'course', 'semester', 'email'] as $field) {
            $node = $dom->createElement($field);
            $node->appendChild($dom->createTextNode((string) $row[$field]));
            $student->appendChild($node);
        }
        $root->appendChild($student);
    }
    $result->free();

    // Write the file. "@" hides the PHP warning; we report failure ourselves.
    return @file_put_contents(XML_FILE, $dom->saveXML(), LOCK_EX) !== false;
}

/* ---------------------------------------------------------------------
 * 7. Dashboard helper
 * ------------------------------------------------------------------- */

/**
 * Work out the current academic session and running semesters.
 * Indian universities usually run: June-November = odd semesters,
 * December-May = even semesters.
 */
function academic_session_info()
{
    $month = (int) date('n');
    $year  = (int) date('Y');
    $start = ($month >= 6) ? $year : $year - 1;
    $isOdd = ($month >= 6 && $month <= 11);

    return [
        'session'   => $start . '-' . substr((string) ($start + 1), -2),
        'term'      => $isOdd ? 'Odd Semester' : 'Even Semester',
        'semesters' => $isOdd ? 'I, III, V, VII' : 'II, IV, VI, VIII',
    ];
}

/* ---------------------------------------------------------------------
 * 8. Icons (inline SVG, works offline - no icon library needed)
 * ------------------------------------------------------------------- */
function icon($name)
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>',
        'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'xml'       => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="10 13 8 15 10 17"/><polyline points="14 13 16 15 14 17"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'edit'      => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'trash'     => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'book'      => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'calendar'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'layers'    => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'menu'      => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'check'     => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert'     => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'eye'       => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'lock'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'user'      => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'mail'      => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'back'      => '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        'refresh'   => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'cap'       => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
        'external'  => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'save'      => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
    ];

    $inner = $paths[$name] ?? $paths['alert'];
    return '<svg class="icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

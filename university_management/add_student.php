<?php
/**
 * add_student.php - Add a new student (CREATE)
 * ----------------------------------------------------------------------
 * 1. Validates the form (JavaScript in the browser + PHP on the server).
 * 2. Rejects duplicate register numbers.
 * 3. Inserts the record with a prepared statement.
 * 4. Re-generates students.xml so XML stays in sync with MySQL.
 */
require_once 'db.php';
require_login();

$errors = [];
$data   = [
    'register_number' => '',
    'student_name'    => '',
    'course'          => '',
    'semester'        => '',
    'email'           => '',
    'contact_number'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = get_student_input();

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['form'] = csrf_error_message();
    } else {
        $errors = validate_student($data);

        if (!isset($errors['register_number']) && register_number_exists($conn, $data['register_number'])) {
            $errors['register_number'] = 'A student with this register number already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare('INSERT INTO students (register_number, student_name, course, semester, email, contact_number)
                                    VALUES (?, ?, ?, ?, ?, ?)');
            $semester = (int) $data['semester'];
            $stmt->bind_param('sssiss', $data['register_number'], $data['student_name'], $data['course'],
                              $semester, $data['email'], $data['contact_number']);
            $stmt->execute();
            $stmt->close();

            // Keep students.xml synchronised with the database.
            if (sync_students_xml($conn)) {
                set_flash('success', 'Student "' . $data['student_name'] . '" was added successfully and the XML file was updated.');
            } else {
                set_flash('warning', 'Student added, but students.xml could not be updated. Check the file permissions.');
            }
            redirect('view_students.php');
        } catch (mysqli_sql_exception $ex) {
            $errors['form'] = ($ex->getCode() === 1062)
                ? 'A student with this register number already exists.'
                : 'Could not save the student. Please try again.';
        }
    }
}

$pageTitle  = 'Add Student';
$activePage = 'add';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Add New Student</h2>
        <p class="text-muted">Fill in the student's academic details. Fields marked * are required.</p>
    </div>
    <a href="view_students.php" class="btn btn-secondary"><?= icon('users') ?> View Students</a>
</div>

<div class="card form-card">
    <?php
    if (isset($errors['form'])) {
        render_alert('error', $errors['form']);
    } elseif (!empty($errors)) {
        render_alert('error', 'Please correct the highlighted fields and try again.');
    }

    $formAction  = 'add_student.php';
    $submitLabel = 'Save Student';
    require 'includes/student_form.php';
    ?>
</div>

<?php require 'includes/footer.php'; ?>

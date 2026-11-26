<?php
/**
 * edit_student.php - Edit an existing student (UPDATE)
 * ----------------------------------------------------------------------
 * Loads the student by id, shows the filled-in form, validates the new
 * values, updates MySQL and then re-generates students.xml.
 */
require_once 'db.php';
require_login();

// The student id comes from the URL: edit_student.php?id=5
$id      = (int) ($_GET['id'] ?? 0);
$student = ($id > 0) ? find_student($conn, $id) : null;

if (!$student) {
    set_flash('error', 'Student record not found.');
    redirect('view_students.php');
}

$errors = [];
$data   = $student;   // pre-fill the form with the current values

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = get_student_input();

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors['form'] = csrf_error_message();
    } else {
        $errors = validate_student($data);

        // Same register number is allowed for this student, but not for another one.
        if (!isset($errors['register_number']) && register_number_exists($conn, $data['register_number'], $id)) {
            $errors['register_number'] = 'Another student already uses this register number.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare('UPDATE students
                                    SET register_number = ?, student_name = ?, course = ?, semester = ?, email = ?, contact_number = ?
                                    WHERE id = ?');
            $semester = (int) $data['semester'];
            $stmt->bind_param('sssissi', $data['register_number'], $data['student_name'], $data['course'],
                              $semester, $data['email'], $data['contact_number'], $id);
            $stmt->execute();
            $stmt->close();

            // Keep students.xml synchronised with the database.
            if (sync_students_xml($conn)) {
                set_flash('success', 'Student "' . $data['student_name'] . '" was updated successfully and the XML file was refreshed.');
            } else {
                set_flash('warning', 'Student updated, but students.xml could not be updated. Check the file permissions.');
            }
            redirect('view_students.php');
        } catch (mysqli_sql_exception $ex) {
            $errors['form'] = ($ex->getCode() === 1062)
                ? 'Another student already uses this register number.'
                : 'Could not update the student. Please try again.';
        }
    }
}

$pageTitle  = 'Edit Student';
$activePage = 'view';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Edit Student</h2>
        <p class="text-muted">
            Updating record <span class="reg-no"><?= e($student['register_number']) ?></span>
            &middot; Last updated <?= e(date('d M Y, h:i A', strtotime($student['updated_at']))) ?>
        </p>
    </div>
    <a href="view_students.php" class="btn btn-secondary"><?= icon('back') ?> Back to List</a>
</div>

<div class="card form-card">
    <?php
    if (isset($errors['form'])) {
        render_alert('error', $errors['form']);
    } elseif (!empty($errors)) {
        render_alert('error', 'Please correct the highlighted fields and try again.');
    }

    $formAction  = 'edit_student.php?id=' . $id;
    $submitLabel = 'Update Student';
    require 'includes/student_form.php';
    ?>
</div>

<?php require 'includes/footer.php'; ?>

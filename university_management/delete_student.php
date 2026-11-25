<?php
/**
 * delete_student.php - Delete a student (DELETE)
 * ----------------------------------------------------------------------
 * GET  delete_student.php?id=5  -> shows a confirmation page
 * POST (with CSRF token)        -> deletes the record, then updates students.xml
 *
 * Deleting only happens through POST + CSRF token, so a simple link or
 * a forged request from another website can never delete a record.
 */
require_once 'db.php';
require_login();

// ---- Step 2: the user confirmed the deletion ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. The student was not deleted.');
        redirect('view_students.php');
    }

    $student = ($id > 0) ? find_student($conn, $id) : null;
    if (!$student) {
        set_flash('error', 'Student record not found. It may have been deleted already.');
        redirect('view_students.php');
    }

    $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // Keep students.xml synchronised with the database.
    if (sync_students_xml($conn)) {
        set_flash('success', 'Student "' . $student['student_name'] . '" (' . $student['register_number'] . ') was deleted and the XML file was updated.');
    } else {
        set_flash('warning', 'Student deleted, but students.xml could not be updated. Check the file permissions.');
    }
    redirect('view_students.php');
}

// ---- Step 1: show the confirmation page ----
$id      = (int) ($_GET['id'] ?? 0);
$student = ($id > 0) ? find_student($conn, $id) : null;

if (!$student) {
    set_flash('error', 'Student record not found.');
    redirect('view_students.php');
}

$pageTitle  = 'Delete Student';
$activePage = 'view';
require 'includes/header.php';
?>

<div class="card confirm-card">
    <span class="confirm-icon"><?= icon('trash') ?></span>
    <h2>Delete this student?</h2>
    <p class="text-muted">This action cannot be undone. The record will be removed from MySQL and from students.xml.</p>

    <dl class="detail-list">
        <div><dt>Register Number</dt><dd><span class="reg-no"><?= e($student['register_number']) ?></span></dd></div>
        <div><dt>Student Name</dt><dd><?= e($student['student_name']) ?></dd></div>
        <div><dt>Course</dt><dd><?= e($student['course']) ?></dd></div>
        <div><dt>Semester</dt><dd><?= e($student['semester']) ?></dd></div>
        <div><dt>Email ID</dt><dd><?= e($student['email']) ?></dd></div>
        <div><dt>Contact Number</dt><dd><?= e($student['contact_number']) ?></dd></div>
    </dl>

    <form method="post" action="delete_student.php" class="confirm-actions">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
        <a href="view_students.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-danger"><?= icon('trash') ?> Yes, Delete Student</button>
    </form>
</div>

<?php require 'includes/footer.php'; ?>

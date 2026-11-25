<?php
/**
 * includes/student_form.php - Student form used by add_student.php and edit_student.php
 * ----------------------------------------------------------------------
 * Expects these variables to be set by the including page:
 *   $data        - array of field values
 *   $errors      - array of field errors (field => message)
 *   $formAction  - URL the form is submitted to
 *   $submitLabel - text for the submit button
 */
?>
<form method="post" action="<?= e($formAction) ?>" data-validate="student" novalidate>
    <?= csrf_field() ?>

    <div class="form-row">
        <div class="form-group<?= error_class($errors, 'register_number') ?>">
            <label for="register_number">Register Number <span class="req">*</span></label>
            <input type="text" id="register_number" name="register_number" class="uppercase"
                   value="<?= e($data['register_number']) ?>" placeholder="e.g. BCA23001" maxlength="20">
            <small class="field-error"><?= field_error($errors, 'register_number') ?></small>
        </div>

        <div class="form-group<?= error_class($errors, 'student_name') ?>">
            <label for="student_name">Student Name <span class="req">*</span></label>
            <input type="text" id="student_name" name="student_name"
                   value="<?= e($data['student_name']) ?>" placeholder="e.g. Aarav Sharma" maxlength="100">
            <small class="field-error"><?= field_error($errors, 'student_name') ?></small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group<?= error_class($errors, 'course') ?>">
            <label for="course">Course <span class="req">*</span></label>
            <select id="course" name="course">
                <option value="">-- Select Course --</option>
                <?php foreach (COURSES as $course): ?>
                    <option value="<?= e($course) ?>"<?= $data['course'] === $course ? ' selected' : '' ?>><?= e($course) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="field-error"><?= field_error($errors, 'course') ?></small>
        </div>

        <div class="form-group<?= error_class($errors, 'semester') ?>">
            <label for="semester">Semester <span class="req">*</span></label>
            <select id="semester" name="semester">
                <option value="">-- Select Semester --</option>
                <?php for ($i = 1; $i <= MAX_SEMESTER; $i++): ?>
                    <option value="<?= $i ?>"<?= (string) $data['semester'] === (string) $i ? ' selected' : '' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
            </select>
            <small class="field-error"><?= field_error($errors, 'semester') ?></small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group<?= error_class($errors, 'email') ?>">
            <label for="email">Email ID <span class="req">*</span></label>
            <input type="email" id="email" name="email"
                   value="<?= e($data['email']) ?>" placeholder="e.g. student@university.edu" maxlength="100">
            <small class="field-error"><?= field_error($errors, 'email') ?></small>
        </div>

        <div class="form-group<?= error_class($errors, 'contact_number') ?>">
            <label for="contact_number">Contact Number <span class="req">*</span></label>
            <input type="tel" id="contact_number" name="contact_number" inputmode="numeric"
                   value="<?= e($data['contact_number']) ?>" placeholder="10-digit mobile number" maxlength="10">
            <small class="field-error"><?= field_error($errors, 'contact_number') ?></small>
        </div>
    </div>

    <div class="form-actions">
        <a href="view_students.php" class="btn btn-secondary"><?= icon('back') ?> Cancel</a>
        <button type="submit" class="btn btn-primary"><?= icon('save') ?> <?= e($submitLabel) ?></button>
    </div>
</form>

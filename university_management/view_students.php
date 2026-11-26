<?php
/**
 * view_students.php - List all students (READ) with search
 * ----------------------------------------------------------------------
 * Students can be searched by register number or name and filtered by
 * course. The search uses a prepared statement with LIKE.
 */
require_once 'db.php';
require_login();

$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
if ($course !== '' && !in_array($course, COURSES, true)) {
    $course = '';   // ignore unknown course values
}

// ---- Build the query depending on the search/filter values ----
$sql    = 'SELECT id, register_number, student_name, course, semester, email, contact_number FROM students';
$where  = [];
$types  = '';
$params = [];

if ($search !== '') {
    $where[]  = '(register_number LIKE ? OR student_name LIKE ?)';
    $like     = '%' . $search . '%';
    $types   .= 'ss';
    $params[] = $like;
    $params[] = $like;
}
if ($course !== '') {
    $where[]  = 'course = ?';
    $types   .= 's';
    $params[] = $course;
}
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY register_number ASC';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalStudents = (int) $conn->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'];
$isFiltered    = ($search !== '' || $course !== '');

$pageTitle  = 'View Students';
$activePage = 'view';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Student Records</h2>
        <p class="text-muted">Manage all registered students. Total records: <strong><?= $totalStudents ?></strong></p>
    </div>
    <a href="add_student.php" class="btn btn-primary"><?= icon('user-plus') ?> Add Student</a>
</div>

<div class="card">
    <!-- Search and filter -->
    <form method="get" action="view_students.php" class="toolbar">
        <div class="search-box">
            <?= icon('search') ?>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search by register number or name..." maxlength="100">
        </div>
        <select name="course" aria-label="Filter by course">
            <option value="">All Courses</option>
            <?php foreach (COURSES as $c): ?>
                <option value="<?= e($c) ?>"<?= $course === $c ? ' selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><?= icon('search') ?> Search</button>
        <?php if ($isFiltered): ?>
            <a href="view_students.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($isFiltered): ?>
        <p class="result-info">
            Found <strong><?= count($students) ?></strong> matching record(s)
            <?php if ($search !== ''): ?>for "<strong><?= e($search) ?></strong>"<?php endif; ?>
            <?php if ($course !== ''): ?>in <strong><?= e($course) ?></strong><?php endif; ?>.
        </p>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <div class="empty-state">
            <?= icon('users') ?>
            <p><?= $isFiltered ? 'No students match your search.' : 'No student records found.' ?></p>
            <?php if (!$isFiltered): ?>
                <a href="add_student.php" class="btn btn-primary btn-sm">Add your first student</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Register No.</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Email ID</th>
                    <th>Contact No.</th>
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $index => $s): ?>
                    <tr>
                        <td class="text-muted"><?= $index + 1 ?></td>
                        <td><span class="reg-no"><?= e($s['register_number']) ?></span></td>
                        <td class="fw-600"><?= e($s['student_name']) ?></td>
                        <td><span class="badge"><?= e($s['course']) ?></span></td>
                        <td><?= e($s['semester']) ?></td>
                        <td><a href="mailto:<?= e($s['email']) ?>" class="link"><?= e($s['email']) ?></a></td>
                        <td><?= e($s['contact_number']) ?></td>
                        <td class="actions">
                            <a href="edit_student.php?id=<?= (int) $s['id'] ?>" class="btn-icon edit" title="Edit"><?= icon('edit') ?></a>
                            <a href="delete_student.php?id=<?= (int) $s['id'] ?>" class="btn-icon delete" title="Delete"><?= icon('trash') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>

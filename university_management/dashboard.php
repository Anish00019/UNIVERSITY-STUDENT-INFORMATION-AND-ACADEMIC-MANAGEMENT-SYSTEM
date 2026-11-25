<?php
/**
 * dashboard.php - Main dashboard
 * ----------------------------------------------------------------------
 * Shows summary statistics, semester information, recently added
 * students and quick links to every module.
 * (These queries contain no user input, so plain query() is safe here.)
 */
require_once 'db.php';
require_login();

// ---- Summary counts ----
$totalStudents = (int) $conn->query('SELECT COUNT(*) AS total FROM students')->fetch_assoc()['total'];
$activeCourses = (int) $conn->query('SELECT COUNT(DISTINCT course) AS total FROM students')->fetch_assoc()['total'];
$totalUsers    = (int) $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
$totalCourses  = count(COURSES);
$session       = academic_session_info();

// ---- Students per semester ----
$semesterCounts = array_fill(1, MAX_SEMESTER, 0);
$result = $conn->query('SELECT semester, COUNT(*) AS total FROM students GROUP BY semester');
while ($row = $result->fetch_assoc()) {
    $semesterCounts[(int) $row['semester']] = (int) $row['total'];
}
$maxSemesterCount = max(1, max($semesterCounts));

// ---- Students per course ----
$courseCounts = [];
$result = $conn->query('SELECT course, COUNT(*) AS total FROM students GROUP BY course ORDER BY total DESC, course ASC');
while ($row = $result->fetch_assoc()) {
    $courseCounts[$row['course']] = (int) $row['total'];
}

// ---- Five most recently added students ----
$recentStudents = $conn->query('SELECT id, register_number, student_name, course, semester, created_at
                                FROM students ORDER BY created_at DESC, id DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require 'includes/header.php';
?>

<!-- Welcome banner with quick actions -->
<section class="welcome card">
    <div>
        <h2>Hello, <?= e($_SESSION['full_name']) ?> 👋</h2>
        <p>Here is an overview of the student records for the academic session <?= e($session['session']) ?>.</p>
    </div>
    <div class="welcome-actions">
        <a href="add_student.php" class="btn btn-light"><?= icon('user-plus') ?> Add Student</a>
        <a href="view_students.php" class="btn btn-outline-light"><?= icon('users') ?> View Students</a>
    </div>
</section>

<!-- Statistic cards -->
<section class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon blue"><?= icon('users') ?></span>
        <div>
            <p class="stat-label">Total Students</p>
            <h3 class="stat-value"><?= $totalStudents ?></h3>
            <small class="text-muted">Records in database</small>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon green"><?= icon('book') ?></span>
        <div>
            <p class="stat-label">Total Courses</p>
            <h3 class="stat-value"><?= $totalCourses ?></h3>
            <small class="text-muted"><?= $activeCourses ?> with enrolled students</small>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon orange"><?= icon('calendar') ?></span>
        <div>
            <p class="stat-label">Current Term</p>
            <h3 class="stat-value small"><?= e($session['term']) ?></h3>
            <small class="text-muted">Session <?= e($session['session']) ?> &middot; Sem <?= e($session['semesters']) ?></small>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon purple"><?= icon('user') ?></span>
        <div>
            <p class="stat-label">System Users</p>
            <h3 class="stat-value"><?= $totalUsers ?></h3>
            <small class="text-muted">Registered accounts</small>
        </div>
    </div>
</section>

<section class="dashboard-grid">
    <!-- Recently added students -->
    <div class="card">
        <div class="card-header">
            <h3><?= icon('clock') ?> Recently Added Students</h3>
            <a href="view_students.php" class="link">View all &rarr;</a>
        </div>
        <?php if (empty($recentStudents)): ?>
            <div class="empty-state">
                <?= icon('users') ?>
                <p>No students added yet.</p>
                <a href="add_student.php" class="btn btn-primary btn-sm">Add your first student</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Register No.</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Sem</th>
                        <th>Added On</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentStudents as $s): ?>
                        <tr>
                            <td><span class="reg-no"><?= e($s['register_number']) ?></span></td>
                            <td><?= e($s['student_name']) ?></td>
                            <td><span class="badge"><?= e($s['course']) ?></span></td>
                            <td><?= e($s['semester']) ?></td>
                            <td class="text-muted"><?= e(date('d M Y', strtotime($s['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Semester information -->
    <div class="card">
        <div class="card-header">
            <h3><?= icon('layers') ?> Semester Information</h3>
        </div>
        <div class="semester-info">
            <div><span class="text-muted">Academic Session</span><strong><?= e($session['session']) ?></strong></div>
            <div><span class="text-muted">Running Term</span><strong><?= e($session['term']) ?></strong></div>
        </div>
        <ul class="bar-list">
            <?php foreach ($semesterCounts as $sem => $count): ?>
                <li>
                    <span class="bar-label">Semester <?= $sem ?></span>
                    <span class="bar-track"><span class="bar-fill" style="width: <?= round($count / $maxSemesterCount * 100) ?>%"></span></span>
                    <span class="bar-value"><?= $count ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="dashboard-grid">
    <!-- Students per course -->
    <div class="card">
        <div class="card-header">
            <h3><?= icon('book') ?> Students by Course</h3>
        </div>
        <?php if (empty($courseCounts)): ?>
            <p class="text-muted">No data available yet.</p>
        <?php else: ?>
            <ul class="bar-list">
                <?php foreach ($courseCounts as $course => $count): ?>
                    <li>
                        <span class="bar-label"><?= e($course) ?></span>
                        <span class="bar-track"><span class="bar-fill green" style="width: <?= round($count / max(1, $totalStudents) * 100) ?>%"></span></span>
                        <span class="bar-value"><?= $count ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Module navigation -->
    <div class="card">
        <div class="card-header">
            <h3><?= icon('dashboard') ?> Quick Links</h3>
        </div>
        <div class="quick-links">
            <a href="add_student.php" class="quick-link"><?= icon('user-plus') ?><span>Add Student</span></a>
            <a href="view_students.php" class="quick-link"><?= icon('users') ?><span>View Students</span></a>
            <a href="xml_view.php" class="quick-link"><?= icon('xml') ?><span>XML Records</span></a>
            <a href="students.xml" class="quick-link" target="_blank" rel="noopener"><?= icon('external') ?><span>Raw XML File</span></a>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>

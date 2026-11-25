<?php
/**
 * xml_view.php - Student Records - XML View
 * ----------------------------------------------------------------------
 * Reads students.xml using PHP SimpleXML and displays the records in an
 * HTML table. The XML file is re-generated automatically whenever a
 * student is added, edited or deleted. A "Sync Now" button is also
 * provided to rebuild the file manually from MySQL.
 */
require_once 'db.php';
require_login();

// ---- Manual "Sync Now" button (POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
    } elseif (sync_students_xml($conn)) {
        set_flash('success', 'students.xml was regenerated from the MySQL database.');
    } else {
        set_flash('error', 'Could not write students.xml. Check the folder permissions.');
    }
    redirect('xml_view.php');
}

// Create the XML file if it does not exist yet.
if (!file_exists(XML_FILE)) {
    sync_students_xml($conn);
}

// ---- Load and read the XML file ----
$records  = [];
$xmlError = '';
$xml      = null;

libxml_use_internal_errors(true);          // collect XML errors instead of printing warnings
if (file_exists(XML_FILE)) {
    $xml = simplexml_load_file(XML_FILE);
}

if ($xml === false || $xml === null) {
    $xmlError = 'The XML file could not be read. Click "Sync Now" to rebuild it from the database.';
} else {
    foreach ($xml->student as $student) {
        $records[] = [
            'id'              => (string) $student['id'],
            'register_number' => (string) $student->register_number,
            'student_name'    => (string) $student->student_name,
            'course'          => (string) $student->course,
            'semester'        => (string) $student->semester,
            'email'           => (string) $student->email,
        ];
    }
}
libxml_clear_errors();

// ---- Compare with MySQL to show the synchronisation status ----
$dbRegNumbers = [];
$result = $conn->query('SELECT register_number FROM students ORDER BY register_number');
while ($row = $result->fetch_assoc()) {
    $dbRegNumbers[] = $row['register_number'];
}
$xmlRegNumbers = array_column($records, 'register_number');
sort($dbRegNumbers);
sort($xmlRegNumbers);
$inSync = ($xmlError === '' && $dbRegNumbers === $xmlRegNumbers);

$generated = ($xml) ? (string) $xml['generated'] : '';
$rawXml    = file_exists(XML_FILE) ? file_get_contents(XML_FILE) : '';

$pageTitle  = 'XML Records';
$activePage = 'xml';
require 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Student Records &ndash; XML View</h2>
        <p class="text-muted">Records loaded from <code>students.xml</code> using PHP SimpleXML.</p>
    </div>
    <div class="page-header-actions">
        <a href="students.xml" target="_blank" rel="noopener" class="btn btn-secondary"><?= icon('external') ?> Open XML File</a>
        <form method="post" action="xml_view.php">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary"><?= icon('refresh') ?> Sync Now</button>
        </form>
    </div>
</div>

<!-- XML file information -->
<section class="xml-meta">
    <div class="xml-meta-item">
        <span class="text-muted">File</span>
        <strong>students.xml</strong>
    </div>
    <div class="xml-meta-item">
        <span class="text-muted">Records in XML</span>
        <strong><?= count($records) ?></strong>
    </div>
    <div class="xml-meta-item">
        <span class="text-muted">Last Generated</span>
        <strong><?= $generated !== '' ? e(date('d M Y, h:i A', strtotime($generated))) : '—' ?></strong>
    </div>
    <div class="xml-meta-item">
        <span class="text-muted">MySQL Status</span>
        <?php if ($inSync): ?>
            <strong class="status status-success"><?= icon('check') ?> In sync</strong>
        <?php else: ?>
            <strong class="status status-warning"><?= icon('alert') ?> Out of sync</strong>
        <?php endif; ?>
    </div>
</section>

<div class="card">
    <?php if ($xmlError !== ''): ?>
        <?php render_alert('error', $xmlError); ?>
    <?php elseif (empty($records)): ?>
        <div class="empty-state">
            <?= icon('xml') ?>
            <p>The XML file does not contain any student records yet.</p>
            <a href="add_student.php" class="btn btn-primary btn-sm">Add a student</a>
        </div>
    <?php else: ?>
        <div class="toolbar">
            <div class="search-box">
                <?= icon('search') ?>
                <input type="search" id="xmlFilter" data-table-filter="xmlTable" placeholder="Quick filter XML records...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="xmlTable">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Register Number</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Email</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $index => $r): ?>
                    <tr>
                        <td class="text-muted"><?= $index + 1 ?></td>
                        <td><span class="reg-no"><?= e($r['register_number']) ?></span></td>
                        <td class="fw-600"><?= e($r['student_name']) ?></td>
                        <td><span class="badge"><?= e($r['course']) ?></span></td>
                        <td><?= e($r['semester']) ?></td>
                        <td><?= e($r['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="no-results" hidden><td colspan="6" class="text-center text-muted">No matching records.</td></tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Raw XML source (useful during the project demonstration) -->
<?php if ($rawXml !== ''): ?>
    <details class="card xml-source">
        <summary><?= icon('xml') ?> View raw XML source</summary>
        <pre><code><?= e($rawXml) ?></code></pre>
    </details>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>

<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$studentModel = new Student($db);
$classModel = new ClassSection($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id = (int) ($_POST['class_id'] ?? 0);

    // Validate IDs
    if ($student_id <= 0 || $class_id <= 0) {
        $message = "Please select both a student and a class section.";
        $messageType = "danger";
    } else {
        $result = $repo->enroll($student_id, $class_id);

        if ($result === 'already_enrolled') {
            $message = "Enrollment Blocked: This student is already actively enrolled in the selected class section.";
            $messageType = "warning";
        } elseif ($result !== false && is_numeric($result)) {
            $student = $studentModel->find($student_id);
            $cls = $classModel->find($class_id);
            $message = "Success! {$student['full_name']} was enrolled in {$cls['class_code']}. (Enrollment ID: #{$result}). Slots decremented.";
            $messageType = "success";
        } else {
            $message = "Enrollment Failed: No slots available in the selected class section! The transaction was rolled back.";
            $messageType = "danger";
        }
    }
}

$students = $studentModel->all();
$classList = $classModel->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>⚡ Enroll Existing Student</h2>
    <a href="admin/enroll.php" class="btn btn-secondary btn-sm">Refresh</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Form -->
    <div class="card">
        <h3>Enrollment Form</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
            Select an existing student from the database and assign them to an available class section.
        </p>

        <form method="POST" action="admin/enroll.php">
            <div class="form-group">
                <label for="student_id">Select Existing Student *</label>
                <select id="student_id" name="student_id" class="form-control" required>
                    <option value="">-- Choose a Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?php echo (int)$st['student_id']; ?>">
                            <?php echo htmlspecialchars($st['full_name'] . ' (' . ($st['email'] ?? 'No email') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_id">Select Class Section *</label>
                <select id="class_id" name="class_id" class="form-control" required>
                    <option value="">-- Choose a Class Section --</option>
                    <?php foreach ($classList as $cl): ?>
                        <option value="<?php echo (int)$cl['class_id']; ?>">
                            <?php echo htmlspecialchars($cl['class_code'] . ' - ' . $cl['course_name'] . ' [' . $cl['slots'] . ' slots left]'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Enroll Student
            </button>
        </form>
    </div>

    <!-- Instructions / Rules card -->
    <div class="card">
        <h3>Enrollment Integrity Rules</h3>
        <ul style="padding-left: 1.25rem; font-size: 0.95rem; color: var(--text-main); line-height: 1.8;">
            <li><strong>Single Transaction:</strong> Slot deduction and enrollment insertion are executed in a single atomic transaction.</li>
            <li><strong>Over-enrollment Prevention:</strong> Sections with 0 seats will abort and rollback immediately.</li>
            <li><strong>Duplicate Guard:</strong> Students cannot be enrolled multiple times into the same active class section.</li>
            <li><strong>Cancellation:</strong> If needed, active enrollments can be cancelled at the <a href="admin/enrollments.php" style="color: var(--primary);">Enrollments Roster</a>, restoring the slot automatically.</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

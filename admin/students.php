<?php
// admin/students.php -- Student recording form (multi-table transaction)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$classes = new ClassSection($db);

$message = '';
$messageType = '';
$details = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $class_id = (int) ($_POST['class_id'] ?? 0);

    // Server-side validation
    if (empty($full_name)) {
        $message = "Validation Error: Student Full Name is required.";
        $messageType = "danger";
    } elseif ($class_id <= 0) {
        $message = "Validation Error: Please select a valid class section.";
        $messageType = "danger";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Validation Error: Please enter a valid email address.";
        $messageType = "danger";
    } else {
        // Call atomic transaction in EnrollmentRepository
        $result = $repo->recordStudent($full_name, $email, $phone, $class_id);

        if ($result !== false && is_array($result)) {
            $student_id = $result['student_id'];
            $enrollment_id = $result['enrollment_id'];

            // Fetch the updated class info to show updated slots
            $updatedClass = $classes->find($class_id);
            $remaining = $updatedClass ? $updatedClass['slots'] : 'N/A';

            $message = "Success! Student '{$full_name}' recorded and enrolled into class '{$updatedClass['class_code']}'. (Student ID: #{$student_id}, Enrollment ID: #{$enrollment_id}, Remaining Slots: {$remaining}). Transaction committed successfully.";
            $messageType = "success";
            $details = [
                'student_id' => $student_id,
                'enrollment_id' => $enrollment_id,
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'class' => $updatedClass['class_code'] . ' - ' . $updatedClass['course_name'],
                'remaining_slots' => $remaining
            ];

            // Clear POST fields on success
            $_POST = [];
        } else {
            // Failed: Transaction rolled back (e.g. 0 slots remaining)
            $selectedClass = $classes->find($class_id);
            $className = $selectedClass ? $selectedClass['class_code'] : "Class #{$class_id}";
            $message = "Transaction Rolled Back: No slots available for {$className}! The database transaction ensured that no partial student or enrollment records were created.";
            $messageType = "danger";
        }
    }
}

// Fetch up-to-date class list for dropdown
$classList = $classes->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>📝 Student Registration & Enrollment</h2>
    <a href="admin/students.php" class="btn btn-secondary btn-sm">Reset Form</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <div>
            <strong><?php echo ($messageType === 'success') ? '✅ ' : '❌ '; ?></strong>
            <?php echo htmlspecialchars($message); ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Registration Form -->
    <div class="card">
        <h3>Record Student (Multi-Table Transaction)</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">
            This single operation inserts into <code>students</code>, inserts into <code>enrollments</code>, and decrements <code>classes.slots</code> inside a database transaction.
        </p>

        <form method="POST" action="admin/students.php">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                       placeholder="e.g. John Doe" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       placeholder="e.g. john.doe@example.com">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                       placeholder="e.g. 09171234567">
            </div>

            <div class="form-group">
                <label for="class_id">Select Class Section *</label>
                <select id="class_id" name="class_id" class="form-control" required>
                    <option value="">-- Choose a Class Section --</option>
                    <?php foreach ($classList as $cls): ?>
                        <option value="<?php echo (int)$cls['class_id']; ?>" 
                            <?php 
                                $selectedClassId = (int)($_POST['class_id'] ?? 0);
                                echo ($selectedClassId === (int)$cls['class_id']) ? 'selected' : ''; 
                            ?>>
                            <?php 
                                echo htmlspecialchars($cls['class_code'] . ' - ' . $cls['course_name'] . ' (' . $cls['schedule'] . ') [Slots: ' . $cls['slots'] . ']'); 
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-muted);">Sections with [Slots: 0] will safely trigger a transaction rollback.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Record Student & Enroll
            </button>
        </form>
    </div>

    <!-- Information & Available Classes Overview -->
    <div>
        <?php if ($details): ?>
            <div class="card" style="border-left: 4px solid var(--success);">
                <h3 style="color: var(--success);">🎉 Transaction Receipt</h3>
                <p><strong>Student ID:</strong> #<?php echo htmlspecialchars($details['student_id']); ?></p>
                <p><strong>Enrollment ID:</strong> #<?php echo htmlspecialchars($details['enrollment_id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($details['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($details['email']); ?></p>
                <p><strong>Enrolled In:</strong> <?php echo htmlspecialchars($details['class']); ?></p>
                <p><strong>Updated Available Slots:</strong> <span class="badge badge-slot slot-available"><?php echo htmlspecialchars($details['remaining_slots']); ?></span></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Class Capacities Live Status</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Class Code</th>
                            <th>Course</th>
                            <th>Slots</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classList as $cls): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cls['class_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cls['course_name']); ?></td>
                                <td>
                                    <?php if ($cls['slots'] > 0): ?>
                                        <span class="badge badge-slot slot-available"><?php echo (int)$cls['slots']; ?> slots</span>
                                    <?php else: ?>
                                        <span class="badge badge-slot slot-zero">0 (Full - Rollback trigger)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

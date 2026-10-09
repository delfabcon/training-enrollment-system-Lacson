<?php
// admin/classes.php -- Manage class schedules and slots
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Course.php';

$classModel = new ClassSection($db);
$courseModel = new Course($db);

$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        if ($classModel->delete($id)) {
            $message = "Class section successfully deleted.";
            $messageType = "success";
        } else {
            $message = "Unable to delete class section.";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $message = "Cannot delete class section: existing enrollments are linked to it.";
        $messageType = "danger";
    }
}

// Handle Add / Edit
$editClass = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $course_id = (int)($_POST['course_id'] ?? 0);
    $class_code = trim($_POST['class_code'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $instructor = trim($_POST['instructor'] ?? '');
    $slots = (int)($_POST['slots'] ?? 0);

    // Validation
    if ($course_id <= 0 || empty($class_code) || empty($schedule) || empty($instructor) || $slots < 0) {
        $message = "Please fill in all required fields and ensure slots is greater than or equal to 0.";
        $messageType = "danger";
        if ($class_id > 0) {
            $editClass = $classModel->find($class_id);
        }
    } else {
        try {
            if ($class_id > 0) {
                $classModel->update($class_id, $course_id, $class_code, $schedule, $instructor, $slots);
                $message = "Class section '{$class_code}' updated successfully!";
                $messageType = "success";
            } else {
                $classModel->create($course_id, $class_code, $schedule, $instructor, $slots);
                $message = "New class section '{$class_code}' added successfully!";
                $messageType = "success";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editClass = $classModel->find($editId);
}

$courses = $courseModel->all();
$classes = $classModel->allWithCourse();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>🗓️ Class Section & Slot Management</h2>
    <a href="admin/classes.php" class="btn btn-secondary btn-sm">Refresh List</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Class Form -->
    <div class="card">
        <h3><?php echo $editClass ? '✏️ Edit Class Section' : '➕ Add Class Section'; ?></h3>
        <form method="POST" action="admin/classes.php">
            <?php if ($editClass): ?>
                <input type="hidden" name="class_id" value="<?php echo (int)$editClass['class_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="course_id">Select Course *</label>
                <select id="course_id" name="course_id" class="form-control" required>
                    <option value="">-- Choose a Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo (int)$c['course_id']; ?>" 
                            <?php 
                                $selectedId = $editClass['course_id'] ?? ($_POST['course_id'] ?? 0);
                                echo ((int)$selectedId === (int)$c['course_id']) ? 'selected' : ''; 
                            ?>>
                            <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class_code">Class / Section Code *</label>
                <input type="text" id="class_code" name="class_code" class="form-control" 
                       value="<?php echo htmlspecialchars($editClass['class_code'] ?? ($_POST['class_code'] ?? '')); ?>" 
                       placeholder="e.g. WD101-SEC1" required>
            </div>

            <div class="form-group">
                <label for="schedule">Schedule *</label>
                <input type="text" id="schedule" name="schedule" class="form-control" 
                       value="<?php echo htmlspecialchars($editClass['schedule'] ?? ($_POST['schedule'] ?? '')); ?>" 
                       placeholder="e.g. Mon/Wed 09:00 AM - 11:00 AM" required>
            </div>

            <div class="form-group">
                <label for="instructor">Instructor Name *</label>
                <input type="text" id="instructor" name="instructor" class="form-control" 
                       value="<?php echo htmlspecialchars($editClass['instructor'] ?? ($_POST['instructor'] ?? '')); ?>" 
                       placeholder="e.g. Prof. Alan Turing" required>
            </div>

            <div class="form-group">
                <label for="slots">Available Slots *</label>
                <input type="number" id="slots" name="slots" min="0" class="form-control" 
                       value="<?php echo htmlspecialchars($editClass['slots'] ?? ($_POST['slots'] ?? '15')); ?>" required>
                <small style="color: var(--text-muted);">Slots represent remaining enrollment capacity.</small>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editClass ? 'Update Class' : 'Save Class Section'; ?>
                </button>
                <?php if ($editClass): ?>
                    <a href="admin/classes.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Classes List -->
    <div class="card">
        <div class="card-header">
            <h3>Scheduled Classes (<?php echo count($classes); ?>)</h3>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class & Course</th>
                        <th>Schedule / Instructor</th>
                        <th>Slots</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No class sections scheduled.</td></tr>
                    <?php else: ?>
                        <?php foreach ($classes as $cl): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($cl['class_code']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($cl['course_name']); ?></small>
                                </td>
                                <td>
                                    <div>🕒 <?php echo htmlspecialchars($cl['schedule']); ?></div>
                                    <small style="color: var(--text-muted);">👨‍🏫 <?php echo htmlspecialchars($cl['instructor']); ?></small>
                                </td>
                                <td>
                                    <?php if ($cl['slots'] > 0): ?>
                                        <span class="badge badge-slot slot-available"><?php echo (int)$cl['slots']; ?> left</span>
                                    <?php else: ?>
                                        <span class="badge badge-slot slot-zero">0 Full</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="admin/classes.php?edit=<?php echo (int)$cl['class_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="admin/classes.php?action=delete&id=<?php echo (int)$cl['class_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete class <?php echo htmlspecialchars($cl['class_code']); ?>?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

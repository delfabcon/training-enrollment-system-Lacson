<?php
// admin/courses.php -- Manage courses (CRUD)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Course.php';

$courseModel = new Course($db);
$message = '';
$messageType = '';

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        if ($courseModel->delete($id)) {
            $message = "Course successfully deleted.";
            $messageType = "success";
        } else {
            $message = "Unable to delete course.";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $message = "Cannot delete course: related class records exist.";
        $messageType = "danger";
    }
}

// Handle Add / Edit POST
$editCourse = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $course_code = trim($_POST['course_code'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Server-side validation
    if (empty($course_code) || empty($course_name)) {
        $message = "Course Code and Course Name are required fields.";
        $messageType = "danger";
        if ($course_id > 0) {
            $editCourse = $courseModel->find($course_id);
        }
    } else {
        try {
            if ($course_id > 0) {
                // Update
                $courseModel->update($course_id, $course_code, $course_name, $description);
                $message = "Course #{$course_code} updated successfully!";
                $messageType = "success";
            } else {
                // Check duplicate code
                $existing = $courseModel->findByCode($course_code);
                if ($existing) {
                    $message = "A course with code '{$course_code}' already exists.";
                    $messageType = "danger";
                } else {
                    $courseModel->create($course_code, $course_name, $description);
                    $message = "Course '{$course_name}' created successfully!";
                    $messageType = "success";
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Load course for editing if requested via GET
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editCourse = $courseModel->find($editId);
}

// Search and filter courses
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE course_code LIKE :s OR course_name LIKE :s ORDER BY course_name ASC");
    $stmt->execute([':s' => "%$search%"]);
    $courses = $stmt->fetchAll();
} else {
    $courses = $courseModel->all();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>📚 Course Management</h2>
    <a href="admin/courses.php" class="btn btn-secondary btn-sm">Refresh List</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Course Form (Add / Edit) -->
    <div class="card">
        <h3><?php echo $editCourse ? '✏️ Edit Course' : '➕ Add New Course'; ?></h3>
        <form method="POST" action="admin/courses.php">
            <?php if ($editCourse): ?>
                <input type="hidden" name="course_id" value="<?php echo (int)$editCourse['course_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="course_code">Course Code *</label>
                <input type="text" id="course_code" name="course_code" class="form-control" 
                       value="<?php echo htmlspecialchars($editCourse['course_code'] ?? ($_POST['course_code'] ?? '')); ?>" 
                       placeholder="e.g. WD101" required>
            </div>

            <div class="form-group">
                <label for="course_name">Course Name *</label>
                <input type="text" id="course_name" name="course_name" class="form-control" 
                       value="<?php echo htmlspecialchars($editCourse['course_name'] ?? ($_POST['course_name'] ?? '')); ?>" 
                       placeholder="e.g. Web Development Fundamentals" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" 
                          placeholder="Short course description or syllabus summary..."><?php echo htmlspecialchars($editCourse['description'] ?? ($_POST['description'] ?? '')); ?></textarea>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <?php echo $editCourse ? 'Update Course' : 'Save Course'; ?>
                </button>
                <?php if ($editCourse): ?>
                    <a href="admin/courses.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Filter & List of Courses -->
    <div class="card">
        <div class="card-header">
            <h3>All Registered Courses (<?php echo count($courses); ?>)</h3>
            <form method="GET" action="admin/courses.php" class="search-form">
                <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 0.35rem 0.6rem; font-size: 0.85rem;">
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="admin/courses.php" class="btn btn-sm btn-secondary" title="Clear search">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">No courses found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['course_code']); ?></strong></td>
                                <td>
                                    <div><?php echo htmlspecialchars($c['course_name']); ?></div>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($c['description'] ?? ''); ?></small>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="admin/courses.php?edit=<?php echo (int)$c['course_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="admin/courses.php?action=delete&id=<?php echo (int)$c['course_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete course <?php echo htmlspecialchars($c['course_code']); ?>?');">Delete</a>
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

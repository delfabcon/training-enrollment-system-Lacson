<?php
// admin/enrollments.php -- List enrollments (JOINs) and cancel enrollments
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);

$message = '';
$messageType = '';

// Handle Cancellation action
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $enrollment_id = (int)$_GET['id'];
    if ($enrollment_id > 0) {
        $cancelled = $repo->cancel($enrollment_id);
        if ($cancelled) {
            $message = "Enrollment #{$enrollment_id} has been successfully cancelled, and 1 class slot was atomically restored!";
            $messageType = "success";
        } else {
            $message = "Unable to cancel enrollment #{$enrollment_id}. It may already be cancelled or does not exist.";
            $messageType = "warning";
        }
    }
}

// Search and filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');

$rows = $repo->allWithDetails($statusFilter, $searchQuery);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>📋 Enrollment Records Roster</h2>
    <div style="display: flex; gap: 0.5rem;">
        <a href="admin/students.php" class="btn btn-primary btn-sm">➕ Record New Student</a>
        <a href="admin/enroll.php" class="btn btn-secondary btn-sm">⚡ Enroll Existing</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="actions-bar">
        <!-- Filter tabs -->
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <span style="font-weight: 600; font-size: 0.9rem; color: var(--secondary);">Filter Status:</span>
            <a href="admin/enrollments.php?search=<?php echo urlencode($searchQuery); ?>" 
               class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="admin/enrollments.php?status=active&search=<?php echo urlencode($searchQuery); ?>" 
               class="btn btn-sm <?php echo ($statusFilter === 'active') ? 'btn-primary' : 'btn-secondary'; ?>">Active</a>
            <a href="admin/enrollments.php?status=cancelled&search=<?php echo urlencode($searchQuery); ?>" 
               class="btn btn-sm <?php echo ($statusFilter === 'cancelled') ? 'btn-primary' : 'btn-secondary'; ?>">Cancelled</a>
        </div>

        <!-- Search box -->
        <form method="GET" action="admin/enrollments.php" class="search-form">
            <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <?php endif; ?>
            <input type="text" name="search" class="form-control" placeholder="Search student, course, class..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>" style="padding: 0.35rem 0.6rem; font-size: 0.85rem; width: 220px;">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="admin/enrollments.php<?php echo !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : ''; ?>" 
                   class="btn btn-sm btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th># ID</th>
                    <th>Student Name & Contact</th>
                    <th>Course</th>
                    <th>Class Section / Schedule</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No enrollment records found matching the criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><strong>#<?php echo (int)$row['enrollment_id']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                <small style="color: var(--text-muted);">
                                    ✉️ <?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?> | 
                                    📞 <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge" style="background: #e2e8f0; color: #334155;">
                                    <?php echo htmlspecialchars($row['course_code']); ?>
                                </span><br>
                                <?php echo htmlspecialchars($row['course_name']); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['class_code']); ?></strong><br>
                                <small style="color: var(--text-muted);">
                                    🕒 <?php echo htmlspecialchars($row['schedule']); ?><br>
                                    👨‍🏫 <?php echo htmlspecialchars($row['instructor']); ?>
                                </small>
                            </td>
                            <td style="white-space: nowrap; font-size: 0.9rem;">
                                <?php echo date('M d, Y h:i A', strtotime($row['enrollment_date'])); ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'active'): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php if ($row['status'] === 'active'): ?>
                                    <a href="admin/enrollments.php?action=cancel&id=<?php echo (int)$row['enrollment_id']; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to cancel enrollment #<?php echo (int)$row['enrollment_id']; ?> for <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>? This will restore 1 slot to class <?php echo htmlspecialchars(addslashes($row['class_code'])); ?>.');">
                                       Cancel Enrollment
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

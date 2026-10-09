<?php
// index.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Fetch summary metrics for quick dashboard view
$totalCourses = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalClasses = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeEnrollments = $db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
?>

<div style="margin-bottom: 2rem;">
    <h1>Training Enrollment System</h1>
    <p style="color: var(--text-muted); font-size: 1.1rem; margin-top: 0.5rem;">
        Welcome to the Administrator Portal. Manage training catalogs, class schedules, multi-table student enrollments, and capacity tracking with atomic database transactions.
    </p>
</div>

<!-- Dashboard Stats -->
<div class="grid grid-4" style="margin-bottom: 2.5rem;">
    <div class="stat-box">
        <div class="stat-number"><?php echo htmlspecialchars($totalCourses); ?></div>
        <div class="stat-label">Available Courses</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo htmlspecialchars($totalClasses); ?></div>
        <div class="stat-label">Scheduled Classes</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo htmlspecialchars($totalStudents); ?></div>
        <div class="stat-label">Registered Students</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo htmlspecialchars($activeEnrollments); ?></div>
        <div class="stat-label">Active Enrollments</div>
    </div>
</div>

<!-- Quick Navigation Cards -->
<div class="grid grid-3">
    <div class="card">
        <h3>📚 Course Catalog</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            Manage training offerings, course codes, titles, and syllabus descriptions.
        </p>
        <a href="admin/courses.php" class="btn btn-primary btn-sm">Manage Courses &rarr;</a>
    </div>

    <div class="card">
        <h3>🗓️ Class Sections & Slots</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            Configure class schedules, assign instructors, and allocate available seat capacities.
        </p>
        <a href="admin/classes.php" class="btn btn-primary btn-sm">Manage Classes &rarr;</a>
    </div>

    <div class="card">
        <h3>📝 Record & Enroll Student</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            Record a new student and enroll them in a class section in a single atomic transaction.
        </p>
        <a href="admin/students.php" class="btn btn-primary btn-sm">Record Student &rarr;</a>
    </div>

    <div class="card">
        <h3>⚡ Enroll Existing Student</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            Quickly enroll an already registered student into an open class section.
        </p>
        <a href="admin/enroll.php" class="btn btn-secondary btn-sm">Quick Enroll &rarr;</a>
    </div>

    <div class="card">
        <h3>📋 All Enrollments</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            View full enrollment roster with multi-table joins and manage cancellations.
        </p>
        <a href="admin/enrollments.php" class="btn btn-secondary btn-sm">View Enrollments &rarr;</a>
    </div>

    <div class="card">
        <h3>📊 Summary Reports</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.25rem;">
            Review aggregate statistics, class capacity utilization, and course popularity.
        </p>
        <a href="admin/reports.php" class="btn btn-secondary btn-sm">View Reports &rarr;</a>
    </div>
</div>

<div class="card" style="margin-top: 1rem; background-color: var(--primary-light); border-color: #bfdbfe;">
    <h3 style="color: var(--primary); margin-bottom: 0.5rem;">🔒 Transaction Integrity & Architecture Notes</h3>
    <ul style="padding-left: 1.5rem; color: #1e3a8a; font-size: 0.95rem;">
        <li><strong>Singleton Pattern:</strong> <code>Database</code> class ensures only one PDO connection is created and shared across the lifecycle.</li>
        <li><strong>Repository Pattern:</strong> <code>EnrollmentRepository</code> encapsulates multi-table operations with ACID guarantees.</li>
        <li><strong>Atomic Registration:</strong> Student insertion, enrollment record creation, and slot decrements execute inside a single transaction or roll back completely on exhaustion.</li>
        <li><strong>Safe Cancellation:</strong> Enrollment cancellation updates status and safely restores class seat capacity within a transaction.</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

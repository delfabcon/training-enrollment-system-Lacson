<?php
// admin/reports.php -- Summary reports and aggregate metrics
require_once __DIR__ . '/../config/db.php';

// Aggregate KPIs
$totalCourses = $db->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalClasses = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeEnrollments = $db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'active'")->fetchColumn();
$cancelledEnrollments = $db->query("SELECT COUNT(*) FROM enrollments WHERE status = 'cancelled'")->fetchColumn();
$totalRemainingSlots = $db->query("SELECT COALESCE(SUM(slots), 0) FROM classes")->fetchColumn();

// Report 1: Class Capacity & Occupancy Breakdown
$sqlCapacity = "
    SELECT 
        c.class_id,
        c.class_code,
        c.schedule,
        c.instructor,
        c.slots AS remaining_slots,
        co.course_code,
        co.course_name,
        COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS active_enrolled,
        COUNT(CASE WHEN e.status = 'cancelled' THEN 1 END) AS cancelled_count
    FROM classes c
    JOIN courses co ON c.course_id = co.course_id
    LEFT JOIN enrollments e ON c.class_id = e.class_id
    GROUP BY c.class_id, c.class_code, c.schedule, c.instructor, c.slots, co.course_code, co.course_name
    ORDER BY co.course_name ASC, c.class_code ASC
";
$capacityReport = $db->query($sqlCapacity)->fetchAll();

// Report 2: Course Popularity Breakdown
$sqlCourses = "
    SELECT 
        co.course_id,
        co.course_code,
        co.course_name,
        COUNT(DISTINCT c.class_id) AS sections_count,
        COUNT(CASE WHEN e.status = 'active' THEN 1 END) AS total_enrolled
    FROM courses co
    LEFT JOIN classes c ON co.course_id = c.course_id
    LEFT JOIN enrollments e ON c.class_id = e.class_id
    GROUP BY co.course_id, co.course_code, co.course_name
    ORDER BY total_enrolled DESC, co.course_name ASC
";
$coursePopularity = $db->query($sqlCourses)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card-header">
    <h2>📊 Summative Reports & Capacity Metrics</h2>
    <a href="admin/reports.php" class="btn btn-secondary btn-sm">Refresh Data</a>
</div>

<!-- KPI Cards -->
<div class="grid grid-3" style="margin-bottom: 2rem;">
    <div class="stat-box">
        <div class="stat-number"><?php echo (int)$totalStudents; ?></div>
        <div class="stat-label">Total Registered Students</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: var(--success);"><?php echo (int)$activeEnrollments; ?></div>
        <div class="stat-label">Active Enrollments</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: var(--danger);"><?php echo (int)$cancelledEnrollments; ?></div>
        <div class="stat-label">Cancelled Enrollments</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo (int)$totalCourses; ?></div>
        <div class="stat-label">Course Programs</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo (int)$totalClasses; ?></div>
        <div class="stat-label">Class Sections Offered</div>
    </div>
    <div class="stat-box">
        <div class="stat-number" style="color: var(--info);"><?php echo (int)$totalRemainingSlots; ?></div>
        <div class="stat-label">Total Open Seat Slots</div>
    </div>
</div>

<!-- Class Capacity Breakdown Table -->
<div class="card">
    <h3>Class Section Capacity & Utilization Report</h3>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
        Detailed view generated using <code>LEFT JOIN</code> and aggregate summations showing active vs. cancelled enrollments alongside remaining seat capacity.
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Course Program</th>
                    <th>Instructor & Schedule</th>
                    <th>Active Enrolled</th>
                    <th>Cancelled</th>
                    <th>Available Slots</th>
                    <th>Total Capacity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($capacityReport as $row): 
                    $totalCapacity = $row['active_enrolled'] + $row['remaining_slots'];
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['class_code']); ?></strong></td>
                        <td>
                            <span class="badge" style="background:#e2e8f0; color:#334155;"><?php echo htmlspecialchars($row['course_code']); ?></span>
                            <?php echo htmlspecialchars($row['course_name']); ?>
                        </td>
                        <td>
                            <div>👨‍🏫 <?php echo htmlspecialchars($row['instructor']); ?></div>
                            <small style="color: var(--text-muted);">🕒 <?php echo htmlspecialchars($row['schedule']); ?></small>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: var(--success);"><?php echo (int)$row['active_enrolled']; ?></span>
                        </td>
                        <td>
                            <span style="color: var(--danger);"><?php echo (int)$row['cancelled_count']; ?></span>
                        </td>
                        <td>
                            <?php if ($row['remaining_slots'] > 0): ?>
                                <span class="badge badge-slot slot-available"><?php echo (int)$row['remaining_slots']; ?> slots</span>
                            <?php else: ?>
                                <span class="badge badge-slot slot-zero">0 Full</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo (int)$totalCapacity; ?> seats</strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Course Popularity Table -->
<div class="card">
    <h3>Course Enrollment Popularity</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Offered Sections</th>
                    <th>Active Students Enrolled</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coursePopularity as $cp): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cp['course_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($cp['course_name']); ?></td>
                        <td><?php echo (int)$cp['sections_count']; ?> section(s)</td>
                        <td>
                            <strong><?php echo (int)$cp['total_enrolled']; ?> student(s)</strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

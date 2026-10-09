<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Enrollment System</title>
    <base href="/training_enrollment/">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <div class="nav-container">
        <a href="index.php" class="brand">
            🎓 <span>Training Enrollment System</span>
        </a>
        <nav>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a>
            <a href="admin/courses.php" class="<?php echo ($current_page == 'courses.php') ? 'active' : ''; ?>">Courses</a>
            <a href="admin/classes.php" class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>">Classes</a>
            <a href="admin/students.php" class="<?php echo ($current_page == 'students.php') ? 'active' : ''; ?>">Record Student</a>
            <a href="admin/enroll.php" class="<?php echo ($current_page == 'enroll.php') ? 'active' : ''; ?>">Enroll</a>
            <a href="admin/enrollments.php" class="<?php echo ($current_page == 'enrollments.php') ? 'active' : ''; ?>">Enrollments</a>
            <a href="admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">Reports</a>
        </nav>
    </div>
</header>
<main>

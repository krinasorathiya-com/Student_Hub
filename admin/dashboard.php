
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../config/db.php";

// Admin-only access
if (
    !isset($_SESSION['admin_id']) ||
    ($_SESSION['admin_role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'];

// Dashboard statistics
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];

$totalEvents = $conn->query("SELECT COUNT(*) AS total FROM events")->fetch_assoc()['total'];

$totalRegistrations = $conn->query("SELECT COUNT(*) AS total FROM registrations")->fetch_assoc()['total'];

// Latest registrations
$latest = $conn->query("
    SELECT r.id, s.name AS student_name,
           e.title AS event_title, r.registration_date
    FROM registrations r
    LEFT JOIN students s ON r.student_id = s.id
    LEFT JOIN events e ON r.event_id = e.id
    ORDER BY r.registration_date DESC
    LIMIT 5
");

// Latest audit logs
$logs = $conn->query("
    SELECT admin_id, action, details, created_at
    FROM audit_logs
    ORDER BY created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Student Hub</title>

<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    margin: 0;
}

.header {
    background: #1e293b;
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.header a {
    color: white;
    background: #dc2626;
    padding: 10px 16px;
    text-decoration: none;
    border-radius: 5px;
}

.container {
    max-width: 1100px;
    margin: 30px auto;
    padding: 0 20px;
}

.menu {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
}

.menu a {
    display: inline-block;
    margin: 5px 15px 5px 0;
    color: #2563eb;
    text-decoration: none;
    font-weight: bold;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 20px;
}

.card, .section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.card h3 {
    color: #475569;
}

.number {
    font-size: 32px;
    font-weight: bold;
    color: #2563eb;
}

.section {
    margin-top: 25px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 12px;
    border: 1px solid #e2e8f0;
    text-align: left;
}

th {
    background: #e2e8f0;
}

@media(max-width: 600px) {
    .header {
        gap: 15px;
    }
}
</style>
</head>

<body>

<div class="header">
    <div>
        <h2>Student Hub Admin</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?></p>
        <small>Role: <?= htmlspecialchars($_SESSION['admin_role']) ?></small>
    </div>

    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <h1>Admin Dashboard</h1>
    <p>Manage and monitor your Student Hub system.</p>

    <!-- Role Based Menu -->
    <div class="menu">

        <?php if ($_SESSION['admin_role'] === 'admin'): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="events.php">Manage Events</a>
            <a href="#registrations">Registrations</a>
            <a href="#audit">Audit Logs</a>
        <?php endif; ?>

    </div>

    <!-- Statistics -->
    <div class="cards">

        <div class="card">
            <h3>Total Users</h3>
            <div class="number"><?= $totalUsers ?></div>
        </div>

        <div class="card">
            <h3>Total Students</h3>
            <div class="number"><?= $totalStudents ?></div>
        </div>

        <div class="card">
            <h3>Total Events</h3>
            <div class="number"><?= $totalEvents ?></div>
        </div>

        <div class="card">
            <h3>Total Registrations</h3>
            <div class="number"><?= $totalRegistrations ?></div>
        </div>

    </div>

    <!-- Latest Registrations -->
    <div class="section" id="registrations">

        <h2>Latest Registrations</h2>

        <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Event Name</th>
                <th>Registration Date</th>
            </tr>

            <?php if ($latest && $latest->num_rows > 0): ?>

                <?php while ($row = $latest->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['student_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['event_title'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['registration_date']) ?></td>
                </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="4">No registrations available.</td>
                </tr>
            <?php endif; ?>

        </table>
        </div>
    </div>

    <!-- Audit Logs -->
    <div class="section" id="audit">

        <h2>Admin Activity / Audit Logs</h2>

        <div class="table-wrap">
        <table>
            <tr>
                <th>Admin ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>Date & Time</th>
            </tr>

            <?php if ($logs && $logs->num_rows > 0): ?>

                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($log['admin_id'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="4">No audit logs available.</td>
                </tr>
            <?php endif; ?>

        </table>
        </div>
    </div>

</div>

</body>
</html>

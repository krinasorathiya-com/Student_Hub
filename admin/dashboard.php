
<?php
session_start();

if (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

// Count records safely
function getCount($conn, $table) {
    $allowed = ['users', 'students', 'events', 'registrations'];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    try {
        $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
        return (int)$result->fetch_assoc()['total'];
    } catch (Throwable $e) {
        return 0;
    }
}

// Fetch latest records without assuming column names
function getRecords($conn, $table) {
    $allowed = ['registrations', 'audit_logs'];

    if (!in_array($table, $allowed, true)) {
        return [];
    }

    try {
        $result = $conn->query("SELECT * FROM `$table` LIMIT 5");
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$totalUsers = getCount($conn, 'users');
$totalStudents = getCount($conn, 'students');
$totalEvents = getCount($conn, 'events');
$totalRegistrations = getCount($conn, 'registrations');

$latestRegistrations = getRecords($conn, 'registrations');
$auditLogs = getRecords($conn, 'audit_logs');

function displayRecords($records) {
    if (empty($records)) {
        echo "<p>No records available.</p>";
        return;
    }

    echo "<div class='table-wrap'><table><thead><tr>";

    foreach (array_keys($records[0]) as $column) {
        if (stripos($column, 'password') !== false) {
            continue;
        }
        echo "<th>" . htmlspecialchars($column) . "</th>";
    }

    echo "</tr></thead><tbody>";

    foreach ($records as $record) {
        echo "<tr>";

        foreach ($record as $column => $value) {
            if (stripos($column, 'password') !== false) {
                continue;
            }

            echo "<td>" . htmlspecialchars((string)($value ?? '')) . "</td>";
        }

        echo "</tr>";
    }

    echo "</tbody></table></div>";
}
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
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
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
    text-decoration: none;
    background: #dc2626;
    padding: 10px 16px;
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

.card {
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
    background: white;
    padding: 22px;
    margin-top: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
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

        <?php displayRecords($latestRegistrations); ?>

    </div>

    <!-- Audit Logs -->
    <div class="section" id="audit">

        <h2>Admin Activity / Audit Logs</h2>

        <?php displayRecords($auditLogs); ?>

    </div>

</div>

</body>
</html>

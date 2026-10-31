
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
$totalEvents = $result->fetch_assoc()['total'];

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM events WHERE status = 'Open'"
);
$openEvents = $result->fetch_assoc()['total'];

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM events WHERE status = 'Closed'"
);
$closedEvents = $result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Student Hub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
        }

        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #2563eb;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            margin-right: 15px;
            color: #2563eb;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</h1>

    <h2>Student Hub Admin Dashboard</h2>

    <div class="cards">

        <div class="card">
            <h3>Total Events</h3>
            <div class="number"><?= $totalEvents ?></div>
        </div>

        <div class="card">
            <h3>Open Events</h3>
            <div class="number"><?= $openEvents ?></div>
        </div>

        <div class="card">
            <h3>Closed Events</h3>
            <div class="number"><?= $closedEvents ?></div>
        </div>

    </div>

    <a href="events.php">Manage Events</a>
    <a href="logout.php">Logout</a>

</div>

</body>
</html>

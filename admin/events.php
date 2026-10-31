
<?php
session_start();

require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all events
$result = $conn->query("SELECT * FROM events ORDER BY id DESC");

if (!$result) {
    die("Error fetching events: " . $conn->error);
}

// Get messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            margin: 0;
            color: #111827;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }

        .dashboard-btn {
            background: #64748b;
        }

        .add-btn {
            background: #2563eb;
        }

        .edit-btn {
            background: #16a34a;
        }

        .delete-btn {
            background: #dc2626;
        }

        .message {
            padding: 13px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #1e293b;
            color: white;
            text-align: left;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
        }

        tr:hover {
            background: #f8fafc;
        }

        .poster {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .action-buttons form {
            margin: 0;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Manage Events</h1>

        <div>
            <a href="dashboard.php" class="btn dashboard-btn">
                Dashboard
            </a>

            <a href="add_event.php" class="btn add-btn">
                + Add New Event
            </a>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Poster</th>
                <th>Title</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Category</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($event = $result->fetch_assoc()): ?>

                <tr>
                    <td>
                        <?= (int)$event['id'] ?>
                    </td>

                    <td>
                        <?php
                        $posterName = basename($event['poster'] ?? '');
                        $posterPath = __DIR__ . "/../uploads/" . $posterName;
                        ?>

                        <?php if (!empty($posterName) && is_file($posterPath)): ?>

                            <img
                                src="../uploads/<?= rawurlencode($posterName) ?>"
                                alt="Event poster"
                                class="poster"
                            >

                        <?php else: ?>

                            No Poster

                        <?php endif; ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($event['title']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($event['event_date']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($event['venue']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($event['category']) ?>
                    </td>

                    <td>
                        <div class="action-buttons">

                            <a
                                href="edit_event.php?id=<?= (int)$event['id'] ?>"
                                class="btn edit-btn">
                                Edit
                            </a>

                            <form
                                action="delete_event.php"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this event?');">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$event['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn delete-btn">
                                    Delete
                                </button>

                            </form>

                        </div>
                    </td>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="7" style="text-align:center;">
                    No events found.
                </td>
            </tr>

        <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>

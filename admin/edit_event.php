
<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "../config/db.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    die("Invalid event ID.");
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Event not found.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = "Invalid request. Please try again.";

    } else {

        $title = trim($_POST['title'] ?? "");
        $description = trim($_POST['description'] ?? "");
        $event_date = $_POST['event_date'] ?? "";
        $venue = trim($_POST['venue'] ?? "");
        $category = trim($_POST['category'] ?? "");
        $status = $_POST['status'] ?? "";

        if (
            $title === "" ||
            $description === "" ||
            $event_date === "" ||
            $venue === "" ||
            $category === ""
        ) {
            $error = "Please fill all required fields.";

        } elseif (
            strlen($title) > 180 ||
            strlen($venue) > 180 ||
            strlen($category) > 60
        ) {
            $error = "Field length exceeds the allowed limit.";

        } elseif (!in_array($status, ["Open", "Closed"], true)) {
            $error = "Invalid event status.";

        } else {

            $dateObject = DateTime::createFromFormat('Y-m-d', $event_date);

            if (
                !$dateObject ||
                $dateObject->format('Y-m-d') !== $event_date
            ) {
                $error = "Please enter a valid date.";
            }
        }

        $newPoster = $event['poster'];
        $uploadedPath = null;

        // Poster upload is optional during editing
        if (
            $error === "" &&
            isset($_FILES['poster']) &&
            $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
                $error = "Poster upload failed.";

            } elseif ($_FILES['poster']['size'] > 2 * 1024 * 1024) {
                $error = "Poster must not exceed 2 MB.";

            } else {

                $tmpPath = $_FILES['poster']['tmp_name'];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpPath);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (
                    !isset($allowed[$mime]) ||
                    getimagesize($tmpPath) === false
                ) {
                    $error = "Only valid JPG, PNG and WEBP images are allowed.";

                } else {

                    $uploadDir = dirname(__DIR__) . "/uploads/";

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $newPoster = bin2hex(random_bytes(16))
                        . "." . $allowed[$mime];

                    $uploadedPath = $uploadDir . $newPoster;

                    if (!move_uploaded_file($tmpPath, $uploadedPath)) {
                        $error = "Could not save the poster.";
                        $uploadedPath = null;
                    }
                }
            }
        }

        if ($error === "") {

            $update = $conn->prepare(
                "UPDATE events
                 SET title = ?, description = ?, event_date = ?,
                     venue = ?, category = ?, status = ?, poster = ?
                 WHERE id = ?"
            );

            $update->bind_param(
                "sssssssi",
                $title,
                $description,
                $event_date,
                $venue,
                $category,
                $status,
                $newPoster,
                $id
            );

            if ($update->execute()) {

                // Delete old poster only after successful update
                if (
                    $uploadedPath !== null &&
                    !empty($event['poster'])
                ) {
                    $oldPath = dirname(__DIR__)
                        . "/uploads/" . basename($event['poster']);

                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: events.php?success="
                    . urlencode("Event updated successfully."));
                exit();

            } else {

                if ($uploadedPath && is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }

                $error = "Could not update event.";
            }

            $update->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Student Hub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 25px;
        }

        .form-box {
            max-width: 650px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        input, textarea, select {
            width: 100%;
            padding: 11px;
            margin: 8px 0 17px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 100px;
        }

        button {
            background: #2563eb;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
            padding: 12px;
        }

        img {
            width: 130px;
            display: block;
            margin: 10px 0;
        }
    </style>
</head>

<body>

<div class="form-box">

    <a href="events.php">← Back to Events</a>

    <h2>Edit Event</h2>

    <?php if ($error !== ""): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
        >

        <label>Event Title</label>
        <input
            type="text"
            name="title"
            maxlength="180"
            value="<?= htmlspecialchars($event['title']) ?>"
            required
        >

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($event['description']) ?></textarea>

        <label>Event Date</label>
        <input
            type="date"
            name="event_date"
            value="<?= htmlspecialchars($event['event_date']) ?>"
            required
        >

        <label>Venue</label>
        <input
            type="text"
            name="venue"
            maxlength="180"
            value="<?= htmlspecialchars($event['venue']) ?>"
            required
        >

        <label>Category</label>
        <input
            type="text"
            name="category"
            maxlength="60"
            value="<?= htmlspecialchars($event['category']) ?>"
            required
        >

        <label>Status</label>
        <select name="status" required>
            <option value="Open" <?= $event['status'] === 'Open' ? 'selected' : '' ?>>
                Open
            </option>
            <option value="Closed" <?= $event['status'] === 'Closed' ? 'selected' : '' ?>>
                Closed
            </option>
        </select>

        <label>Current Poster</label>

        <?php
        $currentPosterPath = dirname(__DIR__)
            . "/uploads/" . basename($event['poster'] ?? "");
        ?>

        <?php if (
            !empty($event['poster']) &&
            is_file($currentPosterPath)
        ): ?>
            <img
                src="../uploads/<?= rawurlencode(basename($event['poster'])) ?>"
                alt="Current poster"
            >
        <?php else: ?>
            <p>Poster Not Available</p>
        <?php endif; ?>

        <label>Upload New Poster (Optional, Max 2 MB)</label>
        <input
            type="file"
            name="poster"
            accept="image/jpeg,image/png,image/webp"
        >

        <button type="submit">Update Event</button>

    </form>

</div>

</body>
</html>

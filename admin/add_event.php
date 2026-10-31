
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
        $status = $_POST['status'] ?? "Open";

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
            $error = "One or more fields exceed the allowed length.";

        } elseif (
            !in_array($status, ["Open", "Closed"], true)
        ) {
            $error = "Invalid event status.";

        } else {

            $dateObject = DateTime::createFromFormat('Y-m-d', $event_date);

            if (
                !$dateObject ||
                $dateObject->format('Y-m-d') !== $event_date
            ) {
                $error = "Please enter a valid event date.";
            } else {

                $posterName = null;
                $uploadedPath = null;

                if (
                    isset($_FILES['poster']) &&
                    $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
                        $error = "Poster upload failed.";

                    } elseif ($_FILES['poster']['size'] > 2 * 1024 * 1024) {
                        $error = "Poster size must not exceed 2 MB.";

                    } else {

                        $tmpPath = $_FILES['poster']['tmp_name'];

                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmpPath);

                        $allowedTypes = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp'
                        ];

                        if (!isset($allowedTypes[$mime])) {
                            $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

                        } elseif (getimagesize($tmpPath) === false) {
                            $error = "Uploaded file is not a valid image.";

                        } else {

                            $posterName = bin2hex(random_bytes(16))
                                . "." . $allowedTypes[$mime];

                            $uploadDir = dirname(__DIR__) . "/uploads/";

                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $uploadedPath = $uploadDir . $posterName;

                            if (!move_uploaded_file($tmpPath, $uploadedPath)) {
                                $error = "Could not save the poster.";
                                $posterName = null;
                                $uploadedPath = null;
                            }
                        }
                    }
                }

                if ($error === "") {

                    $stmt = $conn->prepare(
                        "INSERT INTO events
                        (title, description, event_date, venue, category, status, poster)
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "sssssss",
                        $title,
                        $description,
                        $event_date,
                        $venue,
                        $category,
                        $status,
                        $posterName
                    );

                    if ($stmt->execute()) {

                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        header("Location: events.php?success="
                            . urlencode("Event added successfully."));
                        exit();

                    } else {

                        if ($uploadedPath && file_exists($uploadedPath)) {
                            unlink($uploadedPath);
                        }

                        $error = "Database error: Could not add event.";
                    }

                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - Student Hub</title>

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
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
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

        .back {
            display: inline-block;
            margin-bottom: 15px;
        }

        .error {
            color: #991b1b;
            background: #fee2e2;
            padding: 12px;
        }
    </style>
</head>

<body>

<div class="form-box">

    <a class="back" href="events.php">← Back to Events</a>

    <h2>Add New Event</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
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
            required
        >

        <label>Description</label>
        <textarea
            name="description"
            required
        ></textarea>

        <label>Event Date</label>
        <input
            type="date"
            name="event_date"
            required
        >

        <label>Venue</label>
        <input
            type="text"
            name="venue"
            maxlength="180"
            required
        >

        <label>Category</label>
        <select name="category" required>
            <option value="">Select Category</option>
            <option value="Workshop">Workshop</option>
            <option value="Technical">Technical</option>
            <option value="Cultural">Cultural</option>
            <option value="Sports">Sports</option>
            <option value="Seminar">Seminar</option>
            <option value="Competition">Competition</option>
        </select>

        <label>Event Status</label>
        <select name="status" required>
            <option value="Open">Open</option>
            <option value="Closed">Closed</option>
        </select>

        <label>Event Poster (JPG, PNG, WEBP — Maximum 2 MB)</label>
        <input
            type="file"
            name="poster"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        >

        <button type="submit">Add Event</button>

    </form>

</div>

</body>
</html>


<?php
session_start();

require_once "../config/db.php";

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.php");
    exit();
}

// Verify CSRF token
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header("Location: events.php?error=Invalid request");
    exit();
}

// Get event ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: events.php?error=Invalid event ID");
    exit();
}

// Get poster filename before deleting event
$stmt = $conn->prepare("SELECT poster FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    header("Location: events.php?error=Event not found");
    exit();
}

// Delete event from database
$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    // Delete poster file if it exists
    if (!empty($event['poster'])) {
        $posterName = basename($event['poster']);
        $posterPath = __DIR__ . "/../uploads/" . $posterName;

        if (is_file($posterPath)) {
            unlink($posterPath);
        }
    }

    $stmt->close();

    header("Location: events.php?success=Event deleted successfully");
    exit();

} else {
    $stmt->close();

    header("Location: events.php?error=Unable to delete event");
    exit();
}
?>

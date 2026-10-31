
<?php
session_start();

require_once "../config/db.php";

if (isset($_SESSION['admin_id'])) {

    $adminId = $_SESSION['admin_id'];
    $action = "Admin Logout";
    $details = "Admin logged out successfully.";

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (admin_id, action, details)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("iss", $adminId, $action, $details);
    $stmt->execute();
    $stmt->close();
}

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit();
?>

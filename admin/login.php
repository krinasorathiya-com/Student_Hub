<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../config/db.php";

if (isset($_SESSION['admin_id']) &&
    ($_SESSION['admin_role'] ?? '') === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role, status
             FROM admins WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            if (
                password_verify($password, $admin['password']) &&
                strtolower($admin['role']) === 'admin' &&
                $admin['status'] === 'Active'
            ) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = 'admin';

                // Save login activity
                $action = "Admin Login";
                $details = "Admin " . $admin['name'] . " logged in successfully.";

                $log = $conn->prepare(
                    "INSERT INTO audit_logs (admin_id, action, details)
                     VALUES (?, ?, ?)"
                );

                $log->bind_param(
                    "iss",
                    $admin['id'],
                    $action,
                    $details
                );

                $log->execute();
                $log->close();

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid credentials or inactive admin account.";
            }

        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Student Hub</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-box {
            background: white;
            width: 380px;
            max-width: 92%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #1e293b;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            color: #b91c1c;
            background: #fee2e2;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h2>Student Hub Admin</h2>
    <p style="text-align:center;">Login to your admin account</p>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <input type="email" name="email"
               placeholder="Admin Email" required>

        <input type="password" name="password"
               placeholder="Password" required>

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>
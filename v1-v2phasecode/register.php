<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'user';

    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $role])) {
        $_SESSION['success'] = "Registration successful. Please login.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Global Styles */
        body {
            background-color: #121212;
            font-family: 'Roboto', sans-serif;
            color: #E4E4E4;
            margin: 0;
            padding: 0;
        }

        h1, h2 {
            color: #1DB954;
            font-weight: 600;
        }

        a {
            color: #1DB954;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            font-size: 16px;
        }
        .error {
            background-color: #FF1744;
            color: white;
        }
        .success {
            background-color: #00C853;
            color: white;
        }

        /* Header */
        header {
            background-color: #181818;
            padding: 20px;
            text-align: center;
        }

        /* Navbar */
        .navbar {
            background-color: #282828;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: #1DB954;
            margin: 0 10px;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        /* Event List */
        .event-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .event-card {
            background-color: #282828;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .event-card:hover {
            transform: scale(1.05);
        }

        .event-card h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .event-card p {
            font-size: 14px;
            margin: 5px 0;
        }

        .event-card a {
            color: #1DB954;
            font-size: 16px;
        }

        /* Form Styles */
        .form-container {
            background-color: #181818;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            margin: 20px auto;
        }

        .form-input {
            background-color: #222;
            border: 1px solid #444;
            padding: 15px;
            margin-bottom: 15px;
            width: 100%;
            color: #E4E4E4;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-button {
            padding: 10px 25px;
            background-color: #1DB954;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-button:hover {
            background-color: #1ED760;
        }

        /* User Greeting */
        .user-greeting {
            margin-top: 20px;
            text-align: center;
            font-size: 16px;
        }

        /* Footer */
        footer {
            background-color: #181818;
            padding: 20px;
            text-align: center;
            color: #888;
        }

    </style>
</head>
<body>
<!-- Navbar -->
<div class="navbar">
    <div>
        <a href="index.php">Home</a>
    </div>
    <div>
        <a href="login.php">Login</a>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<!-- Register Form -->
<div class="form-container">
    <h2>Register</h2>
    <form method="POST" action="register.php">
        <input type="text" name="username" placeholder="Username" required class="form-input">
        <input type="password" name="password" placeholder="Password" required class="form-input">
        <select name="role" class="form-input">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <button type="submit" class="form-button">
            <i class="fa fa-user-plus"></i> Register
        </button>
    </form>
</div>

<!-- Footer -->
<footer style="background-color: #2C3E50; color: #ECF0F1; padding: 20px 0; text-align: center; font-family: Arial, sans-serif;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <p style="font-size: 16px; margin-bottom: 10px;">&copy; 2025 Event Management. All rights reserved.</p>
        <div style="margin-top: 10px;">
            <a href="index.html" style="color: #ECF0F1; text-decoration: none; margin: 0 15px; font-size: 14px; transition: color 0.3s ease;" onmouseover="this.style.color='#3498DB'" onmouseout="this.style.color='#ECF0F1'">Back to Home</a>
            <a href="index2.html" style="color: #ECF0F1; text-decoration: none; margin: 0 15px; font-size: 14px; transition: color 0.3s ease;" onmouseover="this.style.color='#3498DB'" onmouseout="this.style.color='#ECF0F1'">Privacy Policy</a>
        </div>
    </div>
</footer>
</body>
</html>

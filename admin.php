<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all events for admin
$events = $db->query("SELECT * FROM events ORDER BY date ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
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

             /* Admin Panel Styles */
         .event-container {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            font-size: 20px;
            margin-bottom: 10px;
            color: #1DB954;
        }

        .event-card p {
            font-size: 16px;
            margin: 5px 0;
            color: #E4E4E4;
        }

        .event-card a {
            color: #1DB954;
            font-size: 16px;
            text-decoration: none;
        }

        .event-card a:hover {
            text-decoration: underline;
        }


    </style>
</head>
<body>
<!-- Navbar -->
<div class="navbar">
    <div>
        <a href="index.php">Home</a>
        <a href="admin.php">Admin Panel</a>
    </div>
    <div>
        <a href="auth.php?action=logout">Logout</a>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="message error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="message success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<!-- Admin Content -->
<h1>Admin Panel</h1>
<div class="event-container">
    <?php foreach ($events as $event): ?>
        <div class="event-card">
            <h2><?= htmlspecialchars($event['name']) ?></h2>
            <p>📅 Date: <?= htmlspecialchars($event['date']) ?></p>
            <p>📍 Location: <?= htmlspecialchars($event['location']) ?></p>
            <p>
                <a href="edit_event.php?id=<?= $event['id'] ?>">Edit</a> |
                <a href="delete_event.php?id=<?= $event['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<!-- Footer -->
<footer>
    <p>&copy; 2025 Event Management. All rights reserved.</p>
    <a href="index.html">Back to Home</a>
</footer>
</body>
</html>
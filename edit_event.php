<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$event_id = $_GET['id'];
$event = $db->query("SELECT * FROM events WHERE id = $event_id")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $location = $_POST['location'];

    $stmt = $db->prepare("UPDATE events SET name = ?, date = ?, location = ? WHERE id = ?");
    if ($stmt->execute([$name, $date, $location, $event_id])) {
        $_SESSION['success'] = "Event updated successfully.";
        header("Location: admin.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update event.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Global Styles */


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

<!-- Edit Event Form -->
<div class="form-container">
    <h2>Edit Event</h2>
    <form method="POST" action="edit_event.php?id=<?= $event_id ?>">
        <input type="text" name="name" placeholder="Event Name" value="<?= htmlspecialchars($event['name']) ?>" required class="form-input">
        <input type="date" name="date" value="<?= htmlspecialchars($event['date']) ?>" required class="form-input">
        <input type="text" name="location" placeholder="Location" value="<?= htmlspecialchars($event['location']) ?>" required class="form-input">
        <button type="submit" class="form-button">Update Event</button>
    </form>
</div>

<!-- Footer -->
<footer>
    <p>&copy; 2025 Event Management. All rights reserved.</p>
    <a href="index.html">Back to Home</a>
</footer>
</body>
</html>
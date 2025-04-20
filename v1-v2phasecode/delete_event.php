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
$stmt = $db->prepare("DELETE FROM events WHERE id = ?");
if ($stmt->execute([$event_id])) {
    $_SESSION['success'] = "Event deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete event.";
}

header("Location: admin.php");
exit();
?>
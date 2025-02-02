<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['event_id'])) {
    header("Location: index.php");
    exit();
}

$event_id = $_GET['event_id'];
$user_id = $_SESSION['user_id'];

// Check if the user has already RSVP'd
$stmt = $db->prepare("SELECT * FROM rsvps WHERE user_id = ? AND event_id = ?");
$stmt->execute([$user_id, $event_id]);

if ($stmt->fetch()) {
    $_SESSION['error'] = "You have already RSVP'd for this event.";
} else {
    // Add RSVP
    $stmt = $db->prepare("INSERT INTO rsvps (user_id, event_id) VALUES (?, ?)");
    if ($stmt->execute([$user_id, $event_id])) {
        $_SESSION['success'] = "You have successfully RSVP'd for this event.";
    } else {
        $_SESSION['error'] = "Failed to RSVP for this event.";
    }
}

header("Location: index.php");
exit();
?>
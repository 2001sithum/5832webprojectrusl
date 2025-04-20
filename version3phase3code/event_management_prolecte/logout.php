<?php
require_once 'includes/functions.php'; // Includes db indirectly if needed by functions, but simplified logic

// Simple Logout: Call the logout function
logoutUser();

// Provide feedback and redirect
if (session_status() == PHP_SESSION_NONE) { session_start(); } // Ensure session for message
$_SESSION['message'] = ['type' => 'success', 'text' => 'You have been logged out.'];
redirect('login.php'); // Redirect to login page
?>

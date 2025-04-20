<?php
// Process headers first
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Only run this script if in development environment
if ($_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != '127.0.0.1') {
    header('HTTP/1.1 403 Forbidden');
    die('This script can only be run in a local development environment.');
}

// Sample event data with local image paths
$sampleEvents = [
    [
        'name' => 'Tech Conference 2023',
        'date' => '2023-11-15',
        'time' => '09:00:00',
        'location' => 'Convention Center',
        'description' => 'Annual technology conference featuring the latest innovations.',
        'image_url' => 'images/image1.jpg', // Updated to local path
        'category' => 'Technology',
        'status' => 'Upcoming',
        'capacity' => 500,
        'phone' => '+1234567890'
    ],
    [
        'name' => 'Music Festival',
        'date' => '2023-08-20',
        'time' => '12:00:00',
        'location' => 'Central Park',
        'description' => 'Outdoor music festival with multiple stages and food vendors.',
        'image_url' => 'images/image2.jpg', // Updated to local path
        'category' => 'Music',
        'status' => 'Ongoing',
        'capacity' => 2000,
        'phone' => '+1234567891'
    ],
    // Add more events as needed...
];

// Database operations
try {
    // Check if events already exist
    $stmt = $db->query("SELECT COUNT(*) FROM events");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        header('HTTP/1.1 200 OK');
        die("Database already contains events. Not adding sample data.");
    }

    // Insert sample events
    $sql = "INSERT INTO events (name, date, time, location, description, image_url, category, status, capacity, phone, attendees_count) 
            VALUES (:name, :date, :time, :location, :description, :image_url, :category, :status, :capacity, :phone, 0)";
    $stmt = $db->prepare($sql);

    $inserted = 0;
    foreach ($sampleEvents as $event) {
        $stmt->execute($event);
        $inserted++;
    }

    header('HTTP/1.1 201 Created');
    echo "Successfully inserted $inserted sample events.";

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    die("Error inserting sample events: " . $e->getMessage());
}
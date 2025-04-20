<?php
require 'db.php'; // Include your database connection file

try {




    // Sample data arrays
    $usernames = ['alice', 'bob', 'charlie', 'dave', 'eve', 'frank', 'grace', 'heidi', 'ivan', 'judy'];
    $eventNames = ['Tech Conference', 'Music Festival', 'Art Exhibition', 'Food Fair', 'Sports Event', 'Book Launch', 'Workshop', 'Seminar', 'Charity Run', 'Movie Night'];
    $locations = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];
    $categories = ['Technology', 'Music', 'Art', 'Food', 'Sports', 'Literature', 'Education', 'Health', 'Charity', 'Entertainment'];
    $statuses = ['Upcoming', 'Ongoing', 'Completed'];




    // Insert 20 users
    for ($i = 1; $i <= 20; $i++) {
        $username = $usernames[array_rand($usernames)] . $i;
        $password = password_hash('password' . $i, PASSWORD_BCRYPT);
        $role = ($i <= 2) ? 'admin' : 'user'; // First two users are admins

        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password, $role]);
    }




    // Insert 20 events
    for ($i = 1; $i <= 20; $i++) {
        $name = $eventNames[array_rand($eventNames)] . ' ' . $i;
        $date = date('Y-m-d', strtotime("+$i days"));
        $time = date('H:i:s', rand(0, 86400));
        $description = "This is a sample description for event $i.";
        $location = $locations[array_rand($locations)];
        $phone = '123-456-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $image_url = "https://example.com/image$i.jpg";
        $category = $categories[array_rand($categories)];
        $status = $statuses[array_rand($statuses)];
        $capacity = rand(50, 500);

        $stmt = $db->prepare("
            INSERT INTO events (name, date, time, description, location, phone, image_url, category, status, capacity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $date, $time, $description, $location, $phone, $image_url, $category, $status, $capacity]);
    }




    // Insert 20 event requests
    for ($i = 1; $i <= 20; $i++) {
        $name = "User $i";
        $email = "user$i@example.com";
        $event_id = rand(1, 20); // Random event ID
        $status = 'pending';

        $stmt = $db->prepare("INSERT INTO event_requests (name, email, event_id, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $event_id, $status]);
    }





    // Insert 20 comments
    for ($i = 1; $i <= 20; $i++) {
        $event_id = rand(1, 20); // Random event ID
        $user_id = rand(1, 20); // Random user ID
        $comment = "This is a sample comment for event $event_id by user $user_id.";

        $stmt = $db->prepare("INSERT INTO comments (event_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$event_id, $user_id, $comment]);
    }




    // Insert 20 RSVPs
    for ($i = 1; $i <= 20; $i++) {
        $user_id = rand(1, 20); // Random user ID
        $event_id = rand(1, 20); // Random event ID

        // Ensure no duplicate RSVPs for the same user and event
        $stmt = $db->prepare("SELECT * FROM rsvps WHERE user_id = ? AND event_id = ?");
        $stmt->execute([$user_id, $event_id]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO rsvps (user_id, event_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $event_id]);
        }
    }

    echo "20 sample data sets inserted successfully!";
} catch (PDOException $e) {
    die("Error inserting sample data: " . $e->getMessage());
}




?>
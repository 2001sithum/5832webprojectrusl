<?php
session_start();

// Database setup
$dsn = 'sqlite:' . __DIR__ . '/event_management.db';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $db = new PDO($dsn, null, null, $options);

    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            date TEXT NOT NULL,
            time TEXT NOT NULL,
            description TEXT NOT NULL,
            location TEXT NOT NULL,
            phone TEXT NOT NULL,
            image_url TEXT NOT NULL,
            category TEXT NOT NULL,
            status TEXT DEFAULT 'Upcoming',
            capacity INTEGER NOT NULL,
            attendees_count INTEGER DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS event_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            event_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'pending',
            FOREIGN KEY (event_id) REFERENCES events (id)
        );

        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events (id),
            FOREIGN KEY (user_id) REFERENCES users (id)
        );
    ");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password
    $role = 'user'; // Default role for registered users

    // Check if username already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Username already exists.";
        header("Location: index.php");
        exit();
    }

    // Insert new user into the database
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);

    $_SESSION['success'] = "Registration successful! Please log in.";
    header("Location: index.php");
    exit();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['success'] = "Login successful!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: index.php");
        exit();
    }
}

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hardcoded admin credentials
    $adminUsername = 'sithara2001';
    $adminPassword = '1234';

    if ($username === $adminUsername && $password === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['success'] = "Admin login successful!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid admin username or password.";
        header("Location: index.php");
        exit();
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    $_SESSION['success'] = "Logout successful!";
    header("Location: index.php");
    exit();
}

// Handle RSVP form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rsvp') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to RSVP.";
        header("Location: index.php");
        exit();
    }

    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $eventId = intval($_POST['event_id']);

    // Check if event capacity is reached
    $stmt = $db->prepare("SELECT capacity, attendees_count FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if ($event['attendees_count'] >= $event['capacity']) {
        $_SESSION['error'] = "Event capacity reached. Cannot RSVP.";
        header("Location: index.php");
        exit();
    }

    // Insert RSVP request into the database
    $stmt = $db->prepare("INSERT INTO event_requests (name, email, event_id) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $eventId]);

    // Update attendees count
    $stmt = $db->prepare("UPDATE events SET attendees_count = attendees_count + 1 WHERE id = ?");
    $stmt->execute([$eventId]);

    $_SESSION['success'] = "RSVP submitted successfully!";
    header("Location: index.php");
    exit();
}

// Handle adding a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'addEvent') {
        if (!isset($_SESSION['admin_logged_in'])) {
            $_SESSION['error'] = "You must be an admin to add events.";
            header("Location: index.php");
            exit();
        }

        // Handle image upload
        $imageUrl = '';
        if (isset($_FILES['eventImage']) && $_FILES['eventImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $imageName = basename($_FILES['eventImage']['name']);
            $imagePath = $uploadDir . $imageName;

            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($_FILES['eventImage']['tmp_name'], $imagePath)) {
                $imageUrl = 'uploads/' . $imageName;
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: index.php");
                exit();
            }
        }

        // Insert event into database
        $stmt = $db->prepare("
            INSERT INTO events (name, date, time, description, location, phone, image_url, category, capacity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['eventName'],
            $_POST['eventDate'],
            $_POST['eventTime'],
            $_POST['eventDescription'],
            $_POST['eventLocation'],
            $_POST['eventPhone'],
            $imageUrl,
            $_POST['eventCategory'],
            $_POST['eventCapacity']
        ]);

        $_SESSION['success'] = "Event added successfully!";
        header("Location: index.php");
        exit();
    } elseif ($_POST['action'] === 'updateEvent') {
        if (!isset($_SESSION['admin_logged_in'])) {
            $_SESSION['error'] = "You must be an admin to update events.";
            header("Location: index.php");
            exit();
        }

        // Handle image upload for update
        $imageUrl = $_POST['existingImage'];
        if (isset($_FILES['eventImage']) && $_FILES['eventImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $imageName = basename($_FILES['eventImage']['name']);
            $imagePath = $uploadDir . $imageName;

            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($_FILES['eventImage']['tmp_name'], $imagePath)) {
                $imageUrl = 'uploads/' . $imageName;
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: index.php");
                exit();
            }
        }

        // Update event in database
        $stmt = $db->prepare("
            UPDATE events 
            SET name = ?, date = ?, time = ?, description = ?, location = ?, phone = ?, image_url = ?, category = ?, capacity = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['eventName'],
            $_POST['eventDate'],
            $_POST['eventTime'],
            $_POST['eventDescription'],
            $_POST['eventLocation'],
            $_POST['eventPhone'],
            $imageUrl,
            $_POST['eventCategory'],
            $_POST['eventCapacity'],
            $_POST['eventId']
        ]);

        $_SESSION['success'] = "Event updated successfully!";
        header("Location: index.php");
        exit();
    }
}

// Handle deleting an event
if (isset($_GET['action']) && $_GET['action'] === 'deleteEvent' && isset($_GET['id'])) {
    if (!isset($_SESSION['admin_logged_in'])) {
        $_SESSION['error'] = "You must be an admin to delete events.";
        header("Location: index.php");
        exit();
    }

    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    $_SESSION['success'] = "Event deleted successfully!";
    header("Location: index.php");
    exit();
}

// Handle adding a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addComment') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to comment.";
        header("Location: index.php");
        exit();
    }

    $eventId = intval($_POST['event_id']);
    $userId = $_SESSION['user_id'];
    $comment = htmlspecialchars($_POST['comment']);

    // Insert comment into the database
    $stmt = $db->prepare("INSERT INTO comments (event_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$eventId, $userId, $comment]);

    $_SESSION['success'] = "Comment added successfully!";
    header("Location: index.php");
    exit();
}

// Fetch all events
$events = $db->query("SELECT * FROM events ORDER BY date ASC")->fetchAll();

// Fetch all comments for events
$comments = [];
foreach ($events as $event) {
    $stmt = $db->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE event_id = ? ORDER BY created_at DESC");
    $stmt->execute([$event['id']]);
    $comments[$event['id']] = $stmt->fetchAll();
}
?>

<?php include 'indexback.html'; ?>

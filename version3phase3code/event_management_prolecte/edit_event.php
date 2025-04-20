<?php
require_once '../includes/db.php'; // Relative path now
require_once '../includes/functions.php';

requireAdmin(); // Only admins

// Get event ID from GET request for editing, validate it
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEditing = $eventId && $eventId > 0; // True if ID is valid and positive
$pageTitle = $isEditing ? "Edit Event" : "Add New Event"; // Set page title accordingly

// Default values for the event form fields
$event = [
    'name' => '', 'date' => date('Y-m-d'), 'time' => '12:00', 'location' => '',
    'description' => '', 'image_url' => '', 'category' => '', 'status' => 'Upcoming',
    'capacity' => 100, 'phone' => ''
];

// If editing, load the existing event data from the database
if ($isEditing) {
    try {
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array
        if ($existingEvent) {
            // Merge existing data into the $event array, overwriting defaults
            $event = array_merge($event, $existingEvent);
        } else {
            // Event ID provided, but no event found in the database
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Event not found for editing.'];
            redirect('admin/index.php'); // Redirect back to admin list
        }
    } catch (PDOException $e) {
        // Database error while loading event data
        error_log("Admin Edit Load Error: ID [$eventId] - " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load event data due to a database error.'];
        redirect('admin/index.php');
    }
}

// Handle form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple Retrieval (using original approach)
    // Trim whitespace and use null coalescing operator for defaults
    $event['name'] = trim($_POST['name'] ?? '');
    $event['date'] = trim($_POST['date'] ?? '');
    $event['time'] = trim($_POST['time'] ?? '');
    $event['location'] = trim($_POST['location'] ?? '');
    $event['description'] = trim($_POST['description'] ?? '');
    $event['image_url'] = trim($_POST['image_url'] ?? '');
    $event['category'] = trim($_POST['category'] ?? '');
    $event['status'] = trim($_POST['status'] ?? 'Upcoming');
    // Filter capacity as integer, default 0, minimum 0
    $event['capacity'] = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
    $event['phone'] = trim($_POST['phone'] ?? '');

    // Basic Validation (original approach)
    $errors = []; // Array to hold validation error messages
    if (empty($event['name'])) $errors[] = "Name required.";
    if (empty($event['date'])) $errors[] = "Date required.";
    if (empty($event['time'])) $errors[] = "Time required.";
    if (empty($event['location'])) $errors[] = "Location required.";
    if (empty($event['description'])) $errors[] = "Description required.";
    // Validate status against allowed values
    if (!in_array($event['status'], ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'])) $errors[] = "Invalid status.";

    // If there are no validation errors, proceed to database operation
    if (empty($errors)) {
        try {
            // Prepare parameters for the SQL query
            // Use ?: null to insert NULL for empty optional fields
            $params = [
                ':name' => $event['name'],
                ':date' => $event['date'],
                ':time' => $event['time'], // Assumes time format is okay for DB
                ':location' => $event['location'],
                ':description' => $event['description'],
                ':image_url' => $event['image_url'] ?: null,
                ':category' => $event['category'] ?: null,
                ':status' => $event['status'],
                ':capacity' => $event['capacity'],
                ':phone' => $event['phone'] ?: null
            ];

            if ($isEditing) {
                // Update existing event
                $sql = "UPDATE events SET name=:name, date=:date, time=:time, location=:location,
                        description=:description, image_url=:image_url, category=:category,
                        status=:status, capacity=:capacity, phone=:phone
                        WHERE id = :id";
                $params[':id'] = $eventId; // Add the event ID for the WHERE clause
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Event updated successfully.'];
                redirect('admin/index.php'); // Redirect after successful update
            } else {
                // Insert new event
                // attendees_count defaults to 0 on insert
                $sql = "INSERT INTO events (name, date, time, location, description, image_url, category, status, capacity, phone, attendees_count)
                          VALUES (:name, :date, :time, :location, :description, :image_url, :category, :status, :capacity, :phone, 0)";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $newEventId = $db->lastInsertId(); // Get the ID of the newly inserted event
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Event added successfully.'];
                error_log("Admin added new event ID: {$newEventId} ('" . escape($event['name']) . "')");
                redirect('admin/index.php'); // Redirect after successful insert
            }
        } catch (PDOException $e) {
            // Database error during save operation
            error_log("Admin Event Save Error: ID [" . ($isEditing ? $eventId : 'NEW') . "] - " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error saving event. Check logs.'];
            // Stay on the form page, message will be shown on next load (if header handles it)
        }
    } else {
        // Validation errors occurred
        // Use session message to display errors (original approach)
        // Note: This message will only show *after* a redirect.
        $_SESSION['message'] = ['type' => 'error', 'text' => implode('<br>', $errors)];
        // No redirect here, stay on the form to potentially show errors (though the original logic relied on session flash message after redirect)
    }
} // End POST handling

// Include the header template
includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <section class="content-section">
        <h1 class="page-title"><?php e($pageTitle); ?></h1>
        <?php // displayMessages(); // Assumes this is handled in the header template to show session messages ?>

        <div class="form-container admin-form">
            <?php // Form action points to the current script, includes ID if editing ?>
            <form action="edit_event.php<?php echo $isEditing ? '?id=' . (int)$eventId : ''; ?>" method="POST">
                <?php // No CSRF input here ?>

                <div class="form-group">
                    <label for="name">Event Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" value="<?php e($event['name']); ?>" required class="form-input">
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="date">Date <span class="required">*</span></label>
                        <input type="date" name="date" id="date" value="<?php e($event['date']); ?>" required class="form-input">
                    </div>
                    <div class="form-group form-group-half">
                        <label for="time">Time <span class="required">*</span></label>
                        <?php // Format time to HH:MM for the input field ?>
                        <input type="time" name="time" id="time" value="<?php e(substr($event['time'], 0, 5)); ?>" required class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" name="location" id="location" value="<?php e($event['location']); ?>" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" name="category" id="category" value="<?php e($event['category']); ?>" class="form-input">
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea name="description" id="description" rows="5" required class="form-textarea"><?php e($event['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="capacity">Capacity <span class="required">*</span></label>
                        <input type="number" name="capacity" id="capacity" value="<?php e($event['capacity']); ?>" required min="0" class="form-input">
                    </div>
                    <div class="form-group form-group-half">
                        <label for="status">Status <span class="required">*</span></label>
                        <select name="status" id="status" required class="form-select">
                            <?php $statuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled']; ?>
                            <?php foreach ($statuses as $statusOption): ?>
                                <option value="<?php e($statusOption); ?>" <?php echo ($event['status'] == $statusOption ? 'selected' : ''); ?>>
                                    <?php e($statusOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Contact Phone (Optional)</label>
                    <input type="tel" name="phone" id="phone" value="<?php e($event['phone']); ?>" class="form-input">
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL (Optional)</label>
                    <input type="url" name="image_url" id="image_url" value="<?php e($event['image_url']); ?>" class="form-input" placeholder="https://...">
                    <?php // Show image preview if URL exists ?>
                    <?php if (!empty($event['image_url'])): ?>
                        <div class="image-preview mt-2">
                            <img src="<?php e($event['image_url']); ?>" alt="Current Image Preview" style="max-width:150px; height: auto; display: block; margin-top: 5px;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="admin/index.php" class="button button-secondary">Cancel</a>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-save"></i> <?php echo $isEditing ? 'Update Event' : 'Add Event'; ?>
                    </button>
                </div>
            </form>
        </div>
    </section>

<?php // Inline CSS as originally provided ?>
    <style>
        /* Form layout styles */
        .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 0; /* Reset margin */ }
        .form-group-half { flex: 1 1 calc(50% - 10px); min-width: 200px; margin-bottom: var(--spacing-lg); /* Add bottom margin back */ }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 767px) {
            .form-row { gap: 0; /* Remove gap on small screens */ }
            .form-group-half { flex: 1 1 100%; /* Stack elements vertically */ }
        }

        /* General admin form styles (if not defined globally) */
        .admin-form .form-group { margin-bottom: 1rem; }
        .admin-form label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .admin-form .form-input,
        .admin-form .form-textarea,
        .admin-form .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc; /* Basic border */
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box; /* Include padding and border in element's total width/height */
        }
        .admin-form .form-textarea { resize: vertical; min-height: 100px; }
        .admin-form .required { color: red; /* Simple required indicator */ }
        .admin-form .form-actions { margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end; }

        /* Dark theme styles (from original prompt, adjusted for clarity) */
        :root {
            --spacing-lg: 1.5rem;
            --primary-color: #1DB954; /* Spotify green */
            --secondary-color: #FFFFFF;
            --background-dark: #121212; /* Spotify dark background */
            --glass-bg: rgba(255, 255, 255, 0.1);
            --blur-radius: 10px;
            --text-color: #FFFFFF;
            --error-color: #E91429;
            --border-radius: 8px;
        }
        body.dark-theme .content-section { /* Apply only if body has dark-theme class */
            background: var(--background-dark);
            color: var(--text-color);
            padding: 2rem;
        }
        body.dark-theme .page-title {
            color: var(--text-color);
            margin-bottom: 1.5rem;
        }
        body.dark-theme .form-container.admin-form {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--blur-radius));
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        body.dark-theme .form-group label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        body.dark-theme .form-input,
        body.dark-theme .form-textarea,
        body.dark-theme .form-select {
            width: 100%;
            padding: 0.75rem;
            background: transparent;
            border: none;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            color: var(--text-color);
            font-size: 1rem;
            transition: border-bottom 0.3s ease;
            border-radius: 0; /* Remove default rounding for underline effect */
        }
        body.dark-theme .form-input:focus,
        body.dark-theme .form-textarea:focus,
        body.dark-theme .form-select:focus {
            outline: none;
            border-bottom: 2px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(29, 185, 84, 0.4); /* Glow effect */
        }
        body.dark-theme .form-select { /* Style select differently for dark theme */
            background: var(--glass-bg); /* Keep glass background */
            border-radius: var(--border-radius); /* Add back border radius */
            padding: 0.75rem; /* Ensure padding */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Add subtle border */
        }
        /* Adjust select appearance for dark theme */
        body.dark-theme .form-select option {
            background-color: #333; /* Dark background for options */
            color: var(--text-color);
        }

        body.dark-theme .button {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            text-decoration: none; /* Ensure links look like buttons */
            display: inline-block; /* Proper alignment */
        }
        body.dark-theme .button-primary {
            background: var(--primary-color);
            color: #000; /* Black text on green */
            border: none;
        }
        body.dark-theme .button-primary:hover {
            background: #21CE62; /* Lighter green on hover */
            box-shadow: 0 0 10px rgba(29, 185, 84, 0.5);
        }
        body.dark-theme .button-secondary {
            background: transparent;
            border: 1px solid var(--secondary-color);
            color: var(--secondary-color);
        }
        body.dark-theme .button-secondary:hover {
            background: rgba(255, 255, 255, 0.1); /* Slight white overlay on hover */
        }
        body.dark-theme .image-preview img {
            border-radius: var(--border-radius);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        body.dark-theme .required {
            color: var(--primary-color); /* Use primary color for required marker */
        }
    </style>
<?php includeTemplate('footer'); ?>
<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// Authorization: Only admins can add or edit events
requireAuth(true); // Require admin role

// --- Determine Mode: Add or Edit ---
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$isEditing = ($eventId !== null && $eventId !== false); // True if editing, false if adding

// Default values for the form fields (used when adding or if fetch fails)
$event = [
    'id' => null,
    'name' => '',
    'date' => '',
    'time' => '',
    'location' => '',
    'description' => '',
    'phone' => '',
    'image_url' => '', // For Unsplash or other external URLs
    'category' => '',
    'status' => 'Upcoming', // Default status for new events
    'capacity' => 100, // Default capacity (use 0 for unlimited)
    'attendees_count' => 0 // Should always be 0 for new events
];
$pageTitle = "Add New Event"; // Default title

// If editing, load existing event data
if ($isEditing) {
    $pageTitle = "Edit Event";
    try {
        $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
        $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        $existingEvent = $stmt->fetch();

        if ($existingEvent) {
            // Merge fetched data into the $event array, overwriting defaults
            $event = array_merge($event, $existingEvent);
             // Ensure numeric types are correct after fetch
             $event['id'] = (int)$event['id'];
             $event['capacity'] = (int)$event['capacity'];
             $event['attendees_count'] = (int)$event['attendees_count'];
        } else {
            // Event ID provided but not found in DB
            setFlashMessage('error', 'The event you are trying to edit (ID: ' . $eventId . ') was not found.');
            redirect('admin.php');
        }
    } catch (PDOException $e) {
        error_log("Event Edit: Load Error - ID [$eventId] - " . $e->getMessage());
        setFlashMessage('error', 'Could not load event data for editing due to a database error.');
        redirect('admin.php'); // Redirect on critical load error
    }
}

$errors = []; // Initialize errors array (field => message)

// --- Form Processing ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- CSRF Token Validation ---
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
         $errors['csrf'] = 'Invalid request origin. Please try submitting the form again.';
         error_log("CSRF token mismatch for event " . ($isEditing ? "edit (ID: $eventId)" : "add") . " attempt.");
    } else {
        // --- Retrieve and Sanitize Form Data ---
        // Update the $event array directly with submitted data for sticky form behavior
        $event['name'] = trim($_POST['name'] ?? '');
        $event['date'] = trim($_POST['date'] ?? '');
        $event['time'] = trim($_POST['time'] ?? '');
        $event['location'] = trim($_POST['location'] ?? '');
        $event['description'] = trim($_POST['description'] ?? ''); // Allow generous trimming later if needed
        $event['phone'] = preg_replace('/[^0-9+() -]/', '', trim($_POST['phone'] ?? '')); // Basic phone sanitize
        $event['image_url'] = trim($_POST['image_url'] ?? ''); // Trim URL
        $event['category'] = trim($_POST['category'] ?? '');
        $event['status'] = trim($_POST['status'] ?? 'Upcoming');
        $rawCapacity = trim($_POST['capacity'] ?? '0');

        // --- Server-Side Validation Rules ---
        if (empty($event['name'])) $errors['name'] = "Event name is required.";
        if (empty($event['date'])) {
             $errors['date'] = "Event date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['date'])) {
            $errors['date'] = "Date must be in YYYY-MM-DD format.";
        }
        // Basic time format check (HH:MM) - could be more robust (HH:MM:SS)
        if (empty($event['time'])) {
             $errors['time'] = "Event time is required.";
        } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $event['time'])) {
             $errors['time'] = "Time must be in HH:MM format (e.g., 14:30).";
        } else {
            // Optionally standardize time to HH:MM:SS if needed by DB
             if (strlen($event['time']) === 5) $event['time'] .= ':00';
        }

        if (empty($event['location'])) $errors['location'] = "Event location is required.";
        if (empty($event['category'])) $errors['category'] = "Event category is required.";
        if (empty($event['description'])) $errors['description'] = "Event description is required.";

        // Validate capacity (must be integer, 0 or greater)
        if (!ctype_digit($rawCapacity) || (int)$rawCapacity < 0) {
             $errors['capacity'] = "Capacity must be a whole number (0 or greater). Use 0 for unlimited.";
             $event['capacity'] = 0; // Reset on error for sticky form
        } else {
             $event['capacity'] = (int)$rawCapacity; // Store as integer
        }

        // Validate status against allowed values
        $allowedStatuses = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
        if (!in_array($event['status'], $allowedStatuses)) {
             $errors['status'] = "Invalid event status selected.";
             $event['status'] = 'Upcoming'; // Reset to default
        }

         // Validate image URL if provided (basic FILTER_VALIDATE_URL)
         if (!empty($event['image_url']) && !filter_var($event['image_url'], FILTER_VALIDATE_URL)) {
             $errors['image_url'] = "The provided Image URL does not appear to be valid.";
             // $event['image_url'] = ''; // Optionally clear invalid URL for sticky form
         }


        // --- Save Data if No Validation Errors ---
        if (empty($errors)) {
            try {
                // Ensure related data (like attendees_count) is handled correctly
                 if ($isEditing) {
                    // Don't reset attendees count when just editing details
                    // It's managed by the RSVP system.
                    // However, if status changes to Cancelled or Completed, maybe reset? Discuss logic.
                     // If capacity is reduced below current attendees, what happens?
                     // For this demo, we'll allow capacity changes but won't automatically kick people out.
                 } else {
                    // New event starts with 0 attendees
                     $event['attendees_count'] = 0;
                 }

                 // Parameters for PDO query (matching table columns)
                 $params = [
                    ':name' => $event['name'],
                    ':date' => $event['date'],
                    ':time' => $event['time'],
                    ':location' => $event['location'],
                    ':description' => $event['description'],
                    ':phone' => $event['phone'] ?: null, // Store null if empty
                    ':image_url' => $event['image_url'] ?: null, // Store null if empty
                    ':category' => $event['category'],
                    ':status' => $event['status'],
                    ':capacity' => $event['capacity'],
                    ':attendees_count' => $event['attendees_count'] // Set explicitly
                 ];


                if ($isEditing) {
                    // --- Update Existing Event ---
                    $sql = "UPDATE events SET
                                name = :name, date = :date, time = :time, location = :location,
                                description = :description, phone = :phone, image_url = :image_url,
                                category = :category, status = :status, capacity = :capacity
                                -- attendees_count is NOT updated here directly
                            WHERE id = :id";
                    $params[':id'] = $eventId; // Add the ID for the WHERE clause
                    $stmt = $db->prepare($sql);

                    if ($stmt->execute($params)) {
                        unsetCsrfToken(); // Clear token on success
                        setFlashMessage('success', 'Event "' . escape($event['name']) . '" updated successfully.');
                        redirect('event_view.php?id=' . $eventId); // Redirect to view page after update
                    } else {
                        // This might indicate a different issue if execute returns false but no exception
                        $errors['db'] = 'Database error: Failed to update the event.';
                        error_log("Event Update failed (PDO execute returned false): ID [$eventId]");
                    }

                } else {
                    // --- Insert New Event ---
                    // Include created_at timestamp
                     $datetimeNow = (DB_TYPE === 'mysql') ? 'NOW()' : "datetime('now')";
                     $sql = "INSERT INTO events (
                                name, date, time, location, description, phone, image_url,
                                category, status, capacity, attendees_count, created_at
                             ) VALUES (
                                :name, :date, :time, :location, :description, :phone, :image_url,
                                :category, :status, :capacity, :attendees_count, {$datetimeNow}
                             )";
                    $stmt = $db->prepare($sql);

                    // Remove attendees_count from params if DB default is 0
                     // Let's keep it explicit here
                     $params[':attendees_count'] = 0; // Ensure 0 for new event


                    if ($stmt->execute($params)) {
                         $newEventId = $db->lastInsertId();
                         unsetCsrfToken(); // Clear token on success
                        setFlashMessage('success', 'Event "' . escape($event['name']) . '" added successfully.');
                         redirect('event_view.php?id=' . $newEventId); // Go to the new event's view page
                    } else {
                         $errors['db'] = 'Database error: Failed to add the new event.';
                         error_log("Event Insert failed (PDO execute returned false) for: {$event['name']}");
                    }
                }
            } catch (PDOException $e) {
                error_log("Event Save Error: " . $e->getMessage());
                // Provide a more user-friendly message for common errors like unique constraints
                 if ((DB_TYPE === 'mysql' && $e->getCode() == '23000') || // Integrity constraint violation (includes unique)
                    (DB_TYPE === 'sqlite' && $e->getCode() == 23000) || // SQLite specific code
                     str_contains(strtolower($e->getMessage()), 'unique constraint failed')) {
                     // Be generic, don't reveal which field caused the conflict
                    $errors['db'] = "Could not save the event. A similar event might already exist.";
                } else {
                    $errors['db'] = 'A database error occurred while saving the event. Please try again.';
                }
            } // End Try-Catch for DB operation
        } // End empty($errors) check
    } // End CSRF valid block

    // --- Display errors if they occurred during processing or validation ---
     if (!empty($errors)) {
         $errorMsg = "Please correct the following errors: " . implode(' ', array_values($errors));
         setFlashMessage('error', $errorMsg);
         // Regenerate CSRF token if there were errors, so user can retry safely
         generateCsrfToken();
    }

} // End of POST request handling

// Ensure CSRF token exists for initial form display or after errors
$csrfToken = generateCsrfToken();

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

<div class="form-container event-edit-form">
    <h2 class="form-title"><i class="fas fa-<?php echo $isEditing ? 'edit' : 'plus-circle'; ?>"></i> <?php echo escape($pageTitle); ?></h2>

    <?php /* Flash messages rendered globally in header.php */ ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . ($isEditing ? '?id=' . $eventId : ''); ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">

        <div class="form-group">
            <label for="name">Event Name <span class="required">*</span></label>
            <input type="text" name="name" id="name" placeholder="Enter a clear and concise event name" value="<?php e($event['name']); ?>" required class="form-input <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" aria-required="true" aria-describedby="nameError">
            <?php if (isset($errors['name'])): ?><span id="nameError" class="invalid-feedback"><?php e($errors['name']); ?></span><?php endif; ?>
        </div>

        <div class="form-row"> <?php /* Use a class for layout */ ?>
            <div class="form-group form-group-half">
                <label for="date">Date <span class="required">*</span></label>
                <input type="date" name="date" id="date" value="<?php e($event['date']); ?>" required class="form-input <?php echo isset($errors['date']) ? 'is-invalid' : ''; ?>" aria-required="true" pattern="\d{4}-\d{2}-\d{2}" aria-describedby="dateError">
                <small class="form-text text-muted">Format: YYYY-MM-DD</small>
                 <?php if (isset($errors['date'])): ?><span id="dateError" class="invalid-feedback"><?php e($errors['date']); ?></span><?php endif; ?>
            </div>
            <div class="form-group form-group-half">
                <label for="time">Time <span class="required">*</span></label>
                 <?php // Use 'time' input type, format value correctly HH:MM ?>
                <input type="time" name="time" id="time" value="<?php e(substr($event['time'], 0, 5)); // HH:MM format for input ?>" required class="form-input <?php echo isset($errors['time']) ? 'is-invalid' : ''; ?>" aria-required="true" step="60" aria-describedby="timeError"> <?php /* step="60" suggests minutes */ ?>
                <small class="form-text text-muted">Format: HH:MM (24-hour)</small>
                 <?php if (isset($errors['time'])): ?><span id="timeError" class="invalid-feedback"><?php e($errors['time']); ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="location">Location <span class="required">*</span></label>
            <input type="text" name="location" id="location" placeholder="Venue name and/or address" value="<?php e($event['location']); ?>" required class="form-input <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" aria-required="true" aria-describedby="locationError">
             <?php if (isset($errors['location'])): ?><span id="locationError" class="invalid-feedback"><?php e($errors['location']); ?></span><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="category">Category <span class="required">*</span></label>
            <input list="event_categories" name="category" id="category" placeholder="e.g., Conference, Music, Workshop" value="<?php e($event['category']); ?>" required class="form-input <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>" aria-required="true" aria-describedby="categoryError">
             <?php /* Datalist provides suggestions but allows free text entry */ ?>
             <datalist id="event_categories">
                 <option value="Conference">
                 <option value="Workshop">
                 <option value="Music Concert">
                 <option value="Art Exhibition">
                 <option value="Food Festival">
                 <option value="Sports Game">
                 <option value="Charity Gala">
                 <option value="Community Meetup">
                 <option value="Networking Event">
                 <option value="Theater Play">
                 <option value="Tech Talk">
             </datalist>
             <?php if (isset($errors['category'])): ?><span id="categoryError" class="invalid-feedback"><?php e($errors['category']); ?></span><?php endif; ?>
        </div>

        <div class="form-group">
            <label for="description">Description <span class="required">*</span></label>
            <textarea name="description" id="description" placeholder="Detailed information about the event. What will attendees experience?" rows="6" required class="form-textarea <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" aria-required="true" aria-describedby="descriptionError"><?php e($event['description']); ?></textarea>
             <small class="form-text text-muted">Provide details about speakers, schedule, requirements, etc.</small>
             <?php if (isset($errors['description'])): ?><span id="descriptionError" class="invalid-feedback"><?php e($errors['description']); ?></span><?php endif; ?>
        </div>

         <div class="form-row">
            <div class="form-group form-group-half">
                <label for="capacity">Capacity <span class="required">*</span></label>
                <input type="number" name="capacity" id="capacity" value="<?php e((string)$event['capacity']); ?>" required min="0" class="form-input <?php echo isset($errors['capacity']) ? 'is-invalid' : ''; ?>" aria-required="true" aria-describedby="capacityError">
                 <small class="form-text text-muted">Set to 0 for unlimited.</small>
                 <?php if (isset($errors['capacity'])): ?><span id="capacityError" class="invalid-feedback"><?php e($errors['capacity']); ?></span><?php endif; ?>
            </div>
             <div class="form-group form-group-half">
                <label for="status">Status <span class="required">*</span></label>
                <select name="status" id="status" class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" required aria-required="true" aria-describedby="statusError">
                    <option value="Upcoming" <?php echo ($event['status'] === 'Upcoming' ? 'selected' : ''); ?>>Upcoming</option>
                    <option value="Ongoing" <?php echo ($event['status'] === 'Ongoing' ? 'selected' : ''); ?>>Ongoing</option>
                    <option value="Completed" <?php echo ($event['status'] === 'Completed' ? 'selected' : ''); ?>>Completed</option>
                    <option value="Cancelled" <?php echo ($event['status'] === 'Cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                </select>
                 <?php if (isset($errors['status'])): ?><span id="statusError" class="invalid-feedback"><?php e($errors['status']); ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="phone">Contact Phone (Optional)</label>
            <input type="tel" name="phone" id="phone" placeholder="Phone number for inquiries" value="<?php e($event['phone']); ?>" class="form-input">
        </div>

         <div class="form-group">
            <label for="image_url">Image URL (Optional)</label>
            <input type="url" name="image_url" id="image_url" placeholder="https://images.unsplash.com/your-image..." value="<?php e($event['image_url']); ?>" class="form-input <?php echo isset($errors['image_url']) ? 'is-invalid' : ''; ?>" aria-describedby="imageHelp imageError">
             <small id="imageHelp" class="form-text text-muted">Link to an image representing the event (e.g., from Unsplash). Must be a valid URL.</small>
             <?php if (isset($errors['image_url'])): ?><span id="imageError" class="invalid-feedback"><?php e($errors['image_url']); ?></span><?php endif; ?>

            <?php // Show preview only if editing and URL is valid ?>
            <?php if ($isEditing && !empty($event['image_url']) && filter_var($event['image_url'], FILTER_VALIDATE_URL)): ?>
                <div class="image-preview mt-2">
                     <p><small>Current Image Preview:</small></p>
                    <img src="<?php e($event['image_url']); ?>" alt="Current event image preview" style="max-width: 250px; max-height: 120px; margin-top: 5px; border-radius: 5px; border: 1px solid var(--border-color); object-fit: cover;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions mt-3"> <?php /* Wrapper for buttons */ ?>
            <a href="<?php echo $isEditing ? baseUrl('event_view.php?id='.$eventId) : baseUrl('admin.php'); ?>">
                 <button type="button" class="action-button form-button-cancel">Cancel</button>
            </a>
             <button type="submit" class="form-button form-button-submit">
                 <i class="fas fa-save" aria-hidden="true"></i> <?php echo $isEditing ? 'Update Event' : 'Add Event'; ?>
            </button>
        </div>

         <p class="required-note mt-2"><small><span class="required">*</span> Required fields</small></p>

    </form>
</div>

<?php
renderTemplate('footer');
?>

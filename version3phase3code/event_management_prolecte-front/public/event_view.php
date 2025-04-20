<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// --- Input and Validation ---
// Get event ID from query string, ensure it's a positive integer
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$eventId) {
    setFlashMessage('error', 'Invalid or missing event ID specified.');
    redirect('index.php');
}

// Initialize variables
/** @var array|null $event */
$event = null;
$userHasRSVPd = false; // Assume not RSVP'd unless logged in and DB check confirms
$currentUserId = $_SESSION['user_id'] ?? 0; // Get current user ID (0 if guest)

// --- Fetch Event Data ---
try {
    // Fetch the specific event by ID
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $event = $stmt->fetch();

    // If event not found, redirect with error
    if (!$event) {
        setFlashMessage('error', 'The requested event could not be found (ID: ' . $eventId . ').');
        redirect('index.php');
    }

     // Check RSVP status if a user is logged in (and not an admin)
     if ($currentUserId > 0 && !isAdmin()) {
        $rsvpCheckStmt = $db->prepare("SELECT 1 FROM rsvps WHERE user_id = :user_id AND event_id = :event_id LIMIT 1");
        $rsvpCheckStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
        $rsvpCheckStmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
        $rsvpCheckStmt->execute();
        $userHasRSVPd = (bool)$rsvpCheckStmt->fetchColumn(); // fetchColumn returns the value or false
     }

} catch (PDOException $e) {
    error_log("Event View Error: ID [$eventId] - " . $e->getMessage());
    setFlashMessage('error', 'Failed to load event details due to a database error.');
    redirect('index.php'); // Redirect on critical error
}

// Set dynamic page title using the fetched event name
$pageTitle = escape($event['name']);
$csrfToken = generateCsrfToken(); // Generate CSRF for potential actions (RSVP, admin delete)

// Prepare variables for easier use in template
$isFull = $event['attendees_count'] >= $event['capacity'] && $event['capacity'] > 0;
$canRSVP = in_array($event['status'], ['Upcoming', 'Ongoing']);
$isLoggedInUser = isAuthenticated() && !isAdmin();
$eventStatusClass = 'status-' . strtolower(escape($event['status']));

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

<article class="event-detail-container <?php echo $eventStatusClass; ?>">
    <header class="event-detail-header">
        <h1><?php e($event['name']); ?></h1>

        <?php if (!empty($event['image_url'])): ?>
             <?php // Basic validation if it's a likely URL format
                 $isValidImageUrl = filter_var($event['image_url'], FILTER_VALIDATE_URL);
             ?>
             <?php if ($isValidImageUrl): ?>
                <img src="<?php e($event['image_url']); ?>" alt="" class="event-image mb-3" loading="lazy"> <?php /* Alt can be empty if heading provides context, or provide descriptive alt */ ?>
             <?php else: ?>
                 <p class="text-center text-muted"><small>(Invalid image URL provided: <?php e($event['image_url']); ?>)</small></p>
             <?php endif; ?>
        <?php endif; ?>

        <div class="event-meta">
            <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> <?php echo formatDate($event['date'], 'l, F j, Y'); ?></span>
            <span><i class="fas fa-clock" aria-hidden="true"></i> <?php echo formatDate($event['time'], 'g:i A'); ?></span>
            <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?php e($event['location']); ?></span>
            <span><i class="fas fa-tags" aria-hidden="true"></i> <?php e($event['category']); ?></span>
             <?php if (!empty($event['phone'])): ?>
               <span><i class="fas fa-phone" aria-hidden="true"></i> <a href="tel:<?php echo escape(preg_replace('/[^0-9+]/', '', $event['phone'])); ?>"><?php e($event['phone']); ?></a></span>
             <?php endif; ?>
        </div>
         <div class="event-meta"> <?php /* Second row for status/capacity */ ?>
            <span><i class="fas fa-check-square" aria-hidden="true"></i> Status: <strong class="event-status-<?php echo strtolower(escape($event['status'])); ?>"><?php e($event['status']); ?></strong></span>
            <span><i class="fas fa-users" aria-hidden="true"></i> Capacity: <?php echo ((int)$event['capacity'] === 0 ? 'Unlimited' : (int)$event['capacity']); ?></span>
            <span><i class="fas fa-user-check" aria-hidden="true"></i> Attending: <?php echo (int)$event['attendees_count']; ?></span>
            <?php if ($isFull && $canRSVP): ?>
                 <span class="event-status-highlight full"><i class="fas fa-exclamation-circle"></i> Event Full</span>
            <?php endif; ?>
        </div>
    </header>

    <section class="event-description mt-3">
        <h2>About this Event</h2>
        <?php /* nl2br converts newlines to <br>, use with escape for user-generated content */ ?>
        <p><?php echo nl2br(escape($event['description'])); ?></p>
    </section>

    <footer class="event-detail-actions mt-3 pt-3 border-top">
         <a href="<?php echo baseUrl('index.php'); ?>" class="action-button action-back">
             <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Events
         </a>

         <?php // --- RSVP / Cancel / Login Buttons --- ?>
         <?php if ($canRSVP): ?>
             <?php if ($isLoggedInUser): // Logged in user (not admin) ?>
                 <?php if ($userHasRSVPd): // User is already RSVP'd ?>
                     <form method="POST" action="<?php echo baseUrl('rsvp.php'); ?>" class="inline-form">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="action-button action-rsvp-cancel" data-confirm="Cancel your RSVP for '<?php e($event['name']); ?>'?">
                            <i class="fas fa-times-circle" aria-hidden="true"></i> Cancel RSVP
                        </button>
                    </form>
                 <?php elseif (!$isFull): // User can RSVP (not full) ?>
                      <form method="POST" action="<?php echo baseUrl('rsvp.php'); ?>" class="inline-form">
                        <input type="hidden" name="action" value="rsvp">
                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="action-button action-rsvp">
                            <i class="fas fa-check-circle" aria-hidden="true"></i> RSVP Now
                        </button>
                    </form>
                 <?php else: // Event is full, user not RSVP'd ?>
                      <span class="action-button is-disabled" aria-disabled="true"><i class="fas fa-exclamation-circle"></i> Event Full</span>
                 <?php endif; ?>
             <?php elseif (!isAuthenticated()): // Guest user, event allows RSVP ?>
                 <a href="<?php echo baseUrl('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="action-button action-login-prompt">
                     <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login to RSVP
                 </a>
             <?php endif; // End user/guest logic ?>
         <?php else: // Event cannot be RSVP'd (Completed / Cancelled) ?>
              <span class="action-button is-disabled" aria-disabled="true">
                  <i class="fas fa-<?php echo $event['status'] === 'Completed' ? 'check' : 'ban'; ?>"></i> Event <?php e($event['status']); ?>
              </span>
         <?php endif; // End $canRSVP condition ?>

         <?php // --- Admin Actions --- ?>
         <?php if (isAdmin()): ?>
             <a href="<?php echo baseUrl('event_edit.php?id=' . $eventId); ?>" class="action-button action-edit">
                 <i class="fas fa-edit" aria-hidden="true"></i> Edit Event
             </a>
              <form method="POST" action="<?php echo baseUrl('event_delete.php'); ?>" class="inline-form">
                 <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                 <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                 <button type="submit" class="action-button action-delete" data-confirm="Permanently delete '<?php e($event['name']); ?>'? Associated RSVPs will also be removed. This cannot be undone.">
                     <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete Event
                 </button>
             </form>
         <?php endif; ?>
    </footer>

    <?php // --- Comments Section (Placeholder) --- ?>
    <?php /*
    <section class="event-comments mt-3 pt-3 border-top" id="comments-section">
        <h3>Comments</h3>
        <p>(Comment functionality is not implemented in this demo)</p>
         // --- Add Comment Form (if logged in) ---
         <?php if (isAuthenticated()): ?>
         <form method="POST" action="<?php echo baseUrl('comment_add.php'); ?>">
             <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
             <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
             <div class="form-group">
                <label for="comment_text">Add your comment:</label>
                <textarea name="comment_text" id="comment_text" rows="3" class="form-textarea" required></textarea>
             </div>
             <button type="submit" class="action-button action-add">Post Comment</button>
         </form>
         <?php else: ?>
             <p><a href="<?php echo baseUrl('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>">Login to add a comment.</a></p>
         <?php endif; ?>

          // --- Display Existing Comments ---
         <div class="comment-list mt-3">
             <?php // Fetch and loop through comments here ?>
             <p>No comments yet.</p>
         </div>
     </section>
    */ ?>

</article>

<?php
renderTemplate('footer');
?>

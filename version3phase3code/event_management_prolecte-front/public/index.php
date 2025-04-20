<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Establishes $db connection and session
require_once '../includes/functions.php'; // Load helper functions

$pageTitle = "Upcoming Events"; // Set page title

// Initialize variables
$events = [];
$currentUserId = $_SESSION['user_id'] ?? 0; // Get current user ID or 0 if not logged in

try {
    // Fetch upcoming or ongoing events that are not cancelled
    // Order by date, then time
    $today = date('Y-m-d');
    // Use a subquery or LEFT JOIN to check RSVP status efficiently
    $sql = "
        SELECT
            e.*,
            (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.user_id = :user_id) AS user_has_rsvpd
        FROM events e
        WHERE e.status IN ('Upcoming', 'Ongoing') AND e.date >= :today
        ORDER BY e.date ASC, e.time ASC
        LIMIT 20"; // Add a LIMIT for pagination later

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':today', $today, PDO::PARAM_STR);
    $stmt->execute();
    $events = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching events on homepage: " . $e->getMessage());
    // Set an error message but allow the page to render (might show empty list)
    setFlashMessage('error', 'Could not load event information at this time due to a database issue.');
}

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <h1 class="page-title"><?php echo escape($pageTitle); ?></h1>
    <p class="text-center lead mb-3">Discover exciting events happening soon!</p>

    <?php // Flash messages are rendered in header.php, so no need to call getFlashMessage() here unless specifically needed again ?>

    <?php if (empty($events) && !isset($_SESSION['flash_message'])): // Show only if no events AND no flash message is currently set ?>
        <div class="message info text-center mt-3">
             <i class="fas fa-info-circle" aria-hidden="true"></i>
            No upcoming events found right now. Please check back later or <a href="<?php echo baseUrl('admin.php'); ?>">add a new event</a> (if admin).
        </div>
    <?php elseif (!empty($events)): ?>
        <div class="event-container">
            <?php foreach ($events as $event):
                 $eventId = (int)$event['id']; // Cast to int
                 $isFull = $event['attendees_count'] >= $event['capacity'] && $event['capacity'] > 0; // Capacity 0 means unlimited
                 $canRSVP = in_array($event['status'], ['Upcoming', 'Ongoing']);
                 $isLoggedInUser = isAuthenticated() && !isAdmin();
                 $csrfToken = generateCsrfToken(); // Generate token for forms inside loop
            ?>
                <div class="event-card status-<?php echo strtolower(escape($event['status'])); ?> <?php echo $isFull ? 'is-full' : ''; ?>">
                    <h2 class="event-card-title">
                        <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>">
                            <?php e($event['name']); ?>
                        </a>
                    </h2>

                    <?php if (!empty($event['image_url'])): // Show image if available ?>
                     <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>" aria-hidden="true" tabindex="-1" class="event-card-image-link">
                        <img src="<?php e($event['image_url']); ?>" alt="" class="event-card-image mb-2" loading="lazy"> <?php // Alt is empty as image is decorative for the link ?>
                    </a>
                    <?php endif; ?>

                    <div class="event-card-details">
                        <p><i class="fas fa-calendar-alt" aria-hidden="true"></i> <span><?php echo formatDate($event['date']); ?></span></p>
                        <p><i class="fas fa-clock" aria-hidden="true"></i> <span><?php echo formatDate($event['time'], 'g:i A'); ?></span></p>
                        <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <span><?php e($event['location']); ?></span></p>
                        <p><i class="fas fa-tags" aria-hidden="true"></i> <span><?php e($event['category']); ?></span></p>
                        <p><i class="fas fa-users" aria-hidden="true"></i> <span><?php echo (int)$event['attendees_count']; ?> / <?php echo ((int)$event['capacity'] === 0 ? 'Unlimited' : (int)$event['capacity']); ?> Spots</span></p>
                         <?php if ($event['status'] === 'Ongoing'): ?>
                            <p class="event-status-highlight"><i class="fas fa-running"></i> <span>Happening Now!</span></p>
                         <?php elseif ($isFull && $canRSVP): ?>
                            <p class="event-status-highlight full"><i class="fas fa-exclamation-circle"></i> <span>Event Full</span></p>
                         <?php endif; ?>
                    </div>

                    <div class="event-card-actions">
                         <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>" class="action-button action-view">
                           <i class="fas fa-eye" aria-hidden="true"></i> Details
                        </a>

                        <?php if ($canRSVP): ?>
                            <?php if ($isLoggedInUser): // RSVP/Cancel for logged-in non-admin users ?>
                               <?php if ($event['user_has_rsvpd']): ?>
                                    <form method="POST" action="<?php echo baseUrl('rsvp.php'); ?>" class="inline-form">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                                        <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="action-button action-rsvp-cancel" data-confirm="Cancel your RSVP for '<?php e($event['name']); ?>'?">
                                           <i class="fas fa-times-circle" aria-hidden="true"></i> Cancel RSVP
                                        </button>
                                    </form>
                               <?php else: // User has not RSVP'd ?>
                                   <?php if (!$isFull): ?>
                                    <form method="POST" action="<?php echo baseUrl('rsvp.php'); ?>" class="inline-form">
                                         <input type="hidden" name="action" value="rsvp">
                                         <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                         <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                                         <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="action-button action-rsvp">
                                           <i class="fas fa-check-circle" aria-hidden="true"></i> RSVP Now
                                        </button>
                                     </form>
                                     <?php else: // Event is full ?>
                                         <span class="action-button is-disabled" aria-disabled="true"><i class="fas fa-exclamation-circle"></i> Full</span>
                                     <?php endif; ?>
                               <?php endif; // End user_has_rsvpd check ?>

                             <?php elseif (isAdmin()): // Show admin actions ?>
                                <a href="<?php echo baseUrl('event_edit.php?id=' . $eventId); ?>" class="action-button action-edit">
                                    <i class="fas fa-edit" aria-hidden="true"></i> Edit
                                </a>
                            <?php elseif (!isAuthenticated()): // Prompt guests to login to RSVP ?>
                                <a href="<?php echo baseUrl('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="action-button action-login-prompt">
                                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login to RSVP
                                </a>
                            <?php endif; ?>
                        <?php else: // Event is not 'Upcoming' or 'Ongoing' ?>
                             <span class="action-button is-disabled" aria-disabled="true">
                                <i class="fas fa-<?php echo $event['status'] === 'Completed' ? 'check' : 'ban'; ?>"></i> <?php e($event['status']); ?>
                             </span>
                        <?php endif; // End $canRSVP check ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
renderTemplate('footer'); // Render footer
?>

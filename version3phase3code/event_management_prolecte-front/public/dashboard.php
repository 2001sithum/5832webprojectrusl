<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// Require user authentication (redirects if not logged in)
// This page is primarily for non-admins, but allow admins to view it too.
requireAuth();

// Redirect admins to the admin panel instead of showing this user dashboard? Optional.
// if (isAdmin()) {
//     redirect('admin.php');
// }

// Retrieve user information from session (already authenticated)
$userId = (int)$_SESSION['user_id'];
$username = escape($_SESSION['username']); // Escape username for display
$pageTitle = $username . "'s Dashboard"; // Dynamic page title

$rsvpdEvents = []; // Initialize event list

try {
    // Fetch events the current user has RSVP'd to
    // Join with events table to get event details
    // Order by event date for relevance (upcoming first)
    $stmt = $db->prepare("
        SELECT e.*
        FROM events e
        JOIN rsvps r ON e.id = r.event_id
        WHERE r.user_id = :user_id
        ORDER BY CASE e.status
                    WHEN 'Ongoing' THEN 1
                    WHEN 'Upcoming' THEN 2
                    WHEN 'Completed' THEN 3
                    WHEN 'Cancelled' THEN 4
                    ELSE 5
                 END ASC, e.date ASC, e.time ASC
    ");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $rsvpdEvents = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard RSVP Fetch Error: User [$userId] - " . $e->getMessage());
    setFlashMessage('error', 'Could not retrieve your RSVP information due to a database error.');
    // Allow page to render even if DB fails, flash message will show
}

$csrfToken = generateCsrfToken(); // For cancel RSVP form

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <h1 class="page-title">Welcome to your Dashboard, <?php echo $username; ?>!</h1>

    <section class="dashboard-section">
        <h2 class="section-title">Your Attending Events</h2>
        <?php if (empty($rsvpdEvents) && !isset($_SESSION['flash_message'])): // Check flash message presence ?>
            <div class="message info">
                <p><i class="fas fa-info-circle"></i> You haven't RSVP'd for any events yet.</p>
                <p><a href="<?php echo baseUrl('index.php'); ?>" class="action-button mt-2" style="display: inline-block;">Find an Event!</a></p>
            </div>
        <?php elseif (!empty($rsvpdEvents)): ?>
            <div class="event-container dashboard-event-list">
                <?php foreach ($rsvpdEvents as $event):
                    $eventId = (int)$event['id'];
                    $canCancel = in_array($event['status'], ['Upcoming', 'Ongoing']);
                ?>
                    <div class="event-card status-<?php echo strtolower(escape($event['status'])); ?>">
                         <h3 class="event-card-title">
                            <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>">
                                <?php e($event['name']); ?>
                            </a>
                        </h3>
                        <div class="event-card-details">
                            <p><i class="fas fa-calendar-alt" aria-hidden="true"></i> <span><?php echo formatDate($event['date']); ?> at <?php echo formatDate($event['time'], 'g:i A'); ?></span></p>
                            <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <span><?php e($event['location']); ?></span></p>
                            <p><i class="fas fa-check-square" aria-hidden="true"></i> Status: <strong class="event-status-<?php echo strtolower(escape($event['status'])); ?>"><?php e($event['status']); ?></strong></p>
                        </div>

                        <div class="event-card-actions">
                             <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>" class="action-button action-view">
                               <i class="fas fa-eye" aria-hidden="true"></i> Details
                            </a>
                             <?php // Only allow cancelling RSVP if the event is 'Upcoming' or 'Ongoing' ?>
                             <?php if ($canCancel): ?>
                                <form method="POST" action="<?php echo baseUrl('rsvp.php'); ?>" class="inline-form">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                                    <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); // Redirect back to dashboard ?>">
                                    <button type="submit" class="action-button action-rsvp-cancel" data-confirm="Cancel your RSVP for '<?php e($event['name']); ?>'?">
                                       <i class="fas fa-times-circle" aria-hidden="true"></i> Cancel RSVP
                                    </button>
                                </form>
                             <?php else: ?>
                                 <span class="action-button is-disabled" aria-disabled="true">
                                    <i class="fas fa-<?php echo $event['status'] === 'Completed' ? 'check' : 'ban'; ?>"></i> <?php e($event['status']); ?>
                                 </span>
                             <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php /* Placeholder for other dashboard features */ ?>
    <?php /*
    <section class="dashboard-section mt-3">
        <h2 class="section-title">Account Settings</h2>
        <p>Manage your profile information.</p>
         <div class="dashboard-actions mt-2">
            <a href="<?php echo baseUrl('profile_edit.php'); ?>" class="action-button action-edit"><i class="fas fa-user-edit"></i> Edit Profile</a>
        </div>
    </section>
    */ ?>

<?php
renderTemplate('footer');
?>

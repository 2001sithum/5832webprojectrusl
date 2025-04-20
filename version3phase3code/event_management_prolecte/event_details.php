<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$eventId || $eventId <= 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid event selected.'];
    redirect('events.php');
}

$event = null;
$userHasRSVPd = false;
$isFull = false;
$canRSVP = false;

try {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event) {
         $_SESSION['message'] = ['type' => 'error', 'text' => 'Event not found.'];
         redirect('events.php');
    }

    $pageTitle = escape($event['name']);

    if(isLoggedIn()){
        $stmtCheck = $db->prepare("SELECT 1 FROM rsvps WHERE user_id = ? AND event_id = ?");
        $stmtCheck->execute([$_SESSION['user_id'], $eventId]);
        $userHasRSVPd = (bool)$stmtCheck->fetch();
    }

    $isFull = $event['attendees_count'] >= $event['capacity'];
    $canRSVP = in_array($event['status'], ['Upcoming', 'Ongoing']);

} catch(PDOException $e) {
    error_log("Event Details Error: ID [$eventId] - " . $e->getMessage());
     $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load event details.'];
     redirect('events.php');
}

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <section class="event-detail-section content-section">

        <?php if($event['image_url']): ?>
            <?php
                 $fallbackUrl = 'images/event_default.jpg';
                 $headerImageUrl = (!empty($event['image_url']) && filter_var($event['image_url'], FILTER_VALIDATE_URL)) ? $event['image_url'] : $fallbackUrl;
            ?>
             <div class="event-detail-banner" style="background-image: url('<?php e($headerImageUrl); ?>');">
                 <?php /* Could add an overlay here if needed */ ?>
             </div>
        <?php endif; ?>

        <div class="event-detail-header">
            <span class="event-detail-category"><?php e($event['category'] ?? 'General'); ?></span>
            <h1><?php e($event['name']); ?></h1>
            <div class="event-meta-icons">
                 <span><i class="far fa-calendar-alt"></i> <?php echo escape(date('l, F j, Y', strtotime($event['date']))); ?></span>
                 <span><i class="far fa-clock"></i> <?php echo escape(date('g:i A', strtotime($event['time']))); ?></span>
                 <span><i class="fas fa-map-marker-alt"></i> <?php e($event['location']); ?></span>
                 <span><i class="fas fa-users"></i> <?php e($event['attendees_count'] ?? 0); ?> / <?php e($event['capacity'] ?? 'N/A'); ?> Spots</span>
                 <span><i class="fas fa-flag"></i> Status: <strong class="status-<?php echo strtolower(escape($event['status'])); ?>"><?php e($event['status']); ?></strong></span>
            </div>
        </div>

        <div class="event-detail-body">
             <div class="event-description">
                 <h2>About the Event</h2>
                 <?php echo nl2br(escape($event['description'])); // Preserve line breaks ?>
            </div>

            <div class="event-actions">
                <h2>Actions</h2>
                <?php if ($canRSVP): ?>
                    <?php if (isLoggedIn()): ?>
                         <?php if ($userHasRSVPd): ?>
                             <form action="rsvp_handler.php" method="POST" class="inline-form">
                                 <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="event_id" value="<?php echo (int)$eventId; ?>">
                                 <?php /* Add CSRF later if needed */ ?>
                                <button type="submit" class="button button-danger" data-confirm="Are you sure you want to cancel your RSVP?">
                                    <i class="fas fa-times-circle"></i> Cancel RSVP
                                </button>
                            </form>
                         <?php elseif ($isFull): ?>
                             <button class="button" disabled><i class="fas fa-exclamation-circle"></i> Event Full</button>
                         <?php else: ?>
                             <form action="rsvp_handler.php" method="POST" class="inline-form">
                                <input type="hidden" name="action" value="rsvp">
                                 <input type="hidden" name="event_id" value="<?php echo (int)$eventId; ?>">
                                 <?php /* Add CSRF later if needed */ ?>
                                <button type="submit" class="button button-primary">
                                     <i class="fas fa-check-circle"></i> RSVP Now
                                 </button>
                            </form>
                         <?php endif; ?>
                    <?php else: // Not logged in ?>
                         <a href="login.php" class="button button-secondary">
                             <i class="fas fa-sign-in-alt"></i> Login to RSVP
                         </a>
                     <?php endif; ?>
                 <?php elseif($event['status'] === 'Completed'): ?>
                    <span class="button is-disabled"><i class="fas fa-check"></i> Event Completed</span>
                 <?php elseif($event['status'] === 'Cancelled'): ?>
                    <span class="button is-disabled status-cancelled"><i class="fas fa-ban"></i> Event Cancelled</span>
                 <?php endif; ?>

                 <a href="events.php" class="button button-secondary"><i class="fas fa-arrow-left"></i> Back to Events</a>

                 <?php if(isAdmin()): ?>
                    <a href="edit_event.php?id=<?php echo (int)$eventId; ?>" class="button button-warning"><i class="fas fa-edit"></i> Edit Event</a>
                 <?php endif; ?>
             </div>
        </div>
         <?php /* Placeholder for comments */ ?>
         <div class="event-comments border-top mt-3 pt-3">
             <h2>Comments</h2>
            <p>Comments section coming soon!</p>
            <?php /* Include comments form/list here later */ ?>
         </div>

    </section>

<?php includeTemplate('footer'); ?>

<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin(); // Ensure user is logged in

// Allow admins to see dashboard but maybe show a different message/links
$isAdminView = isAdmin();
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$pageTitle = $username . "'s Dashboard";

$rsvpdEvents = [];
if (!$isAdminView) { // Don't fetch RSVPs for admin on user dashboard
     try {
         $stmt = $db->prepare("SELECT e.* FROM events e JOIN rsvps r ON e.id = r.event_id WHERE r.user_id = ? ORDER BY e.date ASC, e.time ASC");
         $stmt->execute([$userId]);
        $rsvpdEvents = $stmt->fetchAll();
     } catch (PDOException $e) {
         error_log("Dashboard RSVP Fetch Error: User [$userId] - " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load your RSVP list.'];
     }
}

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>
    <section class="content-section dashboard">
        <h1 class="page-title">Welcome, <?php e($username); ?>!</h1>

         <?php // displayMessages(); // Now handled in header ?>

         <?php if($isAdminView): ?>
            <div class="message info">
                 <p>You are logged in as an Administrator.</p>
                <p><a href="admin/index.php" class="button button-secondary">Go to Admin Panel</a></p>
            </div>
         <?php else: ?>
            <h2 class="section-title">My Upcoming RSVPs</h2>
            <?php if (empty($rsvpdEvents)): ?>
                 <div class="message info text-center">
                     You haven't RSVP'd for any upcoming events. <br>
                    <a href="events.php" class="button button-primary mt-2">Find Events Now</a>
                 </div>
            <?php else: ?>
                <div class="event-card-grid dashboard-grid">
                     <?php foreach ($rsvpdEvents as $event): ?>
                          <?php
                             $fallbackUrl = 'images/event_default.jpg';
                              $cardImageUrl = $event['image_url'] ?? '';
                              if (empty($cardImageUrl) || !filter_var($cardImageUrl, FILTER_VALIDATE_URL)) { $cardImageUrl = $fallbackUrl; }
                              $canCancel = in_array($event['status'], ['Upcoming', 'Ongoing']);
                          ?>
                         <div class="event-card animate-on-scroll">
                             <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="event-card-image-link">
                                 <img src="<?php e($cardImageUrl); ?>" alt="" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallbackUrl; ?>';">
                              </a>
                            <div class="event-card-content">
                                 <h3 class="event-card-title"><a href="event_details.php?id=<?php echo (int)$event['id']; ?>"><?php e($event['name']); ?></a></h3>
                                 <p class="event-card-date"><i class="far fa-calendar-alt"></i> <?php echo escape(date('M j, Y', strtotime($event['date']))); ?> | <?php echo escape(date('g:i A', strtotime($event['time']))); ?></p>
                                 <p class="event-card-location"><i class="fas fa-map-marker-alt"></i> <?php e($event['location']); ?></p>
                                 <p><i class="fas fa-flag"></i> Status: <strong class="status-<?php echo strtolower(escape($event['status'])); ?>"><?php e($event['status']); ?></strong></p>

                                <div class="event-card-actions">
                                     <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="button button-secondary button-small">View Details</a>
                                     <?php if($canCancel): ?>
                                         <form action="rsvp_handler.php" method="POST" class="inline-form">
                                             <input type="hidden" name="action" value="cancel">
                                             <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                                             <?php /* Add CSRF if needed */ ?>
                                              <input type="hidden" name="redirect_to" value="dashboard.php"> <?php // Force back to dash ?>
                                             <button type="submit" class="button button-danger button-small" data-confirm="Cancel RSVP for '<?php e($event['name']); ?>'?">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                         </form>
                                    <?php endif; ?>
                                 </div>
                            </div>
                        </div>
                     <?php endforeach; ?>
                 </div>
            <?php endif; ?>
         <?php endif; // end is normal user view ?>

     </section>

     <?php /* Add profile links/section */ ?>
     <section class="content-section border-top mt-3 pt-3">
         <h2 class="section-title">Account</h2>
         <a href="#" class="button button-secondary">Edit Profile</a>
         <a href="logout.php" class="button button-warning">Logout</a>
     </section>

<?php includeTemplate('footer'); ?>

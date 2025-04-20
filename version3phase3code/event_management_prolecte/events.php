<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "All Events";

// TODO: Add filtering/searching later if needed
$events = [];
try {
    $stmt = $db->prepare("SELECT * FROM events WHERE status = 'Upcoming' OR status = 'Ongoing' ORDER BY date ASC, time ASC");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Events Page Fetch Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not load events list.'];
}

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <section class="content-section">
        <h1 class="page-title"><?php e($pageTitle); ?></h1>
         <p class="lead text-center">Find workshops, conferences, music, art, and more.</p>

         <?php // TODO: Add search/filter form here ?>
        <!--
        <form action="events.php" method="GET" class="search-filter-form">
            <input type="text" name="search" placeholder="Search by name or category...">
            <input type="date" name="date_from">
            <select name="category"> <option value="">All Categories</option> ... </select>
            <button type="submit" class="button">Filter</button>
        </form>
        -->

        <?php if (empty($events)): ?>
             <div class="message info text-center mt-3">
                 No upcoming or ongoing events found matching your criteria. Please check back soon.
             </div>
        <?php else: ?>
            <div class="event-card-grid wide-grid"> <?php /* Maybe wider grid for list page */ ?>
                <?php foreach ($events as $event): ?>
                     <?php
                        // Image Fallback Logic
                         $fallbackUrl = 'images/event_default.jpg';
                         $imageUrl = $event['image_url'] ?? '';
                         if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                             $imageUrl = $fallbackUrl; // Check relative paths optionally here
                         }
                     ?>
                    <div class="event-card animate-on-scroll">
                        <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="event-card-image-link">
                            <img src="<?php e($imageUrl); ?>" alt="" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallbackUrl; ?>';">
                         </a>
                         <div class="event-card-content">
                            <span class="event-card-category"><?php e($event['category'] ?? 'General'); ?></span>
                             <h3 class="event-card-title"><a href="event_details.php?id=<?php echo (int)$event['id']; ?>"><?php e($event['name']); ?></a></h3>
                             <p class="event-card-date"><i class="far fa-calendar-alt"></i> <?php echo escape(date('M j, Y', strtotime($event['date']))); ?> | <?php echo escape(date('g:i A', strtotime($event['time']))); ?></p>
                             <p class="event-card-location"><i class="fas fa-map-marker-alt"></i> <?php e($event['location']); ?></p>
                            <div class="event-card-actions">
                                <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="button button-secondary button-small">View Details</a>
                                <?php /* Basic RSVP status maybe? More complex logic needed here based on session */ ?>
                             </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>

<?php includeTemplate('footer'); ?>

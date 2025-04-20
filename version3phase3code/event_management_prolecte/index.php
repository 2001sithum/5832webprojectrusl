<?php
require_once 'includes/db.php'; // Includes config, db connection, session_start
require_once 'includes/functions.php';

$pageTitle = "Welcome to " . APP_NAME; // Set page title

// Fetch maybe 3 upcoming events for the landing page
$upcomingEvents = [];
try {
    $stmt = $db->prepare("SELECT * FROM events WHERE status = 'Upcoming' AND date >= date('now') ORDER BY date ASC, time ASC LIMIT 3");
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Landing Page Event Fetch Error: " . $e->getMessage());
    // Don't necessarily show error, page can function without events
}

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <!-- ================== Hero Section with Banner ================== -->
    <section class="hero-section parallax-banner" style="background-image: url('images/banners/banner1.jpg');"> <?php /* Style inline for simplicity, better in CSS */ ?>
        <div class="hero-overlay"></div>
        <div class="hero-content animated-letters">
            <h1>Experience Unforgettable Events</h1>
            <p class="subtitle">Discover, Connect, and Celebrate</p>
            <a href="events.php" class="cta-button">Explore Events</a>
        </div>
         <div class="scroll-down-indicator">
             <a href="#featured-events" aria-label="Scroll down"><i class="fas fa-chevron-down"></i></a>
         </div>
    </section>

    <!-- ================== Featured Events Section ================== -->
    <?php if (!empty($upcomingEvents)): ?>
    <section id="featured-events" class="featured-events-section content-section">
        <h2 class="section-title">Upcoming Highlights</h2>
        <div class="event-card-grid">
            <?php foreach ($upcomingEvents as $event): ?>
                 <?php
                    // Basic image fallback for cards
                    $eventImageUrl = $event['image_url'] ?? '';
                     $fallbackUrl = 'images/event_default.jpg';
                     $imageUrl = (!empty($eventImageUrl) && filter_var($eventImageUrl, FILTER_VALIDATE_URL)) ? $eventImageUrl : $fallbackUrl;
                     // Optionally check relative paths: if(file_exists($eventImageUrl)) { $imageUrl = $eventImageUrl; } else { $imageUrl = $fallbackUrl;}
                 ?>
                <div class="event-card animate-on-scroll">
                    <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="event-card-image-link">
                        <img src="<?php e($imageUrl); ?>" alt="<?php e($event['name']); ?>" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallbackUrl; ?>';">
                    </a>
                    <div class="event-card-content">
                        <h3 class="event-card-title"><a href="event_details.php?id=<?php echo (int)$event['id']; ?>"><?php e($event['name']); ?></a></h3>
                        <p class="event-card-date"><i class="far fa-calendar-alt"></i> <?php echo escape(date('M j, Y', strtotime($event['date']))); ?> at <?php echo escape(date('g:i A', strtotime($event['time']))); ?></p>
                        <p class="event-card-location"><i class="fas fa-map-marker-alt"></i> <?php e($event['location']); ?></p>
                        <a href="event_details.php?id=<?php echo (int)$event['id']; ?>" class="button button-secondary button-small">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
         <div class="text-center">
             <a href="events.php" class="button button-primary">See All Events</a>
        </div>
    </section>
    <?php endif; ?>

     <!-- ================== "Why Choose Us" or Feature Section ================== -->
     <section class="feature-section content-section bg-light animate-on-scroll">
         <h2 class="section-title">Why Choose Us?</h2>
         <div class="feature-grid">
             <div class="feature-item">
                 <i class="fas fa-search-location fa-3x feature-icon"></i>
                 <h3>Discover Easily</h3>
                 <p>Find local events tailored to your interests with our intuitive search and categories.</p>
             </div>
              <div class="feature-item">
                 <i class="fas fa-calendar-check fa-3x feature-icon"></i>
                 <h3>Seamless RSVP</h3>
                 <p>Register and manage your attendance for events quickly and easily online.</p>
             </div>
             <div class="feature-item">
                  <i class="fas fa-users fa-3x feature-icon"></i>
                 <h3>Connect & Engage</h3>
                 <p>Join a community of event-goers and stay updated on the latest happenings.</p>
             </div>
         </div>
     </section>

     <!-- ================== Call to Action Section ================== -->
     <section class="cta-section content-section text-center parallax-banner" style="background-image: url('images/banners/banner2.jpg');">
        <div class="hero-overlay darker"></div> <?php /* Darker overlay for CTA */ ?>
        <div class="cta-content">
            <h2 class="cta-title">Ready to Host Your Event?</h2>
            <p>We provide tools and support to make your event a success. Get in touch!</p>
            <a href="contact.php" class="cta-button">Contact Organizer Support</a> <?php /* Example link */ ?>
        </div>
    </section>


<?php includeTemplate('footer'); ?>

<?php
require_once 'includes/db.php'; // Only if fetching images/past events from DB
require_once 'includes/functions.php';

$pageTitle = "Gallery - Past Events";

// --- Sample Gallery Data (Replace/Augment with DB data later) ---
// Assuming images are in 'images/gallery/'
$galleryItems = [
    ['image' => 'images/gallery/image1.jpg', 'title' => 'Tech Conference 2023', 'description' => 'Keynote speaker addressing the audience.'],
    ['image' => 'images/gallery/image2.jpg', 'title' => 'Summer Music Fest', 'description' => 'Main stage performance at sunset.'],
    ['image' => 'images/gallery/image3.jpg', 'title' => 'Art Expo Opening Night', 'description' => 'Attendees admiring artwork.'],
    ['image' => 'images/gallery/image4_food.jpg', 'title' => 'Food Truck Rally', 'description' => 'Diverse culinary offerings.'],
    ['image' => 'images/gallery/image5.jpg', 'title' => 'Tech Conference Networking', 'description' => 'Professionals connecting between sessions.'],
    ['image' => 'images/gallery/image6.jpg', 'title' => 'Charity Fun Run', 'description' => 'Participants crossing the finish line.'],
    ['image' => 'images/gallery/IMG1.jpg', 'title' => 'Coding Workshop', 'description' => 'Collaborative learning session.'],
    ['image' => 'images/gallery/IMG2.jpg', 'title' => 'Festival Crowd Energy', 'description' => 'The lively atmosphere.'],
];
// --- End Sample Data ---

// TODO: Fetch past event data from DB and construct $galleryItems dynamically if needed

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

<section class="content-section gallery-section">
    <h1 class="page-title animated-letters">Event Gallery</h1>
    <p class="lead text-center">A glimpse into some of the successful events hosted.</p>

    <?php // displayMessages(); // In header ?>

     <?php if(empty($galleryItems)): ?>
         <div class="message info text-center">Gallery is currently empty. Check back soon!</div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach($galleryItems as $item): ?>
                 <?php
                      $itemImage = $item['image'] ?? '';
                      $fallbackGallery = 'images/gallery/placeholder.jpg'; // Have a placeholder
                      $imageUrl = (!empty($itemImage) && file_exists($itemImage)) ? $itemImage : $fallbackGallery;
                 ?>
                <div class="gallery-item animate-on-scroll zoom-in">
                    <a href="<?php e($imageUrl); ?>" data-lightbox="event-gallery" data-title="<?php e($item['title'] ?? ''); ?>: <?php e($item['description'] ?? ''); ?>">
                        <img src="<?php e($imageUrl); ?>" alt="<?php e($item['title'] ?? 'Event gallery image'); ?>" loading="lazy">
                         <div class="gallery-item-overlay">
                             <h3><?php e($item['title'] ?? 'Event Highlight'); ?></h3>
                             <p><?php e($item['description'] ?? ''); ?></p>
                             <i class="fas fa-search-plus"></i>
                        </div>
                    </a>
                 </div>
             <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>

<?php /* Include Lightbox CSS/JS or use a simpler modal */ ?>
<?php /* Example using a hypothetical Lightbox library (add links in header/footer) */ ?>
<?php /* <link rel="stylesheet" href="path/to/lightbox.css"> */ ?>
<?php /* <script src="path/to/lightbox.js"></script> */ ?>

<?php /* Add basic CSS for gallery grid (add to main.css) */ ?>
<style>
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-md); margin-top: var(--spacing-lg); }
    .gallery-item { position: relative; overflow: hidden; border-radius: var(--border-radius-md); box-shadow: var(--shadow); }
    .gallery-item img { display: block; width: 100%; height: 250px; object-fit: cover; transition: transform 0.4s ease; }
    .gallery-item a { display: block; }
    .gallery-item:hover img { transform: scale(1.1); }
    .gallery-item-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0, 0, 0, 0.7); color: white; padding: var(--spacing-md); transform: translateY(100%); transition: transform 0.4s ease; text-align: center;}
    .gallery-item:hover .gallery-item-overlay { transform: translateY(0); }
    .gallery-item-overlay h3 { font-size: 1.1rem; margin: 0 0 var(--spacing-xs) 0; color: white; }
    .gallery-item-overlay p { font-size: 0.85rem; margin: 0 0 var(--spacing-sm) 0; color: var(--text-medium); }
    .gallery-item-overlay i { font-size: 1.5rem; opacity: 0.8; }
</style>

<?php includeTemplate('footer'); ?>

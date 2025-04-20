<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Our Team";

// --- Sample Team Data (Replace with database fetch later) ---
$teamMembers = [
    ['name' => 'Alex Johnson', 'role' => 'Lead Event Coordinator', 'bio' => 'Passionate about creating memorable experiences.', 'image' => 'images/team/team_member1.jpg'],
    ['name' => 'Maria Garcia', 'role' => 'Marketing & Outreach', 'bio' => 'Connecting events with the right audience.', 'image' => 'images/team/team_member2.jpg'],
    ['name' => 'Sam Chen', 'role' => 'Technical Support Lead', 'bio' => 'Ensuring smooth online experiences.', 'image' => 'images/team/team_member3.jpg'],
    ['name' => 'Priya Kumar', 'role' => 'Venue & Logistics', 'bio' => 'Handling all the behind-the-scenes details.', 'image' => 'images/team/team_member4.jpg'],
];
// --- End Sample Data ---


includeTemplate('header', ['pageTitle' => $pageTitle]);
?>

<section class="content-section team-section">
    <h1 class="page-title animated-letters">Meet the Team</h1>
    <p class="lead text-center">The passionate people behind <?php e(APP_NAME); ?>.</p>

     <?php // displayMessages(); // In header ?>

    <div class="team-grid">
         <?php foreach ($teamMembers as $member): ?>
            <?php
                 // Image fallback for team members
                 $memberImage = $member['image'] ?? '';
                 $fallbackTeamImage = 'images/team/default_avatar.png'; // Assume you have a default avatar
                 $imageUrl = (!empty($memberImage) && file_exists($memberImage)) ? $memberImage : $fallbackTeamImage;
            ?>
             <div class="team-member-card animate-on-scroll fade-in-up">
                <div class="team-member-photo">
                    <img src="<?php e($imageUrl); ?>" alt="Photo of <?php e($member['name']); ?>" loading="lazy">
                </div>
                <div class="team-member-info">
                     <h3 class="team-member-name"><?php e($member['name']); ?></h3>
                    <p class="team-member-role"><?php e($member['role']); ?></p>
                    <p class="team-member-bio"><?php e($member['bio']); ?></p>
                     <?php /* Optional: Social media links?
                     <div class="team-member-social">
                        <a href="#" aria-label="<?php e($member['name']); ?> on Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="<?php e($member['name']); ?> on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                     </div>
                      */ ?>
                </div>
             </div>
         <?php endforeach; ?>
     </div>

</section>

<?php /* Add CSS for team grid to main.css */ ?>
<style>
    .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-xl); margin-top: var(--spacing-lg); }
    .team-member-card { background: var(--bg-light); border-radius: var(--border-radius-lg); overflow: hidden; box-shadow: var(--shadow); text-align: center; padding-bottom: var(--spacing-lg); transition: transform 0.3s ease; }
    .team-member-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
    .team-member-photo img { width: 100%; height: 280px; object-fit: cover; display: block; }
    .team-member-info { padding: var(--spacing-lg); }
    .team-member-name { font-size: 1.3rem; margin-bottom: var(--spacing-xs); color: var(--text-light); }
    .team-member-role { font-size: 0.95rem; color: var(--primary-color); margin-bottom: var(--spacing-md); font-weight: var(--font-weight-medium); }
    .team-member-bio { font-size: 0.9rem; color: var(--text-medium); }
    .team-member-social { margin-top: var(--spacing-md); }
    .team-member-social a { color: var(--text-medium); margin: 0 var(--spacing-sm); font-size: 1.3rem; transition: color 0.3s ease; }
    .team-member-social a:hover { color: var(--primary-color); }
</style>

<?php includeTemplate('footer'); ?>

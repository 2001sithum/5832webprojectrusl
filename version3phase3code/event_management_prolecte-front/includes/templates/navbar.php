<?php
/**
 * Navigation Bar Template
 *
 * Receives data: $isLoggedIn (bool), $isAdmin (bool), $username (?string - already escaped in header)
 */
?>
<header class="navbar" role="banner">
    <div class="navbar-brand">
        <?php /* Consider adding a logo image later */ ?>
        <a href="<?php echo baseUrl('index.php'); ?>" aria-label="<?php echo escape(APP_NAME); ?> Homepage">
            <i class="fas fa-ticket-alt" aria-hidden="true"></i> <?php /* Example Icon */ ?>
            <span><?php echo escape(APP_NAME); ?></span>
        </a>
    </div>
    <nav class="navbar-links" id="main-navigation" aria-label="Main Navigation">
        <a href="<?php echo baseUrl('index.php'); ?>"><i class="fas fa-home" aria-hidden="true"></i> Home</a>
        <?php if ($isLoggedIn): ?>
            <?php if ($isAdmin): ?>
                <a href="<?php echo baseUrl('admin.php'); ?>"><i class="fas fa-user-shield" aria-hidden="true"></i> Admin Panel</a>
                <?php // Add other admin-specific links if needed (e.g., user management) ?>
            <?php else: // Regular logged-in user ?>
                <a href="<?php echo baseUrl('dashboard.php'); ?>"><i class="fas fa-tachometer-alt" aria-hidden="true"></i> Dashboard</a>
            <?php endif; ?>

            <?php /* User Info and Logout Section */ ?>
            <div class="navbar-user-section">
                 <span class="navbar-user" role="status" aria-live="polite">
                     <i class="fas fa-user" aria-hidden="true"></i> Welcome, <?php echo $username; ?>!
                 </span>
                <a href="<?php echo baseUrl('logout.php'); ?>" class="navbar-logout-button">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout
                </a>
             </div>

        <?php else: // Not logged in ?>
            <a href="<?php echo baseUrl('login.php'); ?>"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login</a>
            <a href="<?php echo baseUrl('register.php'); ?>"><i class="fas fa-user-plus" aria-hidden="true"></i> Register</a>
        <?php endif; ?>
         <?php /* <a href="<?php echo baseUrl('contact.php'); ?>"><i class="fas fa-envelope" aria-hidden="true"></i> Contact</a> */ ?>
        <?php // Add other global links like About, Help, etc. if needed ?>
    </nav>
    <?php /* Hamburger menu toggle button for mobile (initially hidden by CSS) */ ?>
    <button class="navbar-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-navigation">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
</header>

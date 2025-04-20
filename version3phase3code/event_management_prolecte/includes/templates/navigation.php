<?php
// Determine active page for styling (simple version)
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="main-header">
    <nav class="navbar">
        <div class="nav-brand">
            <a href="index.php">
                <i class="fas fa-calendar-alt fa-lg logo-icon"></i> <?php /* Logo Icon */ ?>
                <span class="logo-text"><?php e(APP_NAME); ?></span>
            </a>
        </div>
        <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav-links">
             <span class="nav-toggle-icon"></span> <?php /* CSS will create the bars */ ?>
        </button>
        <ul class="nav-links" id="main-nav-links" data-visible="false">
            <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="events.php" class="<?php echo ($currentPage == 'events.php' || $currentPage == 'event_details.php') ? 'active' : ''; ?>">Events</a></li>
            <li><a href="gallery.php" class="<?php echo ($currentPage == 'gallery.php') ? 'active' : ''; ?>">Gallery</a></li>
             <li><a href="team.php" class="<?php echo ($currentPage == 'team.php') ? 'active' : ''; ?>">Team</a></li>
            <li><a href="contact.php" class="<?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>

            <li class="nav-separator"></li> <?php /* Separator */ ?>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                     <li><a href="admin/index.php" class="<?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Admin</a></li>
                 <?php else: ?>
                     <li><a href="dashboard.php" class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-user"></i> Dashboard</a></li>
                 <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-button <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Login</a></li>
                 <li><a href="register.php" class="nav-button nav-button-secondary <?php echo ($currentPage == 'register.php') ? 'active' : ''; ?>">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo escape(APP_NAME); ?> - Discover and manage events.">
    <title><?php echo escape($pageTitle ?? DEFAULT_PAGE_TITLE); ?> - <?php echo escape(APP_NAME); ?></title>

    <?php /* Favicon Links - Replace with your actual favicons */ ?>
    <link rel="icon" href="<?php echo baseUrl('assets/images/favicon.ico'); ?>" sizes="any"> <?php /* Example */ ?>
    <?php /* <link rel="icon" href="<?php echo baseUrl('assets/images/favicon.svg'); ?>" type="image/svg+xml"> */ ?>
    <?php /* <link rel="apple-touch-icon" href="<?php echo baseUrl('assets/images/apple-touch-icon.png'); ?>"> */ ?>

    <?php /* Google Fonts (Example: Roboto) */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <?php /* FontAwesome CDN (ensure integrity and referrerpolicy for security) */ ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <?php /* Main Stylesheet */ ?>
    <link rel="stylesheet" href="<?php echo baseUrl('css/style.css?v=' . filemtime('../public/css/style.css')); ?>"> <?php /* Cache busting */ ?>

    <?php /* Include specific CSS for the page if needed (passed via $data in renderTemplate) */ ?>
    <?php if (isset($pageStylesheets) && is_array($pageStylesheets)): ?>
        <?php foreach ($pageStylesheets as $cssFile): ?>
            <link rel="stylesheet" href="<?php echo baseUrl('css/' . escape($cssFile)); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

</head>
<body>
<?php
    // Prepare data for the navbar template
    $navbarData = [
        'isLoggedIn' => isAuthenticated(),
        'isAdmin' => isAdmin(),
        'username' => escape($_SESSION['username'] ?? null) // Escape username here
    ];
    // Render Navbar - Session data should be available if database.php ran
    renderTemplate('navbar', $navbarData);
?>
    <main class="container" id="main-content"> <?php // Added id for potential skip links ?>
        <?php // Render flash messages globally, right after the navbar and before main content
              renderTemplate('messages');
        ?>
        <?php // Main page content starts after this in the specific page templates ?>

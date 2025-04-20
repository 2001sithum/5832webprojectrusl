<?php if (session_status() == PHP_SESSION_NONE) { @session_start(); } // Ensure session started ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' | ' : ''; echo escape(APP_NAME); ?></title>
    <link rel="stylesheet" href="css/main.css?v=<?php echo filemtime('css/main.css'); // Basic cache bust ?>">
    <link rel="stylesheet" href="css/animations.css?v=<?php echo filemtime('css/animations.css'); ?>">
    <?php /* FontAwesome or other icon library */ ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?php /* Google Fonts Example */ ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <?php /* --- Animation Library (GSAP example) --- */ ?>
    <?php /* Option 1: CDN */ ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" integrity="sha512-7eHRwcbYkK4d9g/6tD/mhkf++eoTHwpNM9woBxtPUBWm67zeAfFC+HrdoE2GanKeocly/VxeLvIqwvCdk7qScg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" integrity="sha512-onMTRKJBKz8M1TnqqDuGBlowlH0ohFzMXYRN1EQUfvYYIFpocmLbrApHuvIjWMHQLmODfn+hrefCLzEanpgsAg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
     <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/TextPlugin.min.js" integrity="sha512-xAxjKW1J/nrrdlNbKTorASViTPWgHAzKyJad9 масштаб/+jsN9wwMuwLdz+2stXKglvE8BMfE48I4ZzmTi00rUo1sw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> <?php /* If using TextPlugin */ ?>
    <?php /* Option 2: Local file (Download GASP files into js/lib/) */ ?>
    <?php /* <script src="js/lib/gsap.min.js"></script> */ ?>
     <?php /* <script src="js/lib/ScrollTrigger.min.js"></script> */ ?>
    <?php /* <script src="js/lib/TextPlugin.min.js"></script> */ ?>

</head>
<body class="preload"> <?php // Add preload class to potentially hide elements until JS runs ?>
    <?php includeTemplate('navigation'); // Include navigation ?>
    <main id="main-content">
      <?php displayMessages(); // Display any flash messages right below nav ?>
     <?php // <!-- Page Specific Content Starts Here --> ?>
